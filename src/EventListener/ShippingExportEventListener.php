<?php

/*
 * This file was created by developers working at BitBag
 * Do you need more information about us and what we do? Visit our https://bitbag.io website!
 * We are hiring developers from all over the world. Join us and start your new, exciting adventure and become part of us: https://bitbag.io/career
*/

declare(strict_types=1);

namespace BitBag\SyliusByrdShippingExportPlugin\EventListener;

use BitBag\SyliusByrdShippingExportPlugin\Api\Client\ByrdHttpClientInterface;
use BitBag\SyliusByrdShippingExportPlugin\Api\Exception\ByrdApiException;
use BitBag\SyliusShippingExportPlugin\Entity\ShippingExportInterface;
use BitBag\SyliusShippingExportPlugin\Entity\ShippingGatewayInterface;
use BitBag\SyliusShippingExportPlugin\Event\ExportShipmentEvent;
use BitBag\SyliusShippingExportPlugin\Repository\ShippingExportRepositoryInterface;
use BitBag\SyliusShippingExportPlugin\Repository\ShippingGatewayRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\ShipmentInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ShippingExportEventListener
{
    /** @var ByrdHttpClientInterface */
    private $byrdHttpClient;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var ShippingExportRepositoryInterface */
    private $shippingExportRepository;

    /** @var FlashBagInterface */
    private $flashBag;

    /** @var Filesystem */
    private $filesystem;

    /** @var TranslatorInterface */
    private $translator;

    /** @var string */
    private $shippingLabelsPath;

    /** @var ShippingGatewayRepositoryInterface */
    private $shippingGatewayRepository;

    public function __construct(
        ByrdHttpClientInterface $byrdHttpClient,
        EntityManagerInterface $entityManager,
        ShippingExportRepositoryInterface $shippingExportRepository,
        FlashBagInterface $flashBag,
        Filesystem $filesystem,
        TranslatorInterface $translator,
        ShippingGatewayRepositoryInterface $shippingGatewayRepository,
        string $shippingLabelsPath
    ) {
        $this->byrdHttpClient = $byrdHttpClient;
        $this->entityManager = $entityManager;
        $this->shippingExportRepository = $shippingExportRepository;
        $this->flashBag = $flashBag;
        $this->filesystem = $filesystem;
        $this->translator = $translator;
        $this->shippingLabelsPath = $shippingLabelsPath;
        $this->shippingGatewayRepository = $shippingGatewayRepository;
    }

    public function exportShipment(ExportShipmentEvent $exportShipmentEvent): void
    {
        /** @var ShippingExportInterface $shippingExport */
        $shippingExport = $exportShipmentEvent->getShippingExport();

        /** @var ShipmentInterface $shipment */
        $shipment = $shippingExport->getShipment();

        /** @var OrderInterface $order */
        $order = $shipment->getOrder();

        /** @var ShippingGatewayInterface $shippingGateway */
        $shippingGateway = $shippingExport->getShippingGateway();

        try {
            $this->byrdHttpClient->createShipment($order, $shippingGateway);
        } catch (ByrdApiException $e) {
            $shippingExport->setState('failed');
            $this->entityManager->flush();

            $exportShipmentEvent->addErrorFlash(
                sprintf('Byrd error for order %s: %s', $order->getNumber(), $e->getMessage())
            );

            return;
        }

        $exportShipmentEvent->addSuccessFlash();
        $exportShipmentEvent->exportShipment();
    }

    public function autoExport(PaymentInterface $payment): void
    {
        /** @var ShippingGatewayInterface|null $byrdGateway */
        $byrdGateway = $this->shippingGatewayRepository->findOneByCode('byrd');
        if ($byrdGateway === null) {
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

        if ($exportObject === null) {
            return;
        }

        if ($exportObject->getState() !== ShippingExportInterface::STATE_NEW) {
            return;
        }

        $event = new ExportShipmentEvent(
            $exportObject,
            $this->flashBag,
            $this->entityManager,
            $this->filesystem,
            $this->translator,
            $this->shippingLabelsPath
        );

        $this->exportShipment($event);
    }
}
