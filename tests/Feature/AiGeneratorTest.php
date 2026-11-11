<?php

namespace Tests\Feature;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\DTO\GeneratedPostItem;
use App\Features\Ai\Exceptions\AiGenerationException;
use App\Features\Ai\Services\OpenAiPostGeneratorService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AiGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private function fakeClient(array|\Throwable $result): void
    {
        $this->app->instance(AiClientInterface::class, new class($result) implements AiClientInterface {
            public function __construct(private array|\Throwable $result)
            {
            }

            public function generatePosts(string $topic, int $count): array
            {
                if ($this->result instanceof \Throwable) {
                    throw $this->result;
                }

                return $this->result;
            }
        });
    }

    private function fakeAiResponse(string $content, int $status = 200): void
    {
        config([
            'openai.api_key' => 'kunci-uji',
            'openai.base_url' => 'https://ai.test/v1/',
            'openai.model' => 'model-uji',
        ]);

        // Http::fake() bersifat akumulatif (stub pertama menang), jadi mulai dari pabrik HTTP baru.
        Http::swap(new HttpFactory());
        Http::fake(['ai.test/*' => Http::response(['choices' => [['message' => ['content' => $content]]]], $status)]);
    }

    private function item(string $title = 'Judul AI'): array
    {
        return ['title' => $title, 'category' => 'Teknologi', 'excerpt' => 'Ringkas', 'content' => 'Isi lengkap'];
    }

    // ---------- Endpoint ----------

    public function test_ai_generate_flashes_drafts(): void
    {
        $this->fakeClient([new GeneratedPostItem('Judul AI', 'Teknologi', 'Ringkas', 'Isi lengkap')]);

        $this->actingAs(User::factory()->create())->from('/dashboard/posts')
            ->post('/dashboard/posts/ai-generate', ['topic' => 'laravel', 'count' => 2])
            ->assertRedirect('/dashboard/posts')
            ->assertSessionHas('ai_drafts', [$this->item()]);
    }

    public function test_ai_generate_validates_input(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post('/dashboard/posts/ai-generate', [])->assertSessionHasErrors(['topic', 'count']);
        $this->post('/dashboard/posts/ai-generate', ['topic' => 'laravel', 'count' => 0])->assertSessionHasErrors('count');
        $this->post('/dashboard/posts/ai-generate', ['topic' => 'laravel', 'count' => 6])->assertSessionHasErrors('count');
        $this->post('/dashboard/posts/ai-generate', ['topic' => 'laravel', 'count' => 'abc'])->assertSessionHasErrors('count');
    }

    public function test_ai_generate_reports_ai_failure_as_validation_error(): void
    {
        $this->fakeClient(new AiGenerationException('Layanan AI gagal.'));

        $this->actingAs(User::factory()->create())
            ->post('/dashboard/posts/ai-generate', ['topic' => 'laravel', 'count' => 1])
            ->assertSessionHasErrors(['topic' => 'Layanan AI gagal.']);
    }

    public function test_ai_save_stores_selected_drafts(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/dashboard/posts/ai-save', [
            'drafts' => [$this->item('AI Satu'), $this->item('AI Dua')],
        ])->assertRedirect(route('dashboard.posts.index'))->assertSessionHas('success');

        $this->assertDatabaseHas('posts', ['title' => 'AI Satu', 'user_id' => $user->id, 'is_published' => false]);
        $this->assertDatabaseHas('posts', ['title' => 'AI Dua', 'user_id' => $user->id, 'is_published' => false]);
    }

    public function test_ai_save_validates_input(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post('/dashboard/posts/ai-save', [])->assertSessionHasErrors('drafts');
        $this->post('/dashboard/posts/ai-save', ['drafts' => [['title' => 'X', 'category' => 'Ngawur', 'content' => 'Y']]])
            ->assertSessionHasErrors('drafts.0.category');
    }

    // ---------- Service ----------

    public function test_container_resolves_openai_service(): void
    {
        $this->assertInstanceOf(OpenAiPostGeneratorService::class, app(AiClientInterface::class));
    }

    public function test_service_requires_api_key(): void
    {
        config(['openai.api_key' => null]);

        $this->expectException(AiGenerationException::class);
        $this->expectExceptionMessage('API key');

        app(AiClientInterface::class)->generatePosts('topik', 1);
    }

    /** @return array<string, array{0:?string}> */
    public static function invalidBaseUrls(): array
    {
        return [
            'hanya nama' => ['delcom'],
            'tanpa skema' => ['api.openai.com/v1'],
            'kosong' => [''],
            'null' => [null],
        ];
    }

    #[DataProvider('invalidBaseUrls')]
    public function test_service_rejects_invalid_base_url_with_clear_message(?string $baseUrl): void
    {
        config(['openai.api_key' => 'kunci-uji', 'openai.base_url' => $baseUrl]);
        Http::fake();

        try {
            app(AiClientInterface::class)->generatePosts('x', 1);
            $this->fail('Seharusnya melempar AiGenerationException.');
        } catch (AiGenerationException $e) {
            $this->assertStringContainsString('OPENAI_BASE_URL tidak valid', $e->getMessage());
            $this->assertStringContainsString('https://', $e->getMessage());
            Http::assertNothingSent();
        }
    }

    public function test_service_parses_plain_json_array_and_sends_correct_request(): void
    {
        $this->fakeAiResponse(json_encode([$this->item('Satu'), $this->item('Dua'), $this->item('Tiga')]));

        $items = app(AiClientInterface::class)->generatePosts('laravel', 2);

        $this->assertCount(2, $items); // dibatasi sesuai jumlah diminta
        $this->assertSame('Satu', $items[0]->title);

        Http::assertSent(fn (Request $r) => $r->url() === 'https://ai.test/v1/chat/completions'
            && $r->hasHeader('Authorization', 'Bearer kunci-uji')
            && $r['model'] === 'model-uji');
    }

    public function test_service_handles_fenced_wrapped_and_object_responses(): void
    {
        $json = json_encode([$this->item('Satu')]);
        $service = app(AiClientInterface::class);

        $this->fakeAiResponse("```json\n{$json}\n```");
        $this->assertSame('Satu', $service->generatePosts('x', 1)[0]->title);

        $this->fakeAiResponse("Berikut hasilnya: {$json} semoga membantu.");
        $this->assertSame('Satu', $service->generatePosts('x', 1)[0]->title);

        $this->fakeAiResponse(json_encode(['articles' => [$this->item('Dari objek'), $this->item('Lain')]]));
        $this->assertSame('Dari objek', $service->generatePosts('x', 1)[0]->title);

        $this->fakeAiResponse(json_encode($this->item('Tunggal')));
        $this->assertSame('Tunggal', $service->generatePosts('x', 1)[0]->title);
    }

    public function test_service_skips_invalid_items(): void
    {
        $this->fakeAiResponse(json_encode([
            'bukan array',
            ['title' => 'Tanpa isi', 'content' => ''],
            $this->item('Valid'),
        ]));

        $items = app(AiClientInterface::class)->generatePosts('x', 1);

        $this->assertCount(1, $items);
        $this->assertSame('Valid', $items[0]->title);
    }

    public function test_service_sends_one_parallel_request_per_article(): void
    {
        $this->fakeAiResponse(json_encode([$this->item('Satu')]));

        $items = app(AiClientInterface::class)->generatePosts('laravel', 3);

        $this->assertCount(3, $items);
        Http::assertSentCount(3);
        Http::assertSent(fn (Request $r) => str_contains($r['messages'][1]['content'], 'artikel ke-2 dari 3'));
    }

    public function test_service_keeps_successful_articles_when_some_requests_fail(): void
    {
        config(['openai.api_key' => 'kunci-uji', 'openai.base_url' => 'https://ai.test/v1']);
        Http::swap(new HttpFactory());
        Http::fake(['ai.test/*' => Http::sequence()
            ->push(['choices' => [['message' => ['content' => json_encode([$this->item('Berhasil')])]]]])
            ->pushStatus(500)]);

        $items = app(AiClientInterface::class)->generatePosts('x', 2);

        $this->assertCount(1, $items);
        $this->assertSame('Berhasil', $items[0]->title);
    }

    public function test_service_fails_when_no_valid_article_returned(): void
    {
        $service = app(AiClientInterface::class);

        foreach (['tidak ada json sama sekali', '[ukuran besar]', '"hanya teks"', '{"foo":"bar"}', ''] as $content) {
            $this->fakeAiResponse($content);

            try {
                $service->generatePosts('x', 1);
                $this->fail('Seharusnya melempar AiGenerationException untuk: '.$content);
            } catch (AiGenerationException $e) {
                $this->assertStringContainsString('tidak mengembalikan artikel yang valid', $e->getMessage());
            }
        }
    }

    public function test_service_fails_on_http_error(): void
    {
        $this->fakeAiResponse('apa saja', 500);

        $this->expectException(AiGenerationException::class);
        $this->expectExceptionMessage('HTTP 500');

        app(AiClientInterface::class)->generatePosts('x', 1);
    }

    public function test_service_fails_on_connection_error(): void
    {
        config(['openai.api_key' => 'kunci-uji', 'openai.base_url' => 'https://ai.test/v1']);
        Http::fake(function () {
            throw new ConnectionException('boom');
        });

        $this->expectException(AiGenerationException::class);
        $this->expectExceptionMessage('Tidak dapat terhubung');

        app(AiClientInterface::class)->generatePosts('x', 1);
    }

    /** @return array<string, array{0:string, 1:string}> */
    public static function connectionFailures(): array
    {
        return [
            'timeout' => ['cURL error 28: Operation timed out after 60001 milliseconds', 'terlalu lama merespons'],
            'timeout tanpa kode' => ['request timed out', 'terlalu lama merespons'],
            'sertifikat ssl' => ['cURL error 60: SSL certificate problem: unable to get local issuer certificate', 'sertifikat SSL'],
            'dengan tautan dokumentasi' => ['cURL error 99: gagal (see https://curl.se/libcurl/c/libcurl-errors.html) for https://ai.test', 'Tidak dapat terhubung'],
            'dns' => ['cURL error 6: Could not resolve host: ai.test', 'tidak ditemukan'],
            'ditolak' => ['cURL error 7: Failed to connect to ai.test port 443', 'ditolak'],
            'lainnya' => ['cURL error 35: sesuatu (see https://curl.haxx.se/libcurl/c/libcurl-errors.html)', 'Detail: cURL error 35: sesuatu'],
        ];
    }

    #[DataProvider('connectionFailures')]
    public function test_service_explains_connection_failure_cause(string $raw, string $expected): void
    {
        config(['openai.api_key' => 'kunci-uji', 'openai.base_url' => 'https://ai.test/v1']);
        Http::swap(new HttpFactory());
        Http::fake(function () use ($raw) {
            throw new ConnectionException($raw);
        });

        try {
            app(AiClientInterface::class)->generatePosts('x', 1);
            $this->fail('Seharusnya melempar AiGenerationException.');
        } catch (AiGenerationException $e) {
            $this->assertStringContainsString($expected, $e->getMessage());
            // Detail teknis asli cURL harus ikut tampil, tanpa tautan dokumentasi.
            $this->assertStringContainsString('Detail: '.preg_replace('/\s*\(see https?:\/\/\S+\)/', '', $raw), $e->getMessage());
            $this->assertStringNotContainsString('(see https', $e->getMessage());
            $this->assertInstanceOf(ConnectionException::class, $e->getPrevious());
        }
    }

    /** @return array<string, array{0:int, 1:array<string,mixed>, 2:string}> */
    public static function httpFailures(): array
    {
        return [
            '401' => [401, [], 'OPENAI_API_KEY'],
            '403' => [403, [], 'OPENAI_API_KEY'],
            '404' => [404, [], 'OPENAI_MODEL'],
            '429' => [429, [], 'kuota'],
            '500 dengan keterangan' => [500, ['error' => ['message' => 'model sedang sibuk']], 'Keterangan: model sedang sibuk'],
        ];
    }

    #[DataProvider('httpFailures')]
    public function test_service_explains_http_failure(int $status, array $body, string $expected): void
    {
        config(['openai.api_key' => 'kunci-uji', 'openai.base_url' => 'https://ai.test/v1']);
        Http::swap(new HttpFactory());
        Http::fake(['ai.test/*' => Http::response($body, $status)]);

        $this->expectException(AiGenerationException::class);
        $this->expectExceptionMessage($expected);

        app(AiClientInterface::class)->generatePosts('x', 1);
    }

    // ---------- Perintah ai:check ----------

    public function test_ai_check_reports_success(): void
    {
        $this->fakeAiResponse(json_encode([$this->item('Judul Uji')]));

        $this->artisan('ai:check')
            ->expectsOutputToContain('Model    : model-uji')
            ->expectsOutputToContain('BERHASIL')
            ->assertExitCode(0);
    }

    public function test_ai_check_reports_missing_key(): void
    {
        config(['openai.api_key' => null]);

        $this->artisan('ai:check')
            ->expectsOutputToContain('(KOSONG)')
            ->expectsOutputToContain('API key AI belum diisi')
            ->assertExitCode(1);
    }

    public function test_ai_check_shows_technical_detail_on_connection_failure(): void
    {
        config(['openai.api_key' => 'kunci-uji', 'openai.base_url' => 'https://ai.test/v1']);
        Http::swap(new HttpFactory());
        Http::fake(function () {
            throw new ConnectionException('cURL error 28: Operation timed out');
        });

        $this->artisan('ai:check')
            ->expectsOutputToContain('terlalu lama merespons')
            ->expectsOutputToContain('Detail teknis: cURL error 28')
            ->assertExitCode(1);
    }

    // ---------- DTO ----------

    public function test_generated_post_item_normalizes_data(): void
    {
        $this->assertNull(GeneratedPostItem::fromArray(['title' => ['bukan string'], 'content' => 'x']));
        $this->assertNull(GeneratedPostItem::fromArray(['title' => 'Judul', 'content' => '  ']));

        $item = GeneratedPostItem::fromArray([
            'title' => str_repeat('j', 300),
            'category' => 'ngawur',
            'excerpt' => 123,
            'content' => ' Isi ',
        ]);

        $this->assertSame(255, mb_strlen($item->title));
        $this->assertSame('Umum', $item->category);
        $this->assertSame('', $item->excerpt);
        $this->assertSame(['title' => $item->title, 'category' => 'Umum', 'excerpt' => '', 'content' => 'Isi'], $item->toArray());
    }
}
