<?php

/*
 * This file was created by developers working at BitBag
 * Do you need more information about us and what we do? Visit our https://bitbag.io website!
 * We are hiring developers from all over the world. Join us and start your new, exciting adventure and become part of us: https://bitbag.io/career
*/

declare(strict_types=1);

namespace BitBag\SyliusByrdShippingExportPlugin\Form\Type;

use BitBag\SyliusByrdShippingExportPlugin\Repository\ByrdProductMappingRepositoryInterface;
use Sylius\Bundle\ProductBundle\Form\Type\ProductAutocompleteChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ByrdProductMappingType extends AbstractType
{
    /** @var ByrdProductMappingRepositoryInterface */
    private $byrdProductMappingRepository;

    /** @var TranslatorInterface */
    private $translator;

    public function __construct(
        ByrdProductMappingRepositoryInterface $byrdProductMappingRepository,
        TranslatorInterface $translator
    ) {
        $this->byrdProductMappingRepository = $byrdProductMappingRepository;
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('product', ProductAutocompleteChoiceType::class, [
                'label' => 'bitbag_sylius_byrd_shipping_export_plugin.ui.sylius_product',
                'required' => true,
            ])
            ->add('byrdProductSku', AutocompleteChoiceType::class, [
                'label' => 'bitbag_sylius_byrd_shipping_export_plugin.ui.byrd_product_sku',
                'choice_name' => 'name',
                'choice_value' => 'sku',
                'required' => true,
            ])
            ->addEventListener(FormEvents::SUBMIT, function (FormEvent $event): void {
                if (!$event->getData()->getProduct()) {
                    $event->getForm()->addError(new FormError(
                        $this->translator->trans(
                            'bitbag_sylius_byrd_shipping_export_plugin.ui.form.error.product_is_required'
                        )
                    ));

                    return;
                }

                if ($event->getData()->getByrdProductSku() === null || $event->getData()->getByrdProductSku() === '') {
                    $event->getForm()->addError(new FormError(
                        $this->translator->trans(
                            'bitbag_sylius_byrd_shipping_export_plugin.ui.form.error.sku_is_required'
                        )
                    ));

                    return;
                }

                $existingMapping = $this->byrdProductMappingRepository->findOneBy([
                    'product' => $event->getData()->getProduct()->getId()
                ]);
                if ($existingMapping && $existingMapping->getId() !== $event->getForm()->getData()->getId()) {
                    $event->getForm()->addError(new FormError(
                        $this->translator->trans(
                            'bitbag_sylius_byrd_shipping_export_plugin.ui.form.error.product_already_in_use'
                        )
                    ));

                    return;
                }

                $existingMapping = $this->byrdProductMappingRepository->findOneBy([
                    'byrdProductSku' => $event->getData()->getByrdProductSku()
                ]);
                if ($existingMapping && $existingMapping->getId() !== $event->getForm()->getData()->getId()) {
                    $event->getForm()->addError(new FormError(
                        $this->translator->trans(
                            'bitbag_sylius_byrd_shipping_export_plugin.ui.form.error.sku_already_in_use'
                        )
                    ));
                }
            })
        ;
    }
}
