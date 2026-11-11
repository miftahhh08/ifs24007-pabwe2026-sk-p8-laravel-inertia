<?php

namespace App\Features\Ai\Exceptions;

use RuntimeException;

/** Kegagalan saat meminta artikel ke layanan AI (pesan aman ditampilkan ke pengguna). */
class AiGenerationException extends RuntimeException
{
}
