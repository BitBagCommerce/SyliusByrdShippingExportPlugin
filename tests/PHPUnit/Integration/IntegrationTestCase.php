<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace Tests\BitBag\SyliusByrdShippingExportPlugin\Integration;

use ApiTestCase\JsonApiTestCase;

abstract class IntegrationTestCase extends JsonApiTestCase
{
    public function __construct(?string $name = null, array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->dataFixturesPath = __DIR__ . '/DataFixtures/ORM';
    }

    protected function setUp(): void
    {
    }

    protected function tearDown(): void
    {
    }
}
