<?php

return [

    /*
    | Kunci API dan alamat layanan AI. Isi lewat file .env (JANGAN ditulis di sini):
    |   OPENAI_API_KEY=...
    |   OPENAI_BASE_URL=https://api.openai.com/v1   (atau alamat layanan yang kompatibel)
    |   OPENAI_MODEL=gpt-4o-mini                     (atau nama model dari penyedia)
    */
    'api_key' => env('OPENAI_API_KEY'),

    'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),

    'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),

    // Detik, berlaku PER permintaan (tiap artikel = 1 permintaan, dijalankan paralel).
    'timeout' => (int) env('OPENAI_TIMEOUT', 60),

];
