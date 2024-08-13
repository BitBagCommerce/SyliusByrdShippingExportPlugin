<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\SyliusByrdShippingExportPlugin\Api\ByrdRequest;

use BitBag\SyliusByrdShippingExportPlugin\Api\Exception\AuthorizationIssueException;
use BitBag\SyliusByrdShippingExportPlugin\Api\Exception\EmptyProductListException;
use BitBag\SyliusByrdShippingExportPlugin\Api\Exception\NoOrderAttachedException;
use BitBag\SyliusByrdShippingExportPlugin\Api\Exception\NoShippingGatewayAttachedException;
use BitBag\SyliusByrdShippingExportPlugin\Api\Exception\ProductNotFoundException;
use BitBag\SyliusByrdShippingExportPlugin\Api\Factory\ByrdModelFactoryInterface;
use BitBag\SyliusByrdShippingExportPlugin\Api\Model\ByrdProduct;
use BitBag\SyliusByrdShippingExportPlugin\Api\RequestSenderInterface;
use BitBag\SyliusByrdShippingExportPlugin\Entity\ByrdProductMappingInterface;
use BitBag\SyliusByrdShippingExportPlugin\Repository\ByrdProductMappingRepositoryInterface;
use BitBag\SyliusShippingExportPlugin\Entity\ShippingGatewayInterface;
use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Model\ShippingMethodInterface;
use Sylius\Component\Shipping\Model\ShippingCategoryInterface;
use Symfony\Component\HttpFoundation\Request;

final class CreateShipmentByrdRequest extends AbstractByrdRequest implements CreateShipmentByrdRequestInterface
{
    /** @var ByrdProductMappingRepositoryInterface */
    private $byrdProductMappingRepository;

    /** @var FindProductByrdRequestInterface */
    private $findProductRequest;

    /** @var ByrdModelFactoryInterface */
    private $byrdModelFactory;

    /** @var RequestSenderInterface */
    private $requestSender;

    /** @var string */
    protected $requestMethod = Request::METHOD_POST;

    /** @var string */
    protected $requestUrl = '/shipments';

    /** @var OrderInterface|null */
    private $order;

    /** @var ShippingGatewayInterface|null */
    private $shippingGateway;

    public function __construct(
        ByrdProductMappingRepositoryInterface $byrdProductMappingRepository,
        FindProductByrdRequestInterface $findProductRequest,
        ByrdModelFactoryInterface $byrdModelFactory,
        RequestSenderInterface $requestSender,
        string $apiUrl,
    ) {
        parent::__construct($apiUrl);

        $this->byrdProductMappingRepository = $byrdProductMappingRepository;
        $this->findProductRequest = $findProductRequest;
        $this->byrdModelFactory = $byrdModelFactory;
        $this->requestSender = $requestSender;
    }

    public function setOrder(
        OrderInterface $order,
    ): void {
        $this->order = $order;
    }

    public function setShippingGateway(
        ShippingGatewayInterface $shippingGateway,
    ): void {
        $this->shippingGateway = $shippingGateway;
    }

    public function buildRequest(?string $authorizationToken): array
    {
        if (null === $this->shippingGateway) {
            throw new NoShippingGatewayAttachedException(
                'You have to set up shippingGateway via setShippingGateway(...) method',
            );
        }

        if (null === $this->order) {
            throw new NoOrderAttachedException('You have to set up order via setOrder(...) method');
        }

        if (null === $authorizationToken) {
            throw new AuthorizationIssueException('No token provided');
        }

        $request = $this->constructNewShippingRequestBase($this->order);

        $request['shipmentItems'] = $this->createShipmentItemsRequest($this->order, $authorizationToken);
        if (0 === count($request['shipmentItems'])) {
            throw new EmptyProductListException('Cannot sent request with no product');
        }

        $request['destinationAddress'] = $this->createDestinationAddressRequest($this->order);

        $config = $this->shippingGateway->getConfig();
        if (isset($config['shipping_option'])) {
            $request['option'] = $config['shipping_option'];
        }

        return $this->buildRequestFromParams($request);
    }

    private function constructNewShippingRequestBase(
        OrderInterface $order,
    ): array {
        /** @var CustomerInterface $customer */
        $customer = $order->getCustomer();

        /** @var AddressInterface $shippingAddress */
        $shippingAddress = $order->getShippingAddress();

        return [
            'destinationName' => $shippingAddress->getFullName(),
            'destinationPhone' => $shippingAddress->getPhoneNumber(),
            'destinationEmail' => $customer->getEmailCanonical(),
            'destinationCompany' => $shippingAddress->getCompany(),
            'description' => $order->getNotes(),
            'fragile' => false,
            'option' => 'standard',
            'status' => 'new',
        ];
    }

    private function createShipmentItemsRequest(
        OrderInterface $order,
        string $authorizationToken,
    ): array {
        $shipmentItems = [];

        foreach ($order->getItems() as $item) {
            /** @var ProductInterface $product */
            $product = $item->getProduct();

            if (!$this->hasConfiguredByrdShipment($item)) {
                continue;
            }

            $sku = $product->getCode();
            if (!$this->shouldAutoMatchBySku()) {
                /** @var ByrdProductMappingInterface|null $byrdMapping */
                $byrdMapping = $this->byrdProductMappingRepository->findForProduct($product);
                if (null === $byrdMapping) {
                    continue;
                }

                $sku = $byrdMapping->getByrdProductSku();
            }

            $shipmentItems[] = $this->createShipmentItem(
                (string) $sku,
                $item->getQuantity(),
                $authorizationToken,
            );
        }

        return $shipmentItems;
    }

    private function shouldAutoMatchBySku(): bool
    {
        if (null === $this->shippingGateway) {
            return false;
        }

        /** @var array $config */
        $config = $this->shippingGateway->getConfig();

        return isset($config['auto_sku_matching']) && true === $config['auto_sku_matching'];
    }

    private function createShipmentItem(
        string $byrdProductSku,
        int $quantity,
        string $authorizationToken,
    ): array {
        $byrdProduct = $this->fetchByrdProductInformation($byrdProductSku, $authorizationToken);

        return [
            'amount' => $quantity,
            'byrdProductID' => $byrdProduct->getId(),
            'description' => $byrdProduct->getDescription(),
            'productName' => $byrdProduct->getName(),
            'sku' => $byrdProductSku,
        ];
    }

    private function fetchByrdProductInformation(
        string $byrdProductSku,
        string $authorizationToken,
    ): ByrdProduct {
        $this->findProductRequest->setByrdProductSku($byrdProductSku);
        $response = $this->requestSender->sendAuthorized($this->findProductRequest, $authorizationToken);

        $content = json_decode($response->getContent());
        if (isset($content->data) && is_array($content->data) && 0 === count($content->data)) {
            throw new ProductNotFoundException('Product with SKU: ' . $byrdProductSku . ' was not found');
        }
        $product = current($content->data);

        return $this->byrdModelFactory->create(
            $product->id,
            $product->name,
            $product->description,
        );
    }

    private function createDestinationAddressRequest(OrderInterface $order): array
    {
        /** @var AddressInterface $shippingAddress */
        $shippingAddress = $order->getShippingAddress();

        return [
            'countryCode' => $shippingAddress->getCountryCode(),
            'locality' => $shippingAddress->getCity(),
            'postalCode' => $shippingAddress->getPostcode(),
            'thoroughfare' => $shippingAddress->getStreet(),
        ];
    }

    private function hasConfiguredByrdShipment(OrderItemInterface $orderItem): bool
    {
        if (null === $this->shippingGateway) {
            return false;
        }

        /** @var ProductVariantInterface $variant */
        $variant = $orderItem->getVariant();

        if (!$variant->isShippingRequired()) {
            return false;
        }

        /** @var ShippingCategoryInterface|null $variantsShippingCategory */
        $variantsShippingCategory = $variant->getShippingCategory();
        if (null === $variantsShippingCategory) {
            return false;
        }

        /** @var array $gatewaysMethods */
        $gatewaysMethods = $this->shippingGateway->getShippingMethods();

        /** @var ShippingMethodInterface $gatewaysMethod */
        foreach ($gatewaysMethods as $gatewaysMethod) {
            $category = $gatewaysMethod->getCategory();
            if (null !== $category && $category->getId() === $variantsShippingCategory->getId()) {
                return true;
            }
        }

        return false;
    }
}
