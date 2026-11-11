<?php

namespace App\Features\Ai\Services;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\DTO\GeneratedPostItem;
use App\Features\Ai\Exceptions\AiGenerationException;
use App\Features\Post\Enums\PostCategory;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Klien AI berbasis API "chat completions" (format OpenAI, juga dipakai banyak
 * layanan kompatibel). Alamat, model, dan kunci diatur di config/openai.php.
 */
class OpenAiPostGeneratorService implements AiClientInterface
{
    public function generatePosts(string $topic, int $count): array
    {
        $apiKey = config('openai.api_key');

        if (blank($apiKey)) {
            throw new AiGenerationException('API key AI belum diisi di file .env (OPENAI_API_KEY).');
        }

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->timeout((int) config('openai.timeout'))
                ->post(rtrim((string) config('openai.base_url'), '/').'/chat/completions', [
                    'model' => config('openai.model'),
                    'temperature' => 0.7,
                    'messages' => [
                        ['role' => 'system', 'content' => $this->systemPrompt()],
                        ['role' => 'user', 'content' => $this->userPrompt($topic, $count)],
                    ],
                ]);
        } catch (ConnectionException $e) {
            throw new AiGenerationException(
                'Tidak dapat terhubung ke layanan AI. Periksa koneksi internet dan OPENAI_BASE_URL.',
                0,
                $e,
            );
        }

        if ($response->failed()) {
            throw new AiGenerationException(
                'Layanan AI menolak permintaan (HTTP '.$response->status().'). Periksa API key, base URL, dan nama model.'
            );
        }

        $items = $this->parse((string) data_get($response->json(), 'choices.0.message.content', ''), $count);

        if ($items === []) {
            throw new AiGenerationException('Layanan AI tidak mengembalikan artikel yang valid. Coba ulangi.');
        }

        return $items;
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

    private function userPrompt(string $topic, int $count): string
    {
        $categories = implode(', ', PostCategory::values());

        return "Buat {$count} artikel blog berbeda tentang topik: \"{$topic}\".\n"
            ."Balas HANYA dengan JSON array. Setiap elemen berupa objek dengan kunci:\n"
            ."- title: judul menarik\n"
            ."- category: salah satu dari [{$categories}]\n"
            ."- excerpt: ringkasan 1-2 kalimat\n"
            .'- content: isi artikel lengkap dalam format Markdown (minimal 3 paragraf, gunakan sub-judul ##)';
    }
}
