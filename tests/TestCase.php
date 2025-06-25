<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use \Tests\CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->withHeaders([
            'Accept' => 'application/json',
        ]);
    }
}
