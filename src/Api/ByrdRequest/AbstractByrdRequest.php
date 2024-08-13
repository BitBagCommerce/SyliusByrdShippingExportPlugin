<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
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
        string $apiUrl,
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
        ?array $parameters = null,
    ): array {
        $request = [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ];

        if (null !== $parameters && 0 < count($parameters)) {
            $request['body'] = json_encode($parameters);
        }

        return $request;
    }

    protected function createUrl(string $suffix): string
    {
        return $this->apiUrl . $suffix;
    }
}
