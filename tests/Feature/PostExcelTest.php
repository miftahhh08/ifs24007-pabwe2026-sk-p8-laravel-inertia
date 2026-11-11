<?php

namespace Tests\Feature;

use App\Features\Post\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class PostExcelTest extends TestCase
{
    use RefreshDatabase;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->member = User::factory()->create();
    }

    /** Membuat berkas .xlsx sementara dari array baris. */
    private function makeXlsx(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray($rows, null, 'A1');

        $path = tempnam(sys_get_temp_dir(), 'xlsx').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile(
            $path,
            'artikel.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );
    }

    /** Membaca isi unduhan Excel menjadi array baris. */
    private function readDownload($response): array
    {
        $path = tempnam(sys_get_temp_dir(), 'dl').'.xlsx';
        file_put_contents($path, $response->streamedContent());

        return IOFactory::load($path)->getActiveSheet()->toArray();
    }

    public function test_member_exports_only_own_posts(): void
    {
        Post::factory()->for($this->member)->create(['title' => 'Milik Saya', 'content' => '=1+1']);
        Post::factory()->draft()->for($this->member)->create(['title' => 'Draf Saya', 'excerpt' => null]);
        Post::factory()->create(['title' => 'Milik Orang Lain']);

        $response = $this->actingAs($this->member)->get('/dashboard/posts/export');
        $response->assertOk()->assertDownload();

        $rows = $this->readDownload($response);

        $this->assertSame('Judul', $rows[0][1]);
        $this->assertCount(3, $rows); // header + 2 artikel
        $titles = array_column($rows, 1);
        $this->assertContains('Milik Saya', $titles);
        $this->assertNotContains('Milik Orang Lain', $titles);
        $this->assertContains('=1+1', array_column($rows, 4)); // rumus tidak dieksekusi
    }

    public function test_member_can_import_posts(): void
    {
        $file = $this->makeXlsx([
            ['Judul', 'Kategori', 'Ringkasan', 'Isi', 'Status'],
            ['Artikel A', 'bisnis', 'Ringkas A', 'Isi A', 'Terpublikasi'],
            [null, null, null, null, null],
            ['Artikel B', 'Kategori Ngawur', '', 'Isi B', 'Draf'],
            ['', 'Umum', '', 'Isi tanpa judul', 'Draf'],
            [str_repeat('x', 256), 'Umum', '', 'Judul kepanjangan', 'Draf'],
        ]);

        $this->actingAs($this->member)->from('/dashboard/posts')
            ->post('/dashboard/posts/import', ['file' => $file])
            ->assertRedirect('/dashboard/posts')
            ->assertSessionHas('success', fn ($m) => str_contains($m, '2 artikel berhasil diimpor') && str_contains($m, '2 baris dilewati'));

        $a = Post::where('title', 'Artikel A')->first();
        $this->assertSame('Bisnis', $a->category->value);
        $this->assertTrue($a->is_published);
        $this->assertSame($this->member->id, $a->user_id);

        $b = Post::where('title', 'Artikel B')->first();
        $this->assertSame('Umum', $b->category->value);
        $this->assertFalse($b->is_published);
        $this->assertNull($b->excerpt);
    }

    public function test_import_works_with_only_required_columns(): void
    {
        $file = $this->makeXlsx([['Judul', 'Isi'], ['Hanya Dua Kolom', 'Isinya ada']]);

        $this->actingAs($this->member)->post('/dashboard/posts/import', ['file' => $file])
            ->assertSessionHas('success', fn ($m) => str_contains($m, '1 artikel berhasil diimpor') && ! str_contains($m, 'dilewati'));

        $this->assertDatabaseHas('posts', ['title' => 'Hanya Dua Kolom', 'category' => 'Umum', 'is_published' => false]);
    }

    public function test_import_with_header_only_creates_nothing(): void
    {
        $file = $this->makeXlsx([['Judul', 'Isi']]);

        $this->actingAs($this->member)->post('/dashboard/posts/import', ['file' => $file])
            ->assertSessionHas('success', fn ($m) => str_contains($m, '0 artikel berhasil diimpor'));

        $this->assertDatabaseCount('posts', 0);
    }

    public function test_import_rejects_wrong_format_and_unreadable_files(): void
    {
        $this->actingAs($this->member);

        $this->post('/dashboard/posts/import', ['file' => $this->makeXlsx([['Foo', 'Bar'], ['a', 'b']])])
            ->assertSessionHasErrors('file');

        $this->post('/dashboard/posts/import', ['file' => UploadedFile::fake()->createWithContent('rusak.xlsx', 'bukan excel')])
            ->assertSessionHasErrors('file');

        $this->post('/dashboard/posts/import', ['file' => UploadedFile::fake()->create('data.csv', 10)])
            ->assertSessionHasErrors('file');

        $this->post('/dashboard/posts/import', [])->assertSessionHasErrors('file');

        $this->assertDatabaseCount('posts', 0);
    }
}
