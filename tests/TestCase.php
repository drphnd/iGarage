<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Abaikan @vite di Blade saat test, supaya tidak perlu npm run dev / npm run build
        $this->withoutVite();
    }
}