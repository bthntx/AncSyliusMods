<?php

declare(strict_types=1);

namespace AppBundle\Form\Type\Shipping\Calculator;


use Sylius\Component\Currency\Context\CurrencyContextInterface;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;


final class PerWeightRateConfigurationType extends AbstractType
{

    private $currency;

    /**
     * PerWeightRateCalculator constructor.
     */
    public function __construct(CurrencyContextInterface $currencyContext)
    {
        $this->currency = $currencyContext->getCurrencyCode();
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder
            ->add('prices', CollectionType::class, [
                'entry_type' => WeightBracketType::class,
                'entry_options' => array('label' => false),
                'prototype' => true,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'entry_options' => ['currency' => $this->currency],
            ]);
    }


    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'data_class' => null,
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public
    function getBlockPrefix(): string
    {
        return 'app_shipping_calculator_per_weight_rate';
    }
}
