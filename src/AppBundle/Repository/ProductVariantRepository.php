<?php
/**
 * Created by PhpStorm.
 * User: beathan
 * Date: 05.06.2018
 * Time: 20:50
 */

namespace AppBundle\Repository;


use Doctrine\ORM\QueryBuilder;
use Sylius\Bundle\CoreBundle\Doctrine\ORM\ProductVariantRepository as BaseRepository;

class ProductVariantRepository extends  BaseRepository
{
    /**
     * {@inheritdoc}
     */
    public function createListQueryBuilder(string $locale): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('o')
            ->addSelect('translation')
            ->addSelect('product_translation.name as productname')
            ->innerJoin('o.translations', 'translation', 'WITH', 'translation.locale = :locale')
            ->innerJoin('o.product', 'product')
            ->innerJoin('product.translations', 'product_translation', 'WITH', 'product_translation.locale = :locale')

            ->setParameter('locale', $locale)
        ;
        return $queryBuilder;
    }
}