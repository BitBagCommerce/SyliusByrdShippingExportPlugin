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
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class RequestSender implements RequestSenderInterface
{
    /** @var HttpClientInterface */
    private $httpClient;

    public function __construct(
        HttpClientInterface $httpClient,
    ) {
        $this->httpClient = $httpClient;
    }

    public function send(AbstractByrdRequestInterface $byrdRequest): ResponseInterface
    {
        $request = $byrdRequest->buildRequest(null);

        return $this->httpClient->request(
            $byrdRequest->getRequestMethod(),
            $byrdRequest->getRequestUrl(),
            $request,
        );
    }

    public function sendAuthorized(AbstractByrdRequestInterface $byrdRequest, string $authorizationToken): ResponseInterface
    {
        $request = $byrdRequest->buildRequest($authorizationToken);

        return $this->httpClient->request(
            $byrdRequest->getRequestMethod(),
            $byrdRequest->getRequestUrl(),
            $this->addAuthorizationToken($request, $authorizationToken),
        );
    }

    protected function addAuthorizationToken(
        array $request,
        ?string $token = null,
    ): array {
        if (null !== $token) {
            $request['headers']['Authorization'] = 'Bearer ' . $token;
        }

        return $request;
    }
}
