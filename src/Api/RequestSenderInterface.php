<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\SyliusByrdShippingExportPlugin\Api;

use BitBag\SyliusByrdShippingExportPlugin\Api\ByrdRequest\AbstractByrdRequestInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

interface RequestSenderInterface
{
    public function send(AbstractByrdRequestInterface $byrdRequest): ResponseInterface;

    public function sendAuthorized(AbstractByrdRequestInterface $byrdRequest, string $token): ResponseInterface;
}
