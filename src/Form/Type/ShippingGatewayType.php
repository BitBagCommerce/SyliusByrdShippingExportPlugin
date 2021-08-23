<?php

/*
 * This file was created by developers working at BitBag
 * Do you need more information about us and what we do? Visit our https://bitbag.io website!
 * We are hiring developers from all over the world. Join us and start your new, exciting adventure and become part of us: https://bitbag.io/career
*/

declare(strict_types=1);

namespace BitBag\SyliusByrdShippingExportPlugin\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class ShippingGatewayType extends AbstractType
{
    private const OPTION_STANDARD = 'standard';

    private const OPTION_EXPRESS = 'express';

    private const OPTION_ECONOMY = 'economy';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('api_key', TextType::class, [
                'label' => 'bitbag_sylius_byrd_shipping_export_plugin.ui.api_key',
            ])
            ->add('api_secret', TextType::class, [
                'label' => 'bitbag_sylius_byrd_shipping_export_plugin.ui.api_secret',
            ])
            ->add('shipping_option', ChoiceType::class, [
                'label' => 'bitbag_sylius_byrd_shipping_export_plugin.ui.shipping_option',
                'required' => true,
                'choices' => [
                    'bitbag_sylius_byrd_shipping_export_plugin.ui.shipping_option_type.standard' => self::OPTION_STANDARD,
                    'bitbag_sylius_byrd_shipping_export_plugin.ui.shipping_option_type.express' => self::OPTION_EXPRESS,
                    'bitbag_sylius_byrd_shipping_export_plugin.ui.shipping_option_type.economy' => self::OPTION_ECONOMY,
                ],
                'multiple' => false,
            ])
            ->add('auto_sku_matching', CheckboxType::class, [
                'label' => 'bitbag_sylius_byrd_shipping_export_plugin.ui.auto_sku_matching',
                'required' => false,
            ])
            ->add('auto_export', CheckboxType::class, [
                'label' => 'bitbag_sylius_byrd_shipping_export_plugin.ui.auto_export',
                'required' => false,
            ])
        ;
    }
}
