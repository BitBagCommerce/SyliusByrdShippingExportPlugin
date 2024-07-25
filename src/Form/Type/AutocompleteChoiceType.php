<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\SyliusByrdShippingExportPlugin\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class AutocompleteChoiceType extends AbstractType
{
    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
    }

    public function buildView(
        FormView $view,
        FormInterface $form,
        array $options,
    ): void {
        $view->vars['multiple'] = $options['multiple'];
        $view->vars['choice_name'] = $options['choice_name'];
        $view->vars['choice_value'] = $options['choice_value'];
        $view->vars['placeholder'] = $options['placeholder'];

        $view->vars['remote_criteria_type'] = 'contains';
        $view->vars['remote_criteria_name'] = 'sku';

        $view->vars['remote_url'] = $this->urlGenerator->generate(
            'sylius_byrd_shipping_export_plugin_filter_byrd_products',
        );

        $view->vars['load_edit_url'] = $this->urlGenerator->generate(
            'sylius_byrd_shipping_export_plugin_filter_byrd_products',
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired([
                'choice_name',
                'choice_value',
            ])
            ->setDefaults([
                'multiple' => false,
                'error_bubbling' => false,
                'placeholder' => '',
            ])
            ->setAllowedTypes('multiple', ['bool'])
            ->setAllowedTypes('choice_name', ['string'])
            ->setAllowedTypes('choice_value', ['string'])
            ->setAllowedTypes('placeholder', ['string'])
        ;
    }

    public function getParent(): string
    {
        return HiddenType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'sylius_resource_autocomplete_choice';
    }
}
