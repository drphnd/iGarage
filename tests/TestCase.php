<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ignore @vite in Blade during tests, so npm run dev / npm run build is not needed
        $this->withoutVite();
    }
}