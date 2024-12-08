<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected static bool $migrated = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (!self::$migrated) {
            $this->artisan('config:clear');
            $this->artisan('cache:clear');
            $this->artisan('migrate:fresh');
            $this->artisan('db:seed');
            self::$migrated = true;
        }
    }
}
        
