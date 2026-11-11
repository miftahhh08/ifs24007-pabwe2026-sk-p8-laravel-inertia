<?php

namespace App\Features\Post\Services;

use App\Features\Post\Enums\PostCategory;
use App\Models\User;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Ekspor dan impor artikel ke/dari berkas Excel (.xlsx) memakai PhpSpreadsheet.
 */
class PostExcelService
{
    private const HEADERS = ['No', 'Judul', 'Kategori', 'Ringkasan', 'Isi', 'Status', 'Tanggal Publikasi', 'Dibuat'];

    public function __construct(private readonly PostService $posts)
    {
    }

    /**
     * @param  iterable<\App\Features\Post\Models\Post>  $posts
     */
    public function download(iterable $posts, string $filename, bool $withAuthor = false): StreamedResponse
    {
        $spreadsheet = $this->build($posts, $withAuthor);

        return response()->streamDownload(function () use ($spreadsheet) {
            (new XlsxWriter($spreadsheet))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Membaca berkas Excel lalu menyimpan setiap baris sebagai artikel milik $user.
     *
     * @return array{created:int, skipped:int, errors:list<string>}
     *
     * @throws InvalidArgumentException jika berkas tidak bisa dibaca / format salah
     */
    public function import(User $user, string $path): array
    {
        try {
            $reader = new XlsxReader();
            $reader->setReadDataOnly(true);
            $rows = $reader->load($path)->getActiveSheet()->toArray(null, true, true, false);
        } catch (Throwable $e) {
            throw new InvalidArgumentException('Berkas Excel tidak dapat dibaca. Gunakan format .xlsx yang valid.');
        }

        $header = array_map(
            fn ($h) => mb_strtolower(trim((string) $h)),
            array_shift($rows) ?? [],
        );
        $index = array_flip($header);

        if (! isset($index['judul'], $index['isi'])) {
            throw new InvalidArgumentException('Format berkas tidak sesuai. Kolom "Judul" dan "Isi" wajib ada.');
        }

        $created = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $i => $row) {
            $line = $i + 2; // baris 1 = header
            $title = $this->cell($row, $index, 'judul');
            $content = $this->cell($row, $index, 'isi');

            if ($title === '' && $content === '') {
                continue; // baris kosong diabaikan
            }

            if ($title === '' || $content === '' || mb_strlen($title) > 255) {
                $skipped++;
                $errors[] = "Baris {$line}: judul (maks. 255 karakter) dan isi wajib diisi.";

                continue;
            }

            $excerpt = mb_substr($this->cell($row, $index, 'ringkasan'), 0, 500);

            $this->posts->create($user, [
                'title' => $title,
                'category' => PostCategory::fromLabel($this->cell($row, $index, 'kategori'))->value,
                'excerpt' => $excerpt !== '' ? $excerpt : null,
                'content' => $content,
                'is_published' => mb_strtolower($this->cell($row, $index, 'status')) === 'terpublikasi',
            ]);
            $created++;
        }

        return ['created' => $created, 'skipped' => $skipped, 'errors' => $errors];
    }

    /**
     * @param  iterable<\App\Features\Post\Models\Post>  $posts
     */
    public function build(iterable $posts, bool $withAuthor): Spreadsheet
    {
        $headers = self::HEADERS;

        if ($withAuthor) {
            $headers[] = 'Penulis';
            $headers[] = 'Username';
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Artikel');

        foreach ($headers as $col => $text) {
            $sheet->setCellValueExplicit(Coordinate::stringFromColumnIndex($col + 1).'1', $text, DataType::TYPE_STRING);
        }

        $lastColumn = Coordinate::stringFromColumnIndex(count($headers));
        $sheet->getStyle("A1:{$lastColumn}1")->getFont()->setBold(true);

        $row = 2;

        foreach ($posts as $number => $post) {
            $values = [
                $number + 1,
                $post->title,
                $post->category->value,
                $post->excerpt ?? '',
                $post->content,
                $post->is_published ? 'Terpublikasi' : 'Draf',
                $post->published_at?->format('Y-m-d H:i') ?? '',
                $post->created_at->format('Y-m-d H:i'),
            ];

            if ($withAuthor) {
                $values[] = $post->user->name;
                $values[] = $post->user->username;
            }

            foreach ($values as $col => $value) {
                // Selalu simpan sebagai teks agar isi yang diawali "=" tidak dianggap rumus.
                $sheet->setCellValueExplicit(
                    Coordinate::stringFromColumnIndex($col + 1).$row,
                    (string) $value,
                    DataType::TYPE_STRING,
                );
            }

            $row++;
        }

        return $spreadsheet;
    }

    private function cell(array $row, array $index, string $key): string
    {
        return isset($index[$key]) ? trim((string) ($row[$index[$key]] ?? '')) : '';
    }
}
