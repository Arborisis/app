<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $cachePaths = array_merge(
            [dirname(__DIR__).'/bootstrap/cache/config.php'],
            glob(dirname(__DIR__).'/bootstrap/cache/routes-*.php') ?: [],
        );

        foreach ($cachePaths as $cachePath) {
            if (is_file($cachePath)) {
                unlink($cachePath);
            }
        }

        return parent::createApplication();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }
}
