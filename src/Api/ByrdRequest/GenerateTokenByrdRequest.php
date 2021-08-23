<?php

/*
 * This file was created by developers working at BitBag
 * Do you need more information about us and what we do? Visit our https://bitbag.io website!
 * We are hiring developers from all over the world. Join us and start your new, exciting adventure and become part of us: https://bitbag.io/career
*/

declare(strict_types=1);

namespace BitBag\SyliusByrdShippingExportPlugin\Api\ByrdRequest;

use BitBag\SyliusByrdShippingExportPlugin\Api\Exception\InvalidCredentialsException;
use Symfony\Component\HttpFoundation\Request;

final class GenerateTokenByrdRequest extends AbstractByrdRequest implements GenerateTokenByrdRequestInterface
{
    /** @var string */
    protected $requestMethod = Request::METHOD_POST;

    /** @var string */
    protected $requestUrl = '/login';

    /** @var string|null */
    private $username;

    /** @var string|null */
    private $password;

    public function setCredentials(string $username, string $password): void
    {
        $this->username = $username;
        $this->password = $password;
    }

    public function buildRequest(?string $authorizationToken): array
    {
        if ($this->username === null && $this->password === null) {
            throw new InvalidCredentialsException('You have to set up credentials via setCredentials(...) method');
        }

        $body = [
            'username' => $this->username,
            'password' => $this->password,
        ];

        return $this->buildRequestFromParams($body);
    }
}
