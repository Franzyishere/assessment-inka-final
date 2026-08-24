<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = parent::createApplication();

        if (
            ! $app->environment('testing')
            || config('database.default') !== 'sqlite'
            || config('database.connections.sqlite.database') !== ':memory:'
        ) {
            throw new \RuntimeException(
                'Test dihentikan: koneksi wajib menggunakan SQLite :memory: pada environment testing.'
            );
        }

        return $app;
    }
}
