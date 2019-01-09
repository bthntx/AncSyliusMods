<?php
/**
 * Created by PhpStorm.
 * User: beathan
 * Date: 04.06.2018
 * Time: 19:55
 */

namespace AppBundle\Form\Type\Order\Admin;


use AppBundle\Form\Model\OrderItemRow;
use Sylius\Bundle\MoneyBundle\Form\Type\MoneyType;
use Sylius\Component\Currency\Context\CurrencyContextInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdminOrderItemType extends AbstractType
{
    private $currency;

    /**
     * AdminOrderItemType constructor.
     * @param $currency
     */
    public function __construct(CurrencyContextInterface $currencyContext)
    {
        $this->currency = $currencyContext->getCurrencyCode();
    }


    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code',TextType::class)
            ->add('name',TextType::class)
            ->add('quantity',IntegerType::class,['attr'=>['class'=>'calcRow']])
            ->add('price',MoneyType::class,['currency'=>$this->currency,'attr'=>['class'=>'calcRow']])
            ->add('total',MoneyType::class,['currency'=>$this->currency]);

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => OrderItemRow::class,
            ]);
    }


}