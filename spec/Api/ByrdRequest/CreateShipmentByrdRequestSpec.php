<?php

/*
 * This file was created by developers working at BitBag
 * Do you need more information about us and what we do? Visit our https://bitbag.io website!
 * We are hiring developers from all over the world. Join us and start your new, exciting adventure and become part of us: https://bitbag.io/career
*/

declare(strict_types=1);

namespace spec\BitBag\SyliusByrdShippingExportPlugin\Api\ByrdRequest;

use BitBag\SyliusByrdShippingExportPlugin\Api\ByrdRequest\CreateShipmentByrdRequest;
use BitBag\SyliusByrdShippingExportPlugin\Api\ByrdRequest\FindProductByrdRequestInterface;
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
use Doctrine\Common\Collections\ArrayCollection;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Model\ShippingMethodInterface;
use Sylius\Component\Shipping\Model\ShippingCategoryInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class CreateShipmentByrdRequestSpec extends ObjectBehavior
{
    function let(
        ByrdProductMappingRepositoryInterface $byrdProductMappingRepository,
        FindProductByrdRequestInterface $findProductRequest,
        ByrdModelFactoryInterface $byrdModelFactory,
        RequestSenderInterface $requestSender
    ): void {
        $this->beConstructedWith(
            $byrdProductMappingRepository,
            $findProductRequest,
            $byrdModelFactory,
            $requestSender,
            "http://byrd-api-fake-url"
        );
    }

    function it_is_initializable(): void
    {
        $this->shouldHaveType(CreateShipmentByrdRequest::class);
    }

    function it_returns_get_request_method(): void
    {
        $this->getRequestMethod()->shouldReturn("POST");
    }

    function it_returns_request_url(): void
    {
        $this->getRequestUrl()->shouldReturn("http://byrd-api-fake-url/shipments");
    }

    function it_returns_request_with_automatching_by_sku_turned_on(
        OrderInterface $order,
        AddressInterface $shippingAddress,
        CustomerInterface $customer,
        OrderItemInterface $orderItem,
        ProductInterface $product,
        ProductVariantInterface $productVariant,
        RequestSenderInterface $requestSender,
        ResponseInterface $response,
        ByrdModelFactoryInterface $byrdModelFactory,
        ByrdProduct $byrdProduct,
        ShippingGatewayInterface $shippingGateway,
        ShippingCategoryInterface $shippingCategory,
        ShippingMethodInterface $shippingMethod
    ): void {
        $order->getShippingAddress()->willReturn($shippingAddress);
        $order->getCustomer()->willReturn($customer);
        $order->getNotes()->willReturn("Notes attached to order");
        $order->getItems()->willReturn(new ArrayCollection([$orderItem->getWrappedObject()]));
        $orderItem->getProduct()->willReturn($product);
        $orderItem->getQuantity()->willReturn(50);
        $product->getCode()->willReturn('ProductCode');
        $orderItem->getVariant()->willReturn($productVariant);
        $productVariant->isShippingRequired()->willReturn(true);
        $productVariant->getShippingCategory()->willReturn($shippingCategory);
        $shippingCategory->getId()->willReturn(10);

        $requestSender->sendAuthorized(Argument::type(FindProductByrdRequestInterface::class), "authorization-token")->willReturn($response);
        $response->getContent()->willReturn('{"data": [{"id": "byrd-product-id", "name": "Byrd product name", "description": "Byrd product description"}]}');
        $byrdModelFactory->create("byrd-product-id", "Byrd product name", "Byrd product description")->willReturn($byrdProduct);

        $byrdProduct->getId()->willReturn("byrd-product-id");
        $byrdProduct->getName()->willReturn("Byrd product name");
        $byrdProduct->getDescription()->willReturn("Byrd product description");

        $shippingGateway->getConfig()->willReturn([
            "auto_sku_matching" => true,
            "shipping_option" => "express",
        ]);
        $shippingGateway->getShippingMethods()->willReturn(new ArrayCollection([$shippingMethod->getWrappedObject()]));
        $this->setShippingGateway($shippingGateway);

        $shippingMethod->getCategory()->willReturn($shippingCategory);

        $this->setOrder($order);
        $this->buildRequest("authorization-token")->shouldReturn([
            'headers' => [
                'Accept' => "application/json",
                'Content-Type' => "application/json",
            ],
            'body' => '{"destinationName":null,"destinationPhone":null,"destinationEmail":null,"destinationCompany":null,"description":"Notes attached to order","fragile":false,"option":"express","status":"new","shipmentItems":[{"amount":50,"byrdProductID":"byrd-product-id","description":"Byrd product description","productName":"Byrd product name","sku":"ProductCode"}],"destinationAddress":{"countryCode":null,"locality":null,"postalCode":null,"thoroughfare":null}}'
        ]);
    }

    function it_throws_exception_with_automatching_by_sku_turned_on_with_no_product_found_on_byrd_side(
        OrderInterface $order,
        AddressInterface $shippingAddress,
        CustomerInterface $customer,
        OrderItemInterface $orderItem,
        ProductInterface $product,
        RequestSenderInterface $requestSender,
        ResponseInterface $response,
        ShippingGatewayInterface $shippingGateway,
        ProductVariantInterface $productVariant,
        ShippingMethodInterface $shippingMethod,
        ShippingCategoryInterface $shippingCategory
    ): void {
        $order->getShippingAddress()->willReturn($shippingAddress);
        $order->getCustomer()->willReturn($customer);
        $order->getNotes()->willReturn("Notes attached to order");
        $order->getItems()->willReturn(new ArrayCollection([$orderItem->getWrappedObject()]));
        $orderItem->getProduct()->willReturn($product);
        $orderItem->getQuantity()->willReturn(50);
        $orderItem->getVariant()->willReturn($productVariant);
        $product->getCode()->willReturn('ProductCode');
        $productVariant->isShippingRequired()->willReturn(true);
        $productVariant->getShippingCategory()->willReturn($shippingCategory);
        $shippingCategory->getId()->willReturn(10);

        $requestSender->sendAuthorized(Argument::type(FindProductByrdRequestInterface::class), "authorization-token")->willReturn($response);
        $response->getContent()->willReturn('{"data": []}');

        $shippingGateway->getConfig()->willReturn([
            "auto_sku_matching" => true,
            "shipping_option" => "express",
        ]);
        $shippingGateway->getShippingMethods()->willReturn(new ArrayCollection([$shippingMethod->getWrappedObject()]));

        $shippingMethod->getCategory()->willReturn($shippingCategory);

        $this->setShippingGateway($shippingGateway);
        $this->setOrder($order);
        $this->shouldThrow(ProductNotFoundException::class)->during('buildRequest', ["authorization-token"]);
    }

    function it_returns_request_with_automatching_by_sku_turned_off_with_found_mapping(
        OrderInterface $order,
        AddressInterface $shippingAddress,
        CustomerInterface $customer,
        OrderItemInterface $orderItem,
        ProductInterface $product,
        RequestSenderInterface $requestSender,
        ResponseInterface $response,
        ByrdModelFactoryInterface $byrdModelFactory,
        ByrdProduct $byrdProduct,
        ShippingGatewayInterface $shippingGateway,
        ByrdProductMappingRepositoryInterface $byrdProductMappingRepository,
        ByrdProductMappingInterface $byrdProductMapping,
        ProductVariantInterface $productVariant,
        ShippingMethodInterface $shippingMethod,
        ShippingCategoryInterface $shippingCategory
    ): void {
        $order->getShippingAddress()->willReturn($shippingAddress);
        $order->getCustomer()->willReturn($customer);
        $order->getNotes()->willReturn("Notes attached to order");
        $order->getItems()->willReturn(new ArrayCollection([$orderItem->getWrappedObject()]));
        $orderItem->getProduct()->willReturn($product);
        $orderItem->getQuantity()->willReturn(50);
        $orderItem->getVariant()->willReturn($productVariant);
        $product->getCode()->willReturn('ProductCode');
        $productVariant->isShippingRequired()->willReturn(true);
        $productVariant->getShippingCategory()->willReturn($shippingCategory);
        $shippingCategory->getId()->willReturn(10);

        $requestSender->sendAuthorized(Argument::type(FindProductByrdRequestInterface::class), "authorization-token")->willReturn($response);
        $response->getContent()->willReturn('{"data": [{"id": "byrd-product-id", "name": "Byrd product name", "description": "Byrd product description"}]}');
        $byrdModelFactory->create("byrd-product-id", "Byrd product name", "Byrd product description")->willReturn($byrdProduct);

        $byrdProduct->getId()->willReturn("byrd-product-id");
        $byrdProduct->getName()->willReturn("Byrd product name");
        $byrdProduct->getDescription()->willReturn("Byrd product description");

        $shippingGateway->getConfig()->willReturn([
            "auto_sku_matching" => false,
            "shipping_option" => "express",
        ]);
        $shippingGateway->getShippingMethods()->willReturn(new ArrayCollection([$shippingMethod->getWrappedObject()]));

        $shippingMethod->getCategory()->willReturn($shippingCategory);

        $byrdProductMappingRepository->findForProduct($product)->willReturn($byrdProductMapping);
        $byrdProductMapping->getByrdProductSku()->willReturn("byrd-product-sku");

        $this->setShippingGateway($shippingGateway);
        $this->setOrder($order);
        $this->buildRequest("authorization-token")->shouldReturn([
            'headers' => [
                'Accept' => "application/json",
                'Content-Type' => "application/json",
            ],
            'body' => '{"destinationName":null,"destinationPhone":null,"destinationEmail":null,"destinationCompany":null,"description":"Notes attached to order","fragile":false,"option":"express","status":"new","shipmentItems":[{"amount":50,"byrdProductID":"byrd-product-id","description":"Byrd product description","productName":"Byrd product name","sku":"byrd-product-sku"}],"destinationAddress":{"countryCode":null,"locality":null,"postalCode":null,"thoroughfare":null}}'
        ]);
    }

    function it_returns_request_with_automatching_turned_on_by_default(
        OrderInterface $order,
        AddressInterface $shippingAddress,
        CustomerInterface $customer,
        OrderItemInterface $orderItem,
        ProductInterface $product,
        RequestSenderInterface $requestSender,
        ResponseInterface $response,
        ByrdModelFactoryInterface $byrdModelFactory,
        ByrdProduct $byrdProduct,
        ShippingGatewayInterface $shippingGateway,
        ByrdProductMappingRepositoryInterface $byrdProductMappingRepository,
        ByrdProductMappingInterface $byrdProductMapping,
        ProductVariantInterface $productVariant,
        ShippingMethodInterface $shippingMethod,
        ShippingCategoryInterface $shippingCategory
    ): void {
        $order->getShippingAddress()->willReturn($shippingAddress);
        $order->getCustomer()->willReturn($customer);
        $order->getNotes()->willReturn("Notes attached to order");
        $order->getItems()->willReturn(new ArrayCollection([$orderItem->getWrappedObject()]));
        $orderItem->getProduct()->willReturn($product);
        $orderItem->getQuantity()->willReturn(50);
        $orderItem->getVariant()->willReturn($productVariant);
        $product->getCode()->willReturn('ProductCode');
        $productVariant->isShippingRequired()->willReturn(true);
        $productVariant->getShippingCategory()->willReturn($shippingCategory);
        $shippingCategory->getId()->willReturn(10);

        $requestSender->sendAuthorized(Argument::type(FindProductByrdRequestInterface::class), "authorization-token")->willReturn($response);
        $response->getContent()->willReturn('{"data": [{"id": "byrd-product-id", "name": "Byrd product name", "description": "Byrd product description"}]}');
        $byrdModelFactory->create("byrd-product-id", "Byrd product name", "Byrd product description")->willReturn($byrdProduct);

        $byrdProduct->getId()->willReturn("byrd-product-id");
        $byrdProduct->getName()->willReturn("Byrd product name");
        $byrdProduct->getDescription()->willReturn("Byrd product description");

        $shippingGateway->getConfig()->willReturn([
            "auto_sku_matching" => false,
            "shipping_option" => "standard",
        ]);

        $shippingGateway->getShippingMethods()->willReturn(new ArrayCollection([$shippingMethod->getWrappedObject()]));

        $shippingMethod->getCategory()->willReturn($shippingCategory);

        $byrdProductMappingRepository->findForProduct($product)->willReturn($byrdProductMapping);
        $byrdProductMapping->getByrdProductSku()->willReturn("byrd-product-sku");

        $this->setShippingGateway($shippingGateway);
        $this->setOrder($order);
        $this->buildRequest("authorization-token")->shouldReturn([
            'headers' => [
                'Accept' => "application/json",
                'Content-Type' => "application/json",
            ],
            'body' => '{"destinationName":null,"destinationPhone":null,"destinationEmail":null,"destinationCompany":null,"description":"Notes attached to order","fragile":false,"option":"standard","status":"new","shipmentItems":[{"amount":50,"byrdProductID":"byrd-product-id","description":"Byrd product description","productName":"Byrd product name","sku":"byrd-product-sku"}],"destinationAddress":{"countryCode":null,"locality":null,"postalCode":null,"thoroughfare":null}}'
        ]);
    }

    function it_throws_exception_when_no_shipping_gateway_configured(
        OrderInterface $order
    ): void {
        $this->setOrder($order);
        $this->shouldThrow(NoShippingGatewayAttachedException::class)->during('buildRequest', ["authorization-token"]);
    }

    function it_throws_exception_when_no_order_configured(
        ShippingGatewayInterface $shippingGateway
    ): void {
        $this->setShippingGateway($shippingGateway);
        $this->shouldThrow(NoOrderAttachedException::class)->during('buildRequest', ["authorization-token"]);
    }

    function it_returns_request_with_customer_information_when_available(
        OrderInterface $order,
        AddressInterface $shippingAddress,
        CustomerInterface $customer,
        OrderItemInterface $orderItem,
        ProductInterface $product,
        RequestSenderInterface $requestSender,
        ResponseInterface $response,
        ByrdModelFactoryInterface $byrdModelFactory,
        ByrdProduct $byrdProduct,
        ShippingGatewayInterface $shippingGateway,
        ByrdProductMappingRepositoryInterface $byrdProductMappingRepository,
        ByrdProductMappingInterface $byrdProductMapping,
        ProductVariantInterface $productVariant,
        ShippingMethodInterface $shippingMethod,
        ShippingCategoryInterface $shippingCategory
    ): void {
        $customer->getEmailCanonical()->willReturn("customer@email.com");
        $shippingAddress->getFullName()->willReturn("Full name");
        $shippingAddress->getPhoneNumber()->willReturn("+1231231231");
        $shippingAddress->getCompany()->willReturn("Company name");
        $shippingAddress->getCountryCode()->willReturn("PL");
        $shippingAddress->getCity()->willReturn("City name");
        $shippingAddress->getPostcode()->willReturn("Postal code");
        $shippingAddress->getStreet()->willReturn("Street name 3/4");

        $order->getShippingAddress()->willReturn($shippingAddress);
        $order->getCustomer()->willReturn($customer);
        $order->getNotes()->willReturn("Notes attached to order");
        $order->getItems()->willReturn(new ArrayCollection([$orderItem->getWrappedObject()]));
        $orderItem->getProduct()->willReturn($product);
        $orderItem->getQuantity()->willReturn(50);
        $orderItem->getVariant()->willReturn($productVariant);
        $product->getCode()->willReturn('ProductCode');
        $productVariant->isShippingRequired()->willReturn(true);
        $productVariant->getShippingCategory()->willReturn($shippingCategory);
        $shippingCategory->getId()->willReturn(10);

        $requestSender->sendAuthorized(Argument::type(FindProductByrdRequestInterface::class), "authorization-token")->willReturn($response);
        $response->getContent()->willReturn('{"data": [{"id": "byrd-product-id", "name": "Byrd product name", "description": "Byrd product description"}]}');
        $byrdModelFactory->create("byrd-product-id", "Byrd product name", "Byrd product description")->willReturn($byrdProduct);

        $byrdProduct->getId()->willReturn("byrd-product-id");
        $byrdProduct->getName()->willReturn("Byrd product name");
        $byrdProduct->getDescription()->willReturn("Byrd product description");

        $shippingGateway->getConfig()->willReturn([]);
        $shippingGateway->getShippingMethods()->willReturn(new ArrayCollection([$shippingMethod->getWrappedObject()]));

        $shippingMethod->getCategory()->willReturn($shippingCategory);

        $byrdProductMappingRepository->findForProduct($product)->willReturn($byrdProductMapping);
        $byrdProductMapping->getByrdProductSku()->willReturn("byrd-product-sku");

        $this->setShippingGateway($shippingGateway);
        $this->setOrder($order);
        $this->buildRequest("authorization-token")->shouldReturn([
            'headers' => [
                'Accept' => "application/json",
                'Content-Type' => "application/json",
            ],
            'body' => '{"destinationName":"Full name","destinationPhone":"+1231231231","destinationEmail":"customer@email.com","destinationCompany":"Company name","description":"Notes attached to order","fragile":false,"option":"standard","status":"new","shipmentItems":[{"amount":50,"byrdProductID":"byrd-product-id","description":"Byrd product description","productName":"Byrd product name","sku":"byrd-product-sku"}],"destinationAddress":{"countryCode":"PL","locality":"City name","postalCode":"Postal code","thoroughfare":"Street name 3\/4"}}'
        ]);
    }

    function it_throws_exception_with_automatching_by_sku_turned_off_with_no_mapping_found(
        OrderInterface $order,
        AddressInterface $shippingAddress,
        CustomerInterface $customer,
        OrderItemInterface $orderItem,
        ProductInterface $product,
        RequestSenderInterface $requestSender,
        ResponseInterface $response,
        ByrdModelFactoryInterface $byrdModelFactory,
        ByrdProduct $byrdProduct,
        ShippingGatewayInterface $shippingGateway,
        ByrdProductMappingRepositoryInterface $byrdProductMappingRepository,
        ProductVariantInterface $productVariant,
        ShippingMethodInterface $shippingMethod,
        ShippingCategoryInterface $shippingCategory
    ): void {
        $order->getShippingAddress()->willReturn($shippingAddress);
        $order->getCustomer()->willReturn($customer);
        $order->getNotes()->willReturn("Notes attached to order");
        $order->getItems()->willReturn(new ArrayCollection([$orderItem->getWrappedObject()]));
        $orderItem->getProduct()->willReturn($product);
        $orderItem->getQuantity()->willReturn(50);
        $orderItem->getVariant()->willReturn($productVariant);
        $product->getCode()->willReturn('ProductCode');
        $productVariant->isShippingRequired()->willReturn(true);
        $productVariant->getShippingCategory()->willReturn($shippingCategory);
        $shippingCategory->getId()->willReturn(10);

        $requestSender->sendAuthorized(Argument::type(FindProductByrdRequestInterface::class), "authorization-token")->willReturn($response);
        $response->getContent()->willReturn('{"data": [{"id": "byrd-product-id", "name": "Byrd product name", "description": "Byrd product description"}]}');
        $byrdModelFactory->create("byrd-product-id", "Byrd product name", "Byrd product description")->willReturn($byrdProduct);

        $shippingGateway->getConfig()->willReturn([
            "auto_sku_matching" => false,
            "shipping_option" => "express",
        ]);
        $shippingGateway->getShippingMethods()->willReturn(new ArrayCollection([$shippingMethod->getWrappedObject()]));

        $shippingMethod->getCategory()->willReturn($shippingCategory);

        $byrdProductMappingRepository->findForProduct($product)->willReturn(null);

        $this->setShippingGateway($shippingGateway);
        $this->setOrder($order);

        $this->shouldThrow(EmptyProductListException::class)->during('buildRequest', ["authorization-token"]);
    }

    function it_throws_exception_with_automatching_by_sku_turned_off_with_product_not_found_on_byrd_side(
        OrderInterface $order,
        AddressInterface $shippingAddress,
        CustomerInterface $customer,
        OrderItemInterface $orderItem,
        ProductInterface $product,
        RequestSenderInterface $requestSender,
        ResponseInterface $response,
        ShippingGatewayInterface $shippingGateway,
        ByrdProductMappingRepositoryInterface $byrdProductMappingRepository,
        ByrdProductMappingInterface $byrdProductMapping,
        ProductVariantInterface $productVariant,
        ShippingMethodInterface $shippingMethod,
        ShippingCategoryInterface $shippingCategory
    ): void {
        $order->getShippingAddress()->willReturn($shippingAddress);
        $order->getCustomer()->willReturn($customer);
        $order->getNotes()->willReturn("Notes attached to order");
        $order->getItems()->willReturn(new ArrayCollection([$orderItem->getWrappedObject()]));
        $orderItem->getProduct()->willReturn($product);
        $orderItem->getQuantity()->willReturn(50);
        $orderItem->getVariant()->willReturn($productVariant);
        $product->getCode()->willReturn('ProductCode');
        $productVariant->isShippingRequired()->willReturn(true);
        $productVariant->getShippingCategory()->willReturn($shippingCategory);
        $shippingCategory->getId()->willReturn(10);

        $requestSender->sendAuthorized(Argument::type(FindProductByrdRequestInterface::class), "authorization-token")->willReturn($response);
        $response->getContent()->willReturn('{"data": []}');

        $byrdProductMappingRepository->findForProduct($product)->willReturn($byrdProductMapping);
        $byrdProductMapping->getByrdProductSku()->willReturn("byrd-product-sku");

        $shippingGateway->getConfig()->willReturn([
            "auto_sku_matching" => false,
            "shipping_option" => "express",
        ]);
        $shippingGateway->getShippingMethods()->willReturn(new ArrayCollection([$shippingMethod->getWrappedObject()]));

        $shippingMethod->getCategory()->willReturn($shippingCategory);

        $this->setShippingGateway($shippingGateway);
        $this->setOrder($order);

        $this->shouldThrow(ProductNotFoundException::class)->during('buildRequest', ["authorization-token"]);
    }

    function it_throws_exception_when_no_byrd_products_in_order(
        OrderInterface $order,
        AddressInterface $shippingAddress,
        CustomerInterface $customer,
        OrderItemInterface $orderItem,
        ProductInterface $product,
        RequestSenderInterface $requestSender,
        ResponseInterface $response,
        ByrdModelFactoryInterface $byrdModelFactory,
        ByrdProduct $byrdProduct,
        ShippingGatewayInterface $shippingGateway,
        ByrdProductMappingRepositoryInterface $byrdProductMappingRepository,
        ProductVariantInterface $productVariant,
        ShippingMethodInterface $shippingMethod,
        ShippingCategoryInterface $shippingCategory
    ): void {
        $order->getShippingAddress()->willReturn($shippingAddress);
        $order->getCustomer()->willReturn($customer);
        $order->getNotes()->willReturn("Notes attached to order");
        $order->getItems()->willReturn(new ArrayCollection([$orderItem->getWrappedObject()]));
        $orderItem->getProduct()->willReturn($product);
        $orderItem->getQuantity()->willReturn(50);
        $orderItem->getVariant()->willReturn($productVariant);
        $product->getCode()->willReturn('ProductCode');
        $productVariant->isShippingRequired()->willReturn(true);
        $productVariant->getShippingCategory()->willReturn($shippingCategory);
        $shippingCategory->getId()->willReturn(10);

        $requestSender->sendAuthorized(Argument::type(FindProductByrdRequestInterface::class), "authorization-token")->willReturn($response);
        $response->getContent()->willReturn('{"data": [{"id": "byrd-product-id", "name": "Byrd product name", "description": "Byrd product description"}]}');
        $byrdModelFactory->create("byrd-product-id", "Byrd product name", "Byrd product description")->willReturn($byrdProduct);

        $shippingGateway->getConfig()->willReturn([
            "auto_sku_matching" => false,
            "shipping_option" => "express",
        ]);
        $shippingGateway->getShippingMethods()->willReturn(new ArrayCollection([$shippingMethod->getWrappedObject()]));

        $shippingMethod->getCategory()->willReturn($shippingCategory);

        $byrdProductMappingRepository->findForProduct($product)->willReturn(null);

        $this->setShippingGateway($shippingGateway);
        $this->setOrder($order);

        $this->shouldThrow(EmptyProductListException::class)->during('buildRequest', ["authorization-token"]);
    }

    function it_skips_item_which_doesnt_need_shipment(
        OrderInterface $order,
        AddressInterface $shippingAddress,
        CustomerInterface $customer,
        OrderItemInterface $orderItem,
        ProductInterface $product,
        ProductVariantInterface $productVariant,
        ShippingGatewayInterface $shippingGateway,
        ShippingCategoryInterface $shippingCategory
    ): void {
        $order->getShippingAddress()->willReturn($shippingAddress);
        $order->getCustomer()->willReturn($customer);
        $order->getNotes()->willReturn("Notes attached to order");
        $order->getItems()->willReturn(new ArrayCollection([$orderItem->getWrappedObject()]));
        $orderItem->getProduct()->willReturn($product);
        $orderItem->getQuantity()->willReturn(50);
        $product->getCode()->willReturn('ProductCode');
        $orderItem->getVariant()->willReturn($productVariant);
        $productVariant->isShippingRequired()->willReturn(false);
        $productVariant->getShippingCategory()->willReturn($shippingCategory);
        $shippingCategory->getId()->willReturn(10);

        $shippingGateway->getConfig()->willReturn([
            "auto_sku_matching" => true,
            "shipping_option" => "express",
        ]);

        $this->setShippingGateway($shippingGateway);
        $this->setOrder($order);

        $this->shouldThrow(EmptyProductListException::class)->during('buildRequest', ["authorization-token"]);
    }

    function it_skips_item_which_has_configured_no_shipping_category(
        OrderInterface $order,
        AddressInterface $shippingAddress,
        CustomerInterface $customer,
        OrderItemInterface $orderItem,
        ProductInterface $product,
        ProductVariantInterface $productVariant,
        ShippingGatewayInterface $shippingGateway,
        ShippingCategoryInterface $shippingCategory
    ): void {
        $order->getShippingAddress()->willReturn($shippingAddress);
        $order->getCustomer()->willReturn($customer);
        $order->getNotes()->willReturn("Notes attached to order");
        $order->getItems()->willReturn(new ArrayCollection([$orderItem->getWrappedObject()]));
        $orderItem->getProduct()->willReturn($product);
        $orderItem->getQuantity()->willReturn(50);
        $product->getCode()->willReturn('ProductCode');
        $orderItem->getVariant()->willReturn($productVariant);
        $productVariant->isShippingRequired()->willReturn(true);
        $productVariant->getShippingCategory()->willReturn(null);
        $shippingCategory->getId()->willReturn(10);

        $shippingGateway->getConfig()->willReturn([
            "auto_sku_matching" => true,
            "shipping_option" => "express",
        ]);

        $this->setShippingGateway($shippingGateway);
        $this->setOrder($order);
        $this->shouldThrow(EmptyProductListException::class)->during('buildRequest', ["authorization-token"]);
    }

    function it_skips_item_which_has_configured_different_shipping_category(
        OrderInterface $order,
        AddressInterface $shippingAddress,
        CustomerInterface $customer,
        OrderItemInterface $orderItem,
        ProductInterface $product,
        ProductVariantInterface $productVariant,
        ShippingGatewayInterface $shippingGateway,
        ShippingMethodInterface $shippingMethod,
        ShippingCategoryInterface $shippingCategory1,
        ShippingCategoryInterface $shippingCategory2
    ): void {
        $order->getShippingAddress()->willReturn($shippingAddress);
        $order->getCustomer()->willReturn($customer);
        $order->getNotes()->willReturn("Notes attached to order");
        $order->getItems()->willReturn(new ArrayCollection([$orderItem->getWrappedObject()]));
        $orderItem->getProduct()->willReturn($product);
        $orderItem->getQuantity()->willReturn(50);
        $product->getCode()->willReturn('ProductCode');
        $orderItem->getVariant()->willReturn($productVariant);
        $productVariant->isShippingRequired()->willReturn(true);
        $productVariant->getShippingCategory()->willReturn(null);
        $shippingCategory1->getId()->willReturn(10);

        $shippingGateway->getConfig()->willReturn([
            "auto_sku_matching" => true,
            "shipping_option" => "express",
        ]);
        $shippingGateway->getShippingMethods()->willReturn(new ArrayCollection([$shippingMethod->getWrappedObject()]));

        $shippingMethod->getCategory()->willReturn($shippingCategory2);
        $shippingCategory2->getId()->willReturn(20);

        $this->setShippingGateway($shippingGateway);
        $this->setOrder($order);
        $this->shouldThrow(EmptyProductListException::class)->during('buildRequest', ["authorization-token"]);
    }
}
