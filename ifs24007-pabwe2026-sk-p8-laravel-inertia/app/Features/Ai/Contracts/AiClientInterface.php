<?php

namespace App\Features\Ai\Contracts;

use App\Features\Ai\DTO\GeneratedPostItem;

interface AiClientInterface
{
    /**
     * Menghasilkan draf artikel berdasarkan topik.
     *
     * @return list<GeneratedPostItem>
     *
     * @throws \App\Features\Ai\Exceptions\AiGenerationException
     */
    public function generatePosts(string $topic, int $count): array;
}
