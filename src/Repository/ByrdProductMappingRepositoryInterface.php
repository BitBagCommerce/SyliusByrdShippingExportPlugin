<?php

/*
 * This file was created by developers working at BitBag
 * Do you need more information about us and what we do? Visit our https://bitbag.io website!
 * We are hiring developers from all over the world. Join us and start your new, exciting adventure and become part of us: https://bitbag.io/career
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
