<?php
/**
 * Created by PhpStorm.
 * User: beathan
 * Date: 19.05.2018
 * Time: 17:01
 */

namespace AppBundle\Repository;


use Doctrine\DBAL\Connection;
use Sylius\Bundle\TaxonomyBundle\Doctrine\ORM\TaxonRepository as BaseTaxonRepository;
use Sylius\Component\Taxonomy\Model\Taxon;


class TaxonRepository extends BaseTaxonRepository
{


    public function findBySlug(string $slug, string $locale)
    {
        $slugs = explode('|', $slug);

        //  foreach ($slugs as &$row) $row='\''.$row.'\'';

        $result = $this->createQueryBuilder('o')
            ->addSelect('translation')
            ->innerJoin('o.translations', 'translation')
            ->andWhere('translation.slug IN (:slugs)')
            ->andWhere('translation.locale = :locale')
            ->setParameter('slugs', $slugs, Connection::PARAM_STR_ARRAY)
            ->setParameter('locale', $locale);

        $result = $result->getQuery()->getResult();
        if (count($result) > 0) {
            return $result;
        }

        return null;

    }

    public function listRootTaxons(?string $locale)
    {
        $result = $this->createQueryBuilder('o');
        $result->where('o.level = 0 ')
        ->addSelect('translation')
        ->innerJoin('o.translations', 'translation')
        ->andWhere('translation.locale = :locale')
        ->setParameter('locale', $locale);
        $result = $result->getQuery()->getResult();
        if (count($result) == 0) {
            return null;
        }
        return $result;

        $root = new Taxon();
        foreach ($result as $row) {
            $root->addChild($row);
        }



    }


}