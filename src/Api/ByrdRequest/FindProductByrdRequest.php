<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\SyliusByrdShippingExportPlugin\Api\ByrdRequest;

use Symfony\Component\HttpFoundation\Request;

final class FindProductByrdRequest extends AbstractByrdRequest implements FindProductByrdRequestInterface
{
    /** @var string */
    protected $requestMethod = Request::METHOD_GET;

    /** @var string */
    protected $requestUrl = '/warehouse/products';

    /** @var string|null */
    private $byrdProductSku;

    /** @var string */
    private $searchField = 'q';

    public function setByrdProductSku(string $byrdProductSku): void
    {
        $this->byrdProductSku = $byrdProductSku;
    }

    public function setSearchField(string $searchField): void
    {
        $this->searchField = $searchField;
    }

    public function buildRequest(?string $authorizationToken): array
    {
        $this->requestUrl .= '?' . $this->searchField . '=' . $this->byrdProductSku;

        return $this->buildRequestFromParams([]);
    }
}
