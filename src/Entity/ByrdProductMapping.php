<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
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
