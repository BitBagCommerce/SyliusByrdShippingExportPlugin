<?php

/*
 * This file was created by developers working at BitBag
 * Do you need more information about us and what we do? Visit our https://bitbag.io website!
 * We are hiring developers from all over the world. Join us and start your new, exciting adventure and become part of us: https://bitbag.io/career
*/

declare(strict_types=1);

namespace BitBag\SyliusByrdShippingExportPlugin\Api\ByrdRequest;

abstract class AbstractByrdRequest implements AbstractByrdRequestInterface
{
    /** @var string */
    protected $requestMethod;

    /** @var string */
    protected $requestUrl;

    /** @var string */
    private $apiUrl;

    public function __construct(
        string $apiUrl
    ) {
        $this->apiUrl = $apiUrl;

        $this->requestUrl = $this->createUrl($this->requestUrl);
    }

    abstract public function buildRequest(?string $authorizationToken): array;

    public function getRequestMethod(): string
    {
        return $this->requestMethod;
    }

    public function getRequestUrl(): string
    {
        return $this->requestUrl;
    }

    protected function buildRequestFromParams(
        ?array $parameters = null
    ): array {
        $request = [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]
        ];

        if ($parameters !== null && count($parameters) > 0) {
            $request['body'] = json_encode($parameters);
        }

        return $request;
    }

    protected function createUrl(string $suffix): string
    {
        return $this->apiUrl . $suffix;
    }
}
