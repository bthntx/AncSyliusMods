<?php

declare(strict_types=1);

namespace AppBundle\Form\Type\Shipping\Calculator;

use Sylius\Bundle\MoneyBundle\Form\Type\MoneyType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

final class WeightBracketType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('weight', IntegerType::class, [
                'label' => 'sylius.form.shipping_calculator.per_weight_rate_configuration.gramms',
                'constraints' => [
                    new NotBlank(['groups' => ['sylius']]),
                    new Type(['type' => 'integer', 'groups' => ['sylius']]),
                ],
            ])
            ->add('amount', MoneyType::class, [
                'label' => 'sylius.form.shipping_calculator.per_weight_rate_configuration.cost',
                'constraints' => [
                    new NotBlank(['groups' => ['sylius']]),
                    new Type(['type' => 'integer', 'groups' => ['sylius']]),
                ],
                'currency' => $options['currency'],

            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'data_class' => null,
                'currency' => '',
            ])

        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'sylius_shipping_calculator_per_weight_bracket';
    }
}
