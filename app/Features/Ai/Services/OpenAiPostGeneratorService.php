<?php

namespace App\Features\Ai\Services;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\DTO\GeneratedPostItem;
use App\Features\Ai\Exceptions\AiGenerationException;
use App\Features\Post\Enums\PostCategory;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Klien AI berbasis API "chat completions" (format OpenAI, juga dipakai banyak
 * layanan kompatibel). Alamat, model, dan kunci diatur di config/openai.php.
 *
 * Cara kerja: setiap artikel diminta lewat SATU request sendiri, lalu seluruh
 * request dijalankan PARALEL. Hasilnya lebih cepat dan tiap request lebih pendek,
 * sehingga tidak mudah terkena timeout. Bila sebagian request gagal tetapi
 * sebagian berhasil, artikel yang berhasil tetap dikembalikan.
 */
class OpenAiPostGeneratorService implements AiClientInterface
{
    public function generatePosts(string $topic, int $count): array
    {
        $apiKey = config('openai.api_key');

        if (blank($apiKey)) {
            throw new AiGenerationException('API key AI belum diisi di file .env (OPENAI_API_KEY).');
        }

        $baseUrl = trim((string) config('openai.base_url'));

        if (! preg_match('#^https?://[^\s/]+#i', $baseUrl)) {
            throw new AiGenerationException(
                "OPENAI_BASE_URL tidak valid (nilai sekarang: \"{$baseUrl}\"). "
                .'Isi dengan alamat lengkap yang diawali https://, contoh: https://api.openai.com/v1'
            );
        }

        $timeout = (int) config('openai.timeout');

        // Server bawaan PHP di Windows menghitung waktu nyata (bukan waktu CPU),
        // sehingga batas bawaan 30 detik bisa terlampaui saat menunggu AI.
        set_time_limit($timeout + 15);

        $url = rtrim($baseUrl, '/').'/chat/completions';

        $responses = Http::pool(function (Pool $pool) use ($apiKey, $timeout, $url, $topic, $count) {
            $requests = [];

            foreach (range(1, $count) as $number) {
                $requests[] = $pool->as((string) $number)
                    ->withToken($apiKey)
                    ->acceptJson()
                    ->timeout($timeout)
                    ->post($url, [
                        'model' => config('openai.model'),
                        'temperature' => 0.8,
                        'messages' => [
                            ['role' => 'system', 'content' => $this->systemPrompt()],
                            ['role' => 'user', 'content' => $this->userPrompt($topic, $number, $count)],
                        ],
                    ]);
            }

            return $requests;
        });

        $items = [];
        $firstError = null;

        foreach ($responses as $response) {
            try {
                $item = $this->readResponse($response, $timeout);
            } catch (AiGenerationException $e) {
                $firstError ??= $e;

                continue;
            }

            if ($item !== null) {
                $items[] = $item;
            }
        }

        if ($items === []) {
            throw $firstError ?? new AiGenerationException('Layanan AI tidak mengembalikan artikel yang valid. Coba ulangi.');
        }

        return $items;
    }

    /**
     * Mengubah satu jawaban menjadi satu artikel (atau null bila isinya tidak layak).
     *
     * @throws AiGenerationException bila koneksi gagal atau layanan AI menolak.
     */
    private function readResponse(Response|ConnectionException $response, int $timeout): ?GeneratedPostItem
    {
        if ($response instanceof ConnectionException) {
            throw new AiGenerationException($this->connectionMessage($response, $timeout), 0, $response);
        }

        if ($response->failed()) {
            throw new AiGenerationException($this->httpMessage($response));
        }

        $items = $this->parse((string) data_get($response->json(), 'choices.0.message.content', ''), 1);

        return $items[0] ?? null;
    }

    /**
     * Pesan yang menjelaskan PENYEBAB gagal koneksi. Detail teknis dari cURL
     * SELALU ikut ditampilkan agar penyebab pasti bisa dibaca langsung di layar.
     */
    private function connectionMessage(ConnectionException $e, int $timeout): string
    {
        $text = $e->getMessage();
        $code = preg_match('/cURL error (\d+)/', $text, $m) ? (int) $m[1] : null;

        $cause = match (true) {
            $code === 28, stripos($text, 'timed out') !== false => "Layanan AI terlalu lama merespons (batas {$timeout} detik). Coba kurangi jumlah artikel atau naikkan OPENAI_TIMEOUT di .env.",
            $code === 60, $code === 77, stripos($text, 'SSL certificate') !== false => 'PHP tidak mengenali sertifikat SSL (umum di Windows). Unduh cacert.pem, isi curl.cainfo dan openssl.cafile di php.ini, lalu restart server.',
            $code === 35 => 'Jabat tangan SSL/TLS gagal. Coba matikan VPN, proxy, atau antivirus yang memeriksa HTTPS, lalu pastikan cacert.pem sudah diatur di php.ini.',
            $code === 6 => 'Alamat layanan AI tidak ditemukan. Periksa OPENAI_BASE_URL dan koneksi internet.',
            $code === 7 => 'Koneksi ke layanan AI ditolak. Periksa OPENAI_BASE_URL, firewall, atau proxy.',
            $code === 52, $code === 56 => 'Koneksi terputus saat menunggu jawaban AI. Periksa kestabilan internet, firewall, atau VPN.',
            default => 'Tidak dapat terhubung ke layanan AI. Periksa koneksi internet dan OPENAI_BASE_URL.',
        };

        // Buang tautan dokumentasi cURL agar detail lebih ringkas.
        $detail = trim(preg_replace('/\s*\(see https?:\/\/\S+\)/', '', $text) ?? $text);

        return $cause.' Detail: '.Str::limit($detail, 220);
    }

    /** Pesan untuk balasan error HTTP, disertai keterangan dari penyedia bila ada. */
    private function httpMessage(Response $response): string
    {
        $status = $response->status();

        $message = match ($status) {
            401, 403 => "API key ditolak (HTTP {$status}). Periksa OPENAI_API_KEY.",
            404 => 'Alamat atau model tidak ditemukan (HTTP 404). Periksa OPENAI_BASE_URL dan OPENAI_MODEL.',
            429 => 'Batas permintaan atau kuota layanan AI tercapai (HTTP 429). Tunggu sebentar atau periksa kuota akun.',
            default => "Layanan AI menolak permintaan (HTTP {$status}). Periksa API key, base URL, dan nama model.",
        };

        $detail = data_get($response->json(), 'error.message');

        return is_string($detail) && $detail !== ''
            ? $message.' Keterangan: '.Str::limit($detail, 150)
            : $message;
    }

    /** @return list<GeneratedPostItem> */
    private function parse(string $text, int $count): array
    {
        $text = trim($text);
        // Buang pagar kode markdown bila model membungkus JSON dengan ```json ... ```
        $text = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $text) ?? $text;

        $decoded = json_decode($text, true);

        if (! is_array($decoded)) {
            // Cadangan: ambil array JSON pertama di dalam teks bebas.
            $start = strpos($text, '[');
            $end = strrpos($text, ']');

            if ($start !== false && $end !== false && $end > $start) {
                $decoded = json_decode(substr($text, $start, $end - $start + 1), true);
            }
        }

        if (! is_array($decoded)) {
            return [];
        }

        if (isset($decoded['articles']) && is_array($decoded['articles'])) {
            $decoded = $decoded['articles'];
        } elseif (isset($decoded['title'])) {
            $decoded = [$decoded]; // satu artikel saja
        }

        $items = [];

        foreach ($decoded as $row) {
            $item = is_array($row) ? GeneratedPostItem::fromArray($row) : null;

            if ($item === null) {
                continue;
            }

            $items[] = $item;

            if (count($items) >= $count) {
                break;
            }
        }

        return $items;
    }

    private function systemPrompt(): string
    {
        return 'Anda adalah penulis blog profesional berbahasa Indonesia. '
            .'Balas HANYA dengan JSON valid tanpa teks lain.';
    }

    private function userPrompt(string $topic, int $number, int $count): string
    {
        $categories = implode(', ', PostCategory::values());
        $angle = $count > 1
            ? "Ini artikel ke-{$number} dari {$count}; pilih sudut pandang yang berbeda dari artikel lainnya.\n"
            : '';

        return "Buat 1 artikel blog tentang topik: \"{$topic}\".\n{$angle}"
            ."Balas HANYA dengan JSON array berisi satu objek dengan kunci:\n"
            ."- title: judul menarik\n"
            ."- category: salah satu dari [{$categories}]\n"
            ."- excerpt: ringkasan 1-2 kalimat\n"
            .'- content: isi artikel lengkap dalam format Markdown (minimal 3 paragraf, gunakan sub-judul ##)';
    }
}
