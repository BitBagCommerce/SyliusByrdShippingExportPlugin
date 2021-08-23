<?php

/*
 * This file was created by developers working at BitBag
 * Do you need more information about us and what we do? Visit our https://bitbag.io website!
 * We are hiring developers from all over the world. Join us and start your new, exciting adventure and become part of us: https://bitbag.io/career
*/

declare(strict_types=1);

namespace spec\BitBag\SyliusByrdShippingExportPlugin\EventListener;

use BitBag\SyliusByrdShippingExportPlugin\Api\Client\ByrdHttpClientInterface;
use BitBag\SyliusByrdShippingExportPlugin\Api\Exception\ByrdApiException;
use BitBag\SyliusByrdShippingExportPlugin\EventListener\ShippingExportEventListener;
use BitBag\SyliusShippingExportPlugin\Entity\ShippingExportInterface;
use BitBag\SyliusShippingExportPlugin\Entity\ShippingGatewayInterface;
use BitBag\SyliusShippingExportPlugin\Event\ExportShipmentEvent;
use BitBag\SyliusShippingExportPlugin\Repository\ShippingExportRepositoryInterface;
use BitBag\SyliusShippingExportPlugin\Repository\ShippingGatewayRepositoryInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\ShipmentInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ShippingExportEventListenerSpec extends ObjectBehavior
{
    function let(
        ByrdHttpClientInterface $byrdHttpClient,
        EntityManagerInterface $entityManager,
        ShippingExportRepositoryInterface $shippingExportRepository,
        FlashBagInterface $flashBag,
        Filesystem $filesystem,
        TranslatorInterface $translator,
        ShippingGatewayRepositoryInterface $shippingGatewayRepository
    ): void {
        $this->beConstructedWith(
            $byrdHttpClient,
            $entityManager,
            $shippingExportRepository,
            $flashBag,
            $filesystem,
            $translator,
            $shippingGatewayRepository,
            "shipping-labels-path"
        );
    }

    function it_is_initializable(): void
    {
        $this->shouldHaveType(ShippingExportEventListener::class);
    }

    function it_exports_shipment(
        ShippingExportInterface $shippingExport,
        OrderInterface $order,
        ShipmentInterface $shipment,
        ShippingGatewayInterface $shippingGateway,
        ExportShipmentEvent $event
    ): void
    {
        $shippingExport->getShipment()->willReturn($shipment);
        $shippingExport->getShippingGateway()->willReturn($shippingGateway);
        $shipment->getOrder()->willReturn($order);

        $event->getShippingExport()->willReturn($shippingExport);
        $event->addSuccessFlash()->shouldBeCalled();
        $event->exportShipment()->shouldBeCalled();

        $this->exportShipment($event);
    }

    function it_adds_flash_on_failed_export(
        ByrdHttpClientInterface $byrdHttpClient,
        ShippingExportInterface $shippingExport,
        OrderInterface $order,
        ShipmentInterface $shipment,
        ShippingGatewayInterface $shippingGateway,
        EntityManagerInterface $entityManager,
        ExportShipmentEvent $event
    ): void
    {
        $shippingExport->getShipment()->willReturn($shipment);
        $shippingExport->getShippingGateway()->willReturn($shippingGateway);
        $shippingExport->setState("failed")->shouldBeCalled();
        $entityManager->flush()->shouldBeCalled();
        $shipment->getOrder()->willReturn($order);

        $event->getShippingExport()->willReturn($shippingExport);
        $event->addErrorFlash("Byrd error for order : ")->shouldBeCalled();

        $order->getNumber()->shouldBeCalled();

        $byrdHttpClient->createShipment($order, $shippingGateway)->willThrow(ByrdApiException::class);

        $this->exportShipment($event);
    }

    function it_auto_exports_shipment(
        PaymentInterface $payment,
        ShippingGatewayRepositoryInterface $shippingGatewayRepository,
        ShippingGatewayInterface $shippingGateway,
        OrderInterface $order,
        ShipmentInterface $shipment,
        ShippingExportRepositoryInterface $shippingExportRepository,
        ShippingExportInterface $shippingExport
    ): void {
        $shippingGatewayRepository->findOneByCode('byrd')->willReturn($shippingGateway);
        $shippingGateway->getConfig()->willReturn([
            'auto_export' => true,
        ]);

        $payment->getOrder()->willReturn($order);
        $order->getShipments()->willReturn(new ArrayCollection([$shipment->getWrappedObject()]));
        $shipment->getId()->willReturn(10);
        $shipment->getOrder()->willReturn($order);

        $shippingExportRepository->findOneBy([
            'shipment' => 10,
        ])->willReturn($shippingExport);

        $shippingExport->getState()->willReturn("new");
        $shippingExport->getShipment()->willReturn($shipment);
        $shippingExport->getShippingGateway()->willReturn($shippingGateway);

        $shippingExport->setState("exported")->shouldBeCalled();
        $shippingExport->setExportedAt(Argument::type(\DateTime::class))->shouldBeCalled();

        $this->autoExport($payment);
    }

    function it_doesnt_auto_export_shipment_due_nullable_shipping_gateway(
        PaymentInterface $payment,
        ShippingGatewayRepositoryInterface $shippingGatewayRepository
    ): void {
        $shippingGatewayRepository->findOneByCode('byrd')->willReturn(null);

        $this->autoExport($payment);
    }

    function it_doesnt_auto_export_shipment_due_not_set_auto_export_gateway_configuration(
        PaymentInterface $payment,
        ShippingGatewayRepositoryInterface $shippingGatewayRepository,
        ShippingGatewayInterface $shippingGateway
    ): void {
        $shippingGatewayRepository->findOneByCode('byrd')->willReturn($shippingGateway);
        $shippingGateway->getConfig()->willReturn([]);

        $this->autoExport($payment);
    }

    function it_doesnt_auto_export_shipment_due_auto_export_gateway_configuration_set_to_false(
        PaymentInterface $payment,
        ShippingGatewayRepositoryInterface $shippingGatewayRepository,
        ShippingGatewayInterface $shippingGateway
    ): void {
        $shippingGatewayRepository->findOneByCode('byrd')->willReturn($shippingGateway);
        $shippingGateway->getConfig()->willReturn([
            'auto_export' => false,
        ]);

        $this->autoExport($payment);
    }

    function it_doesnt_auto_export_shipment_due_export_object_not_found(
        PaymentInterface $payment,
        ShippingGatewayRepositoryInterface $shippingGatewayRepository,
        ShippingGatewayInterface $shippingGateway,
        OrderInterface $order,
        ShipmentInterface $shipment,
        ShippingExportRepositoryInterface $shippingExportRepository
    ): void {
        $shippingGatewayRepository->findOneByCode('byrd')->willReturn($shippingGateway);
        $shippingGateway->getConfig()->willReturn([
            'auto_export' => true,
        ]);

        $payment->getOrder()->willReturn($order);
        $order->getShipments()->willReturn(new ArrayCollection([$shipment->getWrappedObject()]));
        $shipment->getId()->willReturn(10);
        $shipment->getOrder()->willReturn($order);

        $shippingExportRepository->findOneBy([
            'shipment' => 10,
        ])->willReturn(null);

        $this->autoExport($payment);
    }

    function it_prevents_exporting_non_new_exports(
        PaymentInterface $payment,
        ShippingGatewayRepositoryInterface $shippingGatewayRepository,
        ShippingGatewayInterface $shippingGateway,
        OrderInterface $order,
        ShipmentInterface $shipment,
        ShippingExportRepositoryInterface $shippingExportRepository,
        ShippingExportInterface $shippingExport
    ): void {
        $shippingGatewayRepository->findOneByCode('byrd')->willReturn($shippingGateway);
        $shippingGateway->getConfig()->willReturn([
            'auto_export' => true,
        ]);

        $payment->getOrder()->willReturn($order);
        $order->getShipments()->willReturn(new ArrayCollection([$shipment->getWrappedObject()]));
        $shipment->getId()->willReturn(10);
        $shipment->getOrder()->willReturn($order);

        $shippingExportRepository->findOneBy([
            'shipment' => 10,
        ])->willReturn($shippingExport);

        $shippingExport->getState()->willReturn("exported");
        $shippingExport->getShipment()->willReturn($shipment);

        $shippingExport->getShippingGateway()->willReturn($shippingGateway);

        $this->autoExport($payment);
    }
}
