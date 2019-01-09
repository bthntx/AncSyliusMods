<?php
/**
 * Created by PhpStorm.
 * User: beathan
 * Date: 27.06.2018
 * Time: 15:43
 */

namespace AppBundle\Entity;

use Sylius\Component\Core\Model\Product as BaseProduct;

class Product extends BaseProduct
{

    public function implodedTaxons(
        array $taxons = ['Category' => 1, 'Period' => 1, 'Gender' => 1, 'Culture' => 1, 'Width' => 1]
    ): String {
        $result = null;
        $taxonsUsed = ['Culture'=>1,'Gender'=>1,];
        foreach ($this->productTaxons as $taxon) {
            if ($taxon->getTaxon()->getChildren()->count()>0 || array_key_exists($taxon->getTaxon()->getRoot()->getName(), $taxonsUsed)) {continue;}
            if (array_key_exists($taxon->getTaxon()->getRoot()->getName(),
                    $taxons) ) {
                if ($taxon->getTaxon()->getRoot()->getName() == 'Period' && $taxon->getTaxon()->getParent()) {
                    $result[] = $taxon->getTaxon()->getParent()->getSlug();
                } else {
                    $result[] = $taxon->getTaxon()->getSlug();
                }
                $taxonsUsed[$taxon->getTaxon()->getRoot()->getName()] = 1;
            }
        }

        return implode('|', $result ?? ['Home']);
    }

    public function getTaxonByRoot(string $taxonCode)
    {
        foreach ($this->productTaxons as $taxon) {
            if ($taxon->getTaxon()->getRoot()->getCode() == $taxonCode) {
                return $taxon->getTaxon();
            }
        }

    }

}