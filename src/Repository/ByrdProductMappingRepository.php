<?php

/*
 * This file was created by developers working at BitBag
 * Do you need more information about us and what we do? Visit our https://bitbag.io website!
 * We are hiring developers from all over the world. Join us and start your new, exciting adventure and become part of us: https://bitbag.io/career
*/

declare(strict_types=1);

namespace BitBag\SyliusByrdShippingExportPlugin\Repository;

use BitBag\SyliusByrdShippingExportPlugin\Entity\ByrdProductMapping;
use BitBag\SyliusByrdShippingExportPlugin\Entity\ByrdProductMappingInterface;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Core\Model\ProductInterface;

final class ByrdProductMappingRepository extends EntityRepository implements ByrdProductMappingRepositoryInterface
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, $em->getClassMetadata(ByrdProductMapping::class));
    }

    public function findForProduct(ProductInterface $product): ?ByrdProductMappingInterface
    {
        /** @var ByrdProductMapping|null $byrdProductMapping */
        $byrdProductMapping = $this->findOneBy(['product' => $product]);

        return $byrdProductMapping;
    }
}
