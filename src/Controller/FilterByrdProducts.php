<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
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
        ShippingGatewayRepositoryInterface $shippingGatewayRepository,
    ) {
        $this->byrdHttpClient = $byrdHttpClient;
        $this->shippingGatewayRepository = $shippingGatewayRepository;
    }

    public function __invoke(Request $request): Response
    {
        /** @var ShippingGatewayInterface|null $gateway */
        $gateway = $this->shippingGatewayRepository->findOneByCode('byrd');
        if (null === $gateway) {
            return new Response('[]');
        }

        $products = $this->byrdHttpClient->filterProductsBySku((string) $request->query->get('sku'), $gateway);

        return new JsonResponse($products);
    }
}
