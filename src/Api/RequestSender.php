<?php

/*
 * This file was created by developers working at BitBag
 * Do you need more information about us and what we do? Visit our https://bitbag.io website!
 * We are hiring developers from all over the world. Join us and start your new, exciting adventure and become part of us: https://bitbag.io/career
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
        HttpClientInterface $httpClient
    ) {
        $this->httpClient = $httpClient;
    }

    public function send(AbstractByrdRequestInterface $byrdRequest): ResponseInterface
    {
        $request = $byrdRequest->buildRequest(null);

        return $this->httpClient->request(
            $byrdRequest->getRequestMethod(),
            $byrdRequest->getRequestUrl(),
            $request
        );
    }

    public function sendAuthorized(AbstractByrdRequestInterface $byrdRequest, string $authorizationToken): ResponseInterface
    {
        $request = $byrdRequest->buildRequest($authorizationToken);

        return $this->httpClient->request(
            $byrdRequest->getRequestMethod(),
            $byrdRequest->getRequestUrl(),
            $this->addAuthorizationToken($request, $authorizationToken)
        );
    }

    protected function addAuthorizationToken(
        array $request,
        ?string $token = null
    ): array {
        if ($token !== null) {
            $request['headers']['Authorization'] = 'Bearer ' . $token;
        }

        return $request;
    }
}
