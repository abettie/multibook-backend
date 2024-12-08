<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected static bool $migrated = false;

    public static function setUpBeforeClass(): void
    {
        if (!is_file('.env.testing')) {
            echo "Pleace create .env.testing for connecting the test DB.\n";
            echo "Test aborted.\n";
            exit;
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        if (strpos(env('DB_DATABASE'), 'test') === false) {
            echo "DB name must include 'test'.\n";
            echo "Test aborted.\n";
            exit;
        }

        if (!self::$migrated) {
            $this->artisan('config:clear');
            $this->artisan('cache:clear');
            $this->artisan('migrate:fresh');
            $this->artisan('db:seed');
            self::$migrated = true;
        }
    }
}
