<?php

/*
 * This file was created by developers working at BitBag
 * Do you need more information about us and what we do? Visit our https://bitbag.io website!
 * We are hiring developers from all over the world. Join us and start your new, exciting adventure and become part of us: https://bitbag.io/career
*/

declare(strict_types=1);

namespace BitBag\SyliusByrdShippingExportPlugin\Controller;

use BitBag\SyliusByrdShippingExportPlugin\Api\Client\ByrdHttpClientInterface;
use BitBag\SyliusShippingExportPlugin\Entity\ShippingGatewayInterface;
use BitBag\SyliusShippingExportPlugin\Repository\ShippingGatewayRepositoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class FilterByrdProducts
{
    /** @var ByrdHttpClientInterface */
    private $byrdHttpClient;

    /** @var ShippingGatewayRepositoryInterface */
    private $shippingGatewayRepository;

    public function __construct(
        ByrdHttpClientInterface $byrdHttpClient,
        ShippingGatewayRepositoryInterface $shippingGatewayRepository
    ) {
        $this->byrdHttpClient = $byrdHttpClient;
        $this->shippingGatewayRepository = $shippingGatewayRepository;
    }

    public function __invoke(Request $request): Response
    {
        /** @var ShippingGatewayInterface|null $gateway */
        $gateway = $this->shippingGatewayRepository->findOneByCode('byrd');
        if ($gateway === null) {
            return new Response('[]');
        }

        $products = $this->byrdHttpClient->filterProductsBySku((string) $request->query->get('sku'), $gateway);

        return new JsonResponse($products);
    }
}
