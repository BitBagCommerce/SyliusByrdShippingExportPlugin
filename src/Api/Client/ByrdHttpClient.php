<?php

/*
 * This file was created by developers working at BitBag
 * Do you need more information about us and what we do? Visit our https://bitbag.io website!
 * We are hiring developers from all over the world. Join us and start your new, exciting adventure and become part of us: https://bitbag.io/career
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
        RequestSenderInterface $requestSender
    ) {
        $this->generateTokenRequest = $generateTokenRequest;
        $this->createShipmentRequest = $createShipmentRequest;
        $this->findProductByrdRequest = $findProductByrdRequest;
        $this->requestSender = $requestSender;
    }

    public function createShipment(
        OrderInterface $order,
        ShippingGatewayInterface $shippingGateway
    ): void {
        $token = $this->receiveAuthorizationToken($shippingGateway);

        $this->createShipmentRequest->setOrder($order);
        $this->createShipmentRequest->setShippingGateway($shippingGateway);
        $response = $this->requestSender->sendAuthorized($this->createShipmentRequest, $token);

        if ($response->getStatusCode() !== Response::HTTP_CREATED) {
            throw new \InvalidArgumentException('Something went wrong: ' . $response->getContent());
        }
    }

    private function receiveAuthorizationToken(ShippingGatewayInterface $shippingGateway): string
    {
        $gatewayConfig = $shippingGateway->getConfig();
        $this->generateTokenRequest->setCredentials(
            $gatewayConfig['api_key'] ?? '',
            $gatewayConfig['api_secret'] ?? ''
        );

        $response = $this->requestSender->send($this->generateTokenRequest);

        if ($response->getStatusCode() != Response::HTTP_CREATED) {
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
        if (!is_array($response->data) || count($response->data) === 0) {
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
