<?php

use Laravel\Lumen\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Creates the application.
     *
     * @return \Laravel\Lumen\Application
     */
    public function createApplication()
    {
        return require __DIR__.'/../bootstrap/app.php';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseUrl = config('app.url');
    }

    protected function headers($headers = [])
    {
        return array_merge($headers, ['Client-Token' => '598eec18374b5']);
    }
}
