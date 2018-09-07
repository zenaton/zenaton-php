<?php

namespace Zenaton\Test;

use PHPUnit\Framework\TestCase;
use Zenaton\Client;

class ClientInvolvedTestCase extends TestCase
{
    public static function setUpBeforeClass()
    {
        // Make sure Client singleton instance is destroyed before running any of those tests
        static::destroyClientSingleton();

        parent::setUpBeforeClass();
    }

    public function setUp()
    {
        parent::setUp();

        Client::init('FakeAppId', 'FakeApiToken', 'FakeAppEnv');
    }

    public function tearDown()
    {
        // Make sure Client singleton instance is destroyed between tests
        static::destroyClientSingleton();

        parent::tearDown();
    }

    /**
     * Destroy the current Client singleton instance.
     */
    protected static function destroyClientSingleton()
    {
        $terminator = (static function () {
            static::$instance = null;
        })->bindTo(null, Client::class);

        $terminator();
    }
}
