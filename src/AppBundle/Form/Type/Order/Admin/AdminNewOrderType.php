<?php
/**
 * Created by PhpStorm.
 * User: beathan
 * Date: 04.06.2018
 * Time: 15:20
 */

namespace AppBundle\Form\Type\Order\Admin;

use Sylius\Bundle\AddressingBundle\Form\Type\AddressType;
use Sylius\Bundle\CoreBundle\Doctrine\ORM\CustomerRepository;
use Sylius\Bundle\MoneyBundle\Form\Type\MoneyType;
use Sylius\Bundle\PaymentBundle\Form\Type\PaymentMethodChoiceType;
use Sylius\Bundle\ShippingBundle\Form\Type\ShippingMethodChoiceType;
use Sylius\Component\Core\Model\Customer;
use Sylius\Component\Core\Repository\CustomerRepositoryInterface;
use Sylius\Component\Currency\Context\CurrencyContextInterface;
use Sylius\Component\Order\Model\Order;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Valid;


class AdminNewOrderType extends AbstractType
{
    /** @var CustomerRepositoryInterface $customerRepository */
    protected $customerRepository;
    private $currency;

    /**
     * AdminNewOrderType constructor.
     */
    public function __construct(CustomerRepositoryInterface $customerRepo, CurrencyContextInterface $currencyContext)
    {
        $this->customerRepository = $customerRepo;
        $this->currency = $currencyContext->getCurrencyCode();
    }


    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Customer $customer */
        $customer = $this->customerRepository->find($options['customerId']);
        $address = null;
        if ($customer) {
            if ($customer->getAddresses()->count() > 0) {
                $address = $customer->getAddresses()->last();
            }
        } else {

        }
        $builder
            ->add('notes', TextareaType::class)
            ->add('productSearch', TextType::class, ['mapped' => false])
            ->add('itemsCollection', CollectionType::class, [
                'mapped' => false,
                'entry_type' => AdminOrderItemType::class,
                'prototype' => true,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'attr' => ['item_class' => 'five fields'],
            ])
            ->add('shippingAddress', AddressType::class, [
                'shippable' => true,
                'constraints' => [new Valid()],
                'data' => $address,
            ])
            ->add('shipment_price', MoneyType::class, ['mapped' => false,'currency'=>$this->currency])
            ->add('shipment_override', CheckboxType::class, ['mapped' => false])
            ->add('discount', MoneyType::class, ['mapped' => false,'currency'=>$this->currency])
            ->add('shipment_type', ShippingMethodChoiceType::class, ['mapped' => false])
            ->add('payment_type', PaymentMethodChoiceType::class, ['mapped' => false])
            ->add('customer', EntityType::class, [
                'class' => Customer::class,
                'choice_value' => function (Customer $entity = null) {
                    return $entity ? $entity->getId() : 0;
                },
                'query_builder' => function (CustomerRepository $er) use ($options) {
                    return $er->createQueryBuilder('u')
                        ->where('u.id = :id')->setParameter('id', $options['customerId']);
                },
            ]);

    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'sylius_admin_create_new_order';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => Order::class,
                'customerId' => 0,
                'allow_extra_fields' => true,
            ]);
    }
}