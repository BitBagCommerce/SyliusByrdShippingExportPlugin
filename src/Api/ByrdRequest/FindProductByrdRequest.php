<?php

/*
 * This file was created by developers working at BitBag
 * Do you need more information about us and what we do? Visit our https://bitbag.io website!
 * We are hiring developers from all over the world. Join us and start your new, exciting adventure and become part of us: https://bitbag.io/career
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
