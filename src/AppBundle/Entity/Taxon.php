<?php
/**
 * Created by PhpStorm.
 * User: beathan
 * Date: 27.06.2018
 * Time: 15:43
 */

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sylius\Component\Core\Model\Taxon  as BaseTaxon;
use Sylius\Component\Taxonomy\Model\TaxonInterface;

class Taxon extends BaseTaxon
{

    /**
     * @var \Sylius\Component\Core\Model\Taxon[]|Collection $linkedTaxons
     */
    private $linkedTaxons;

    /**
     * Taxon constructor.
     * @param Collection|BaseTaxon[] $linkedTaxons
     */
    public function __construct()
    {
        parent::__construct();
        $this->linkedTaxons = new ArrayCollection();
    }

    /**
     * @return Collection|BaseTaxon[]
     */
    public function getLinkedTaxons()
    {
        return $this->linkedTaxons;

    }

    public function getSubRoot(): ?TaxonInterface
    {
        $result = $this->getAncestors();

        return ($result->count()>=2) ? $result->get($result->count()-2): null;
    }


    /**
     * @param Collection|BaseTaxon[] $linkedTaxons
     */
    public function setLinkedTaxons($linkedTaxons): void
    {
        $this->linkedTaxons = $linkedTaxons;
    }



}