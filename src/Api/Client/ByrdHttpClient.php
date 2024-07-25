<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\SyliusByrdShippingExportPlugin\Api\Client;

use BitBag\SyliusByrdShippingExportPlugin\Api\ByrdRequest\CreateShipmentByrdRequest;
use BitBag\SyliusByrdShippingExportPlugin\Api\ByrdRequest\CreateShipmentByrdRequestInterface;
use BitBag\SyliusByrdShippingExportPlugin\Api\ByrdRequest\FindProductByrdRequest;
use BitBag\SyliusByrdShippingExportPlugin\Api\ByrdRequest\FindProductByrdRequestInterface;
use BitBag\SyliusByrdShippingExportPlugin\Api\ByrdRequest\GenerateTokenByrdRequest;
use BitBag\SyliusByrdShippingExportPlugin\Api\ByrdRequest\GenerateTokenByrdRequestInterface;
use BitBag\SyliusByrdShippingExportPlugin\Api\Exception\AuthorizationIssueException;
use BitBag\SyliusByrdShippingExportPlugin\Api\RequestSenderInterface;
use BitBag\SyliusShippingExportPlugin\Entity\ShippingGatewayInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class ByrdHttpClient implements ByrdHttpClientInterface
{
    /** @var GenerateTokenByrdRequestInterface */
    private $generateTokenRequest;

    /** @var CreateShipmentByrdRequestInterface */
    private $createShipmentRequest;

    /** @var FindProductByrdRequestInterface */
    private $findProductByrdRequest;

    /** @var RequestSenderInterface */
    private $requestSender;

    public function __construct(
        GenerateTokenByrdRequest $generateTokenRequest,
        CreateShipmentByrdRequest $createShipmentRequest,
        FindProductByrdRequest $findProductByrdRequest,
        RequestSenderInterface $requestSender,
    ) {
        $this->generateTokenRequest = $generateTokenRequest;
        $this->createShipmentRequest = $createShipmentRequest;
        $this->findProductByrdRequest = $findProductByrdRequest;
        $this->requestSender = $requestSender;
    }

    public function createShipment(
        OrderInterface $order,
        ShippingGatewayInterface $shippingGateway,
    ): void {
        $token = $this->receiveAuthorizationToken($shippingGateway);

        $this->createShipmentRequest->setOrder($order);
        $this->createShipmentRequest->setShippingGateway($shippingGateway);
        $response = $this->requestSender->sendAuthorized($this->createShipmentRequest, $token);

        if (Response::HTTP_CREATED !== $response->getStatusCode()) {
            throw new \InvalidArgumentException('Something went wrong: ' . $response->getContent());
        }
    }

    private function receiveAuthorizationToken(ShippingGatewayInterface $shippingGateway): string
    {
        $gatewayConfig = $shippingGateway->getConfig();
        $this->generateTokenRequest->setCredentials(
            $gatewayConfig['api_key'] ?? '',
            $gatewayConfig['api_secret'] ?? '',
        );

        $response = $this->requestSender->send($this->generateTokenRequest);

        if (Response::HTTP_CREATED != $response->getStatusCode()) {
            throw new AuthorizationIssueException('Authorization issue');
        }

        $content = json_decode($response->getContent());
        if (!$content->token) {
            throw new \Exception('No token received from server');
        }

        return $content->token;
    }

    public function filterProductsBySku(?string $sku, ShippingGatewayInterface $shippingGateway): array
    {
        $token = $this->receiveAuthorizationToken($shippingGateway);

        $this->findProductByrdRequest->setSearchField('q');
        $this->findProductByrdRequest->setByrdProductSku((string) $sku);

        /** @var ResponseInterface $response */
        $response = $this->requestSender->sendAuthorized($this->findProductByrdRequest, $token);
        $response = json_decode($response->getContent());
        if (!is_array($response->data) || 0 === count($response->data)) {
            return [];
        }

        $products = [];
        foreach ($response->data as $key => $value) {
            $products[] = [
                'name' => sprintf('%s (SKU: %s)', $value->name, $value->sku),
                'sku' => $value->sku,
            ];
        }

        return $products;
    }
}
