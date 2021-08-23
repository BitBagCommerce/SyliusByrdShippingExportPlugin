<?php

/*
 * This file was created by developers working at BitBag
 * Do you need more information about us and what we do? Visit our https://bitbag.io website!
 * We are hiring developers from all over the world. Join us and start your new, exciting adventure and become part of us: https://bitbag.io/career
*/

declare(strict_types=1);

namespace BitBag\SyliusByrdShippingExportPlugin\Entity;

use Sylius\Component\Core\Model\ProductInterface;

class ByrdProductMapping implements ByrdProductMappingInterface
{
    /** @var int|null */
    protected $id;

    /** @var ProductInterface|null */
    protected $product;

    /** @var string|null */
    protected $byrdProductSku;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduct(): ?ProductInterface
    {
        return $this->product;
    }

    public function setProduct(?ProductInterface $product): void
    {
        $this->product = $product;
    }

    public function getByrdProductSku(): ?string
    {
        return $this->byrdProductSku;
    }

    public function setByrdProductSku(?string $byrdProductSku): void
    {
        $this->byrdProductSku = $byrdProductSku;
    }
}
