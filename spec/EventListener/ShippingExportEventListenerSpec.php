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
use BitBag\SyliusShippingExportPlugin\Repository\ShippingExportRepository;
use BitBag\SyliusShippingExportPlugin\Repository\ShippingExportRepositoryInterface;
use BitBag\SyliusShippingExportPlugin\Repository\ShippingGatewayRepositoryInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\ShipmentInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ShippingExportEventListenerSpec extends ObjectBehavior
{
    function let(
        ByrdHttpClientInterface $byrdHttpClient,
        EntityManagerInterface $entityManager,
        ShippingExportRepositoryInterface $shippingExportRepository,
        RequestStack $requestStack,
        SessionInterface $session,
        FlashBagInterface $flashBag,
        TranslatorInterface $translator,
        ShippingGatewayRepositoryInterface $shippingGatewayRepository
    ): void {
        $session->getBag('flashes')->willReturn($flashBag);
        $requestStack->getSession()->willReturn($session);
        $this->beConstructedWith(
            $byrdHttpClient,
            $entityManager,
            $shippingExportRepository,
            $requestStack,
            $translator,
            $shippingGatewayRepository
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
        RequestStack $requestStack,
        SessionInterface $session,
        FlashBagInterface $flashBag,
        ShippingExportRepository $shippingExportRepository,
        ResourceControllerEvent $exportShipmentEvent,
    ): void
    {
        $shippingExport->getShipment()->willReturn($shipment);
        $shippingExport->getShippingGateway()->willReturn($shippingGateway);
        $shipment->getOrder()->willReturn($order);

        $exportShipmentEvent->getSubject()->willReturn($shippingExport);

        $requestStack->getSession()->willReturn($session);
        $session->getBag('flashes')->willReturn($flashBag);
        $flashBag->add(
            'success',
            'bitbag.ui.shipment_data_has_been_exported'
        );

        $shippingExport->setState(ShippingExportInterface::STATE_EXPORTED);
        $shippingExport->setExportedAt(Argument::type(\DateTime::class));

        $this->exportShipment($exportShipmentEvent);
    }

    function it_adds_flash_on_failed_export(
        ByrdHttpClientInterface $byrdHttpClient,
        ShippingExportInterface $shippingExport,
        OrderInterface $order,
        ShipmentInterface $shipment,
        ShippingGatewayInterface $shippingGateway,
        EntityManagerInterface $entityManager,
        RequestStack $requestStack,
        SessionInterface $session,
        FlashBagInterface $flashBag,
        ResourceControllerEvent $exportShipmentEvent,
    ): void
    {
        $shippingExport->getShipment()->willReturn($shipment);
        $shippingExport->getShippingGateway()->willReturn($shippingGateway);
        $shippingExport->setState("failed")->shouldBeCalled();
        $entityManager->flush()->shouldBeCalled();
        $shipment->getOrder()->willReturn($order);

        $exportShipmentEvent->getSubject()->willReturn($shippingExport);

        $requestStack->getSession()->willReturn($session);
        $session->getBag('flashes')->willReturn($flashBag);
        $flashBag->add(
            'error',
            'Byrd error for order : '
        );

        $order->getNumber()->shouldBeCalled();

        $byrdHttpClient->createShipment($order, $shippingGateway)->willThrow(ByrdApiException::class);

        $this->exportShipment($exportShipmentEvent);
    }

    function it_auto_exports_shipment(
        PaymentInterface $payment,
        ShippingGatewayRepositoryInterface $shippingGatewayRepository,
        ShippingGatewayInterface $shippingGateway,
        OrderInterface $order,
        ShipmentInterface $shipment,
        ShippingExportRepositoryInterface $shippingExportRepository,
        ShippingExportInterface $shippingExport,
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
        $shippingExportRepository->add($shippingExport)->shouldBeCalled();

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
