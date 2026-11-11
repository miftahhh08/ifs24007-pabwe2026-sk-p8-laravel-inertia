<?php

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\Exceptions\AiGenerationException;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
| php artisan ai:check
| Menguji koneksi ke layanan AI dan menampilkan PENYEBAB bila gagal
| (timeout, sertifikat SSL, URL salah, API key ditolak, dst.).
*/
Artisan::command('ai:check', function (AiClientInterface $ai) {
    $key = (string) config('openai.api_key');

    $this->line('Base URL : '.config('openai.base_url'));
    $this->line('Model    : '.config('openai.model'));
    $this->line('Timeout  : '.config('openai.timeout').' detik');
    $this->line('API key  : '.($key === '' ? '(KOSONG)' : substr($key, 0, 3).'***'));
    $this->newLine();

    try {
        $items = $ai->generatePosts('uji koneksi', 1);
    } catch (AiGenerationException $e) {
        $this->error($e->getMessage());

        if ($e->getPrevious()) {
            $this->line('Detail teknis: '.$e->getPrevious()->getMessage());
        }

        return 1;
    }

    $this->info('BERHASIL. Contoh judul dari AI: '.$items[0]->title);

    return 0;
})->purpose('Uji koneksi ke layanan AI dan tampilkan penyebab bila gagal');
