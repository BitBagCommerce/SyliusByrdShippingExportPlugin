<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\SyliusByrdShippingExportPlugin\EventListener;

use BitBag\SyliusByrdShippingExportPlugin\Api\Client\ByrdHttpClientInterface;
use BitBag\SyliusByrdShippingExportPlugin\Api\Exception\ByrdApiException;
use BitBag\SyliusShippingExportPlugin\Entity\ShippingExportInterface;
use BitBag\SyliusShippingExportPlugin\Entity\ShippingGatewayInterface;
use BitBag\SyliusShippingExportPlugin\Repository\ShippingExportRepositoryInterface;
use BitBag\SyliusShippingExportPlugin\Repository\ShippingGatewayRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\ShipmentInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ShippingExportEventListener
{
    /** @var ByrdHttpClientInterface */
    private $byrdHttpClient;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var ShippingExportRepositoryInterface */
    private $shippingExportRepository;

    /** @var RequestStack */
    private $requestStack;

    /** @var TranslatorInterface */
    private $translator;

    /** @var ShippingGatewayRepositoryInterface */
    private $shippingGatewayRepository;

    public function __construct(
        ByrdHttpClientInterface $byrdHttpClient,
        EntityManagerInterface $entityManager,
        ShippingExportRepositoryInterface $shippingExportRepository,
        RequestStack $requestStack,
        TranslatorInterface $translator,
        ShippingGatewayRepositoryInterface $shippingGatewayRepository,
    ) {
        $this->byrdHttpClient = $byrdHttpClient;
        $this->entityManager = $entityManager;
        $this->shippingExportRepository = $shippingExportRepository;
        $this->requestStack = $requestStack;
        $this->translator = $translator;
        $this->shippingGatewayRepository = $shippingGatewayRepository;
    }

    public function exportShipment(ResourceControllerEvent $exportShipmentEvent): void
    {
        /** @var ShippingExportInterface $shippingExport */
        $shippingExport = $exportShipmentEvent->getSubject();

        /** @var ShipmentInterface $shipment */
        $shipment = $shippingExport->getShipment();

        /** @var OrderInterface $order */
        $order = $shipment->getOrder();

        /** @var ShippingGatewayInterface $shippingGateway */
        $shippingGateway = $shippingExport->getShippingGateway();

        $flashBag = $this->requestStack->getSession()->getBag('flashes');

        try {
            $this->byrdHttpClient->createShipment($order, $shippingGateway);
        } catch (ByrdApiException $e) {
            $shippingExport->setState('failed');
            $this->entityManager->flush();
            $flashBag->add(
                'error',
                sprintf('Byrd error for order %s: %s', $order->getNumber(), $e->getMessage()),
            );

            return;
        }

        $message = $this->translator->trans('bitbag.ui.shipment_data_has_been_exported');
        $flashBag->add(
            'success',
            $message,
        );

        $this->export($shippingExport);
    }

    public function autoExport(PaymentInterface $payment): void
    {
        /** @var ShippingGatewayInterface|null $byrdGateway */
        $byrdGateway = $this->shippingGatewayRepository->findOneByCode('byrd');
        if (null === $byrdGateway) {
            return;
        }

        /** @var array $config */
        $config = $byrdGateway->getConfig();
        if (!isset($config['auto_export']) || !$config['auto_export']) {
            return;
        }

        /** @var OrderInterface $order */
        $order = $payment->getOrder();

        /** @var ShipmentInterface $shipment */
        $shipment = $order->getShipments()->first();

        /** @var ShippingExportInterface|null $exportObject */
        $exportObject = $this->shippingExportRepository->findOneBy([
            'shipment' => $shipment->getId(),
        ]);

        if (null === $exportObject) {
            return;
        }

        if (ShippingExportInterface::STATE_NEW !== $exportObject->getState()) {
            return;
        }

        $exportShipmentEvent = new ResourceControllerEvent($exportObject);
        $this->exportShipment($exportShipmentEvent);
    }

    public function export(ShippingExportInterface $shippingExport): void
    {
        $shippingExport->setState(ShippingExportInterface::STATE_EXPORTED);
        $shippingExport->setExportedAt(new \DateTime());
        $this->shippingExportRepository->add($shippingExport);
    }
}
