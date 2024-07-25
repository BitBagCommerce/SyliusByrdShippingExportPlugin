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
use Sylius\Component\Resource\Model\ResourceInterface;

interface ByrdProductMappingInterface extends ResourceInterface
{
    public function getId(): ?int;

    public function getProduct(): ?ProductInterface;

    public function setProduct(?ProductInterface $product): void;

    public function getByrdProductSku(): ?string;

    public function setByrdProductSku(?string $byrdProductSku): void;
}
