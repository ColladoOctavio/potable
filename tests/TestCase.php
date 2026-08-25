<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $app = parent::createApplication();

        if ($app->environment('testing')) {
            $app['config']->set([
                'database.default' => 'sqlite',
                'database.connections.sqlite.database' => ':memory:',
                'tenancy.database.central_connection' => 'sqlite',
                'cache.default' => 'array',
                'session.driver' => 'array',
                'queue.default' => 'sync',
            ]);
        }

        return $app;
    }
}
