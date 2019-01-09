<?php
/**
 * Created by PhpStorm.
 * User: beathan
 * Date: 27.06.2018
 * Time: 17:18
 */

namespace AppBundle\Form\Extension;


use AppBundle\Entity\Taxon;
use Doctrine\ORM\EntityRepository;
use Sylius\Bundle\TaxonomyBundle\Form\Type\TaxonType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

class TaxonTypeExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Adding new fields works just like in the parent form type.
        $builder->add('linkedTaxons', EntityType::class, [
            'multiple' =>true,
            'class' => Taxon::class,
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('t')->where('t.root = 1')
                    ->orderBy('t.code', 'ASC');
            },
            'attr' => ['class'=>'XXX','style'=>'height: 600px;'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType(): string
    {
        return TaxonType::class;
    }

}