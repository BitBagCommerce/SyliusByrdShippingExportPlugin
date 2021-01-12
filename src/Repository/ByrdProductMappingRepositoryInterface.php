<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * another great project.
 * You can find more information about us on https://bitbag.io and write us
 * an email on mikolaj.krol@bitbag.pl.
 */

declare(strict_types=1);

namespace BitBag\SyliusByrdShippingExportPlugin\Repository;

use BitBag\SyliusByrdShippingExportPlugin\Entity\ByrdProductMappingInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

interface ByrdProductMappingRepositoryInterface extends RepositoryInterface
{
    public function findForProduct(ProductInterface $product): ?ByrdProductMappingInterface;
}
