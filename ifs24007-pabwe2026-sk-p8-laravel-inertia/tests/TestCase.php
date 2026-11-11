<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Halaman Inertia dirender tanpa membutuhkan hasil `npm run build`.
        $this->withoutVite();
    }
}
