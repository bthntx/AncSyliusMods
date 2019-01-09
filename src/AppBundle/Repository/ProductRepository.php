<?php

namespace AppBundle\Repository;

use AppBundle\Doctrine\ColumnHydrator;
use AppBundle\Entity\Taxon;
use Doctrine\ORM\QueryBuilder;
use Sylius\Bundle\CoreBundle\Doctrine\ORM\ProductRepository as BaseProductRepository;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductTaxon;


class ProductRepository extends BaseProductRepository
{

    public function findRandomProductsByTaxon(
        ChannelInterface $channel,
        $taxons,
        string $locale,
        array $sorting = [],
        int $count
    )
    {
        $products = [];
        /** @var Taxon[] $taxonList */
        $taxonList = $taxons;
        /** @var Taxon $taxonBase */
        $taxonBase = null;
        if (!\is_array($taxons)) {
            return null;
        }

        for ($i = 0; $i < $count; $i++) {
                $txns = $taxonList;
                $tmp_query = $this->createShopListQueryBuilderMultiTaxons($channel, $txns, $locale, $sorting);
                $max_amount = count($tmp_query->getQuery()->getResult());
                $tmp_query->setFirstResult(rand(0, $max_amount-$count));
                $tmp_query->setMaxResults($count);
                $product = $tmp_query->getQuery()->getResult();
                if (count($product) > 0) {
                    foreach ($product as $prow) {
                        $products[] = $prow->getId();
                    }
                }
                $product = null;
            if (count($products) >= $count) {
                break;
            }
        }

        $queryBuilder = $this->createQueryBuilder('products');
        $queryBuilder->andWhere('products.id IN (:productList)')->setParameter('productList', $products);
        $queryBuilder->setMaxResults($count);

        return $queryBuilder->getQuery()->getResult();
    }

    public function findSimilarProductsByLinkedTaxons(
        ChannelInterface $channel,
        $taxons,
        string $locale,
        array $sorting = [],
        int $count
    ) {
        //$this->_em->getConfiguration()->addCustomHydrationMode('COLUMN_HYDRATOR', ColumnHydrator::class);
        /** @var QueryBuilder[] $qbList */
        $qbList = [];
        $products = [];
        /** @var Taxon[] $taxonList */
        $taxonList = $taxons;
        /** @var Taxon $taxonBase */
        $taxonBase = null;
        if (!\is_array($taxons)) {
            return null;
        }
        $taxonCount = count($taxonList);
        /** @var Taxon $taxon */
        for ($i = 0; $i < $taxonCount; $i++) {
            if ($taxonList[$i]->getLinkedTaxons()->count() > 0) {
                $taxonBase = $taxonList[$i];
                unset($taxonList[$i]);
            }
        }
        if (!$taxonBase) {
            return false;
        }
        for ($i = 0; $i < $count; $i++) {
            foreach ($taxonBase->getLinkedTaxons() as $linkedTaxon) {
                $txns = array_merge([$linkedTaxon], $taxonList);
                $tmp_query = $this->createShopListQueryBuilderMultiTaxons($channel, $txns, $locale, $sorting);
                $max_amount = count($tmp_query->getQuery()->getResult());
                $tmp_query->setFirstResult(rand(0, $max_amount));
                $tmp_query->setMaxResults(1);
                $product = $tmp_query->getQuery()->getResult();
                if (count($product) > 0) {
                    $products[] = $product[0]->getId();
                }
                $product = null;
            }
            if (count($products) >= $count) {
                break;
            }
        }

        $queryBuilder = $this->createQueryBuilder('products');
        $queryBuilder->andWhere('products.id IN (:productList)')->setParameter('productList', $products);
        $queryBuilder->setMaxResults($count);

        return $queryBuilder->getQuery()->getResult();
    }

    public function createShopListQueryBuilderMultiTaxons(
        ChannelInterface $channel,
        $taxons,
        string $locale,
        array $sorting = []
    ): QueryBuilder {
        $this->_em->getConfiguration()->addCustomHydrationMode('COLUMN_HYDRATOR', ColumnHydrator::class);
        $needWidth = false;
        $lessWidth = 0;
        $subQuery = $this->_em->createQueryBuilder();
        $subQuery2 = $this->_em->createQueryBuilder();
        $subQuery->select('IDENTITY(pt.product)')->from(ProductTaxon::class, 'pt');
        if (\is_array($taxons)) {
            foreach ($taxons as $trow) {
                if ($trow->getCode() == 'belt_mounts') {
                    $needWidth = true;
                }
                if ($trow->getRoot()->getCode() == 'width') {
                    $lessWidth = (int)($trow->getCode());
                    $txRoot = $trow->getRoot();
                }
            }
            $updatedTaxonsList = $taxons;
            if ($needWidth && $lessWidth > 0) {
                //Need to popup width taxon from main filter
                $updatedTaxonsList = [];
                foreach ($taxons as $trow) {
                    if ($trow->getRoot()->getCode() != 'width') {
                        $updatedTaxonsList[] = $trow;
                    }
                }
            }

            $subQuery->
            where('pt.taxon IN (:taxon)')->setParameter('taxon', $updatedTaxonsList)
                ->groupBy('pt.product')->having('count(pt.taxon) = :len')->setParameter('len',
                    count($updatedTaxonsList));
        }

        $filteredProducts = $subQuery->getQuery()->getResult('COLUMN_HYDRATOR');

        if ($needWidth && $lessWidth > 0) {
            $subQuery2->select('IDENTITY(pt.product)')->from(ProductTaxon::class, 'pt')
            ->leftJoin('pt.taxon','tx')
            ->where('pt.product IN (:productList)')->setParameter('productList',$filteredProducts)
            ->andWhere('tx.root = :root AND tx.code< :width')->setParameter('root',$txRoot)->setParameter('width',$lessWidth);

            $filteredProducts = $subQuery2->getQuery()->getResult('COLUMN_HYDRATOR');
        }

        $queryBuilder = $this->createQueryBuilder('o')
            ->addSelect('translation')
            ->innerJoin('o.translations', 'translation', 'WITH', 'translation.locale = :locale')
            ->innerJoin('o.productTaxons', 'productTaxon')
            ->andWhere(':channel MEMBER OF o.channels')
            ->andWhere('o.enabled = true')
            ->addGroupBy('o.id')
            ->setParameter('locale', $locale)
            ->setParameter('channel', $channel);

        $queryBuilder->andWhere('o.id IN (:productList)')->setParameter('productList', $filteredProducts);
        // Grid hack, we do not need to join these if we don't sort by price
        if (isset($sorting['price'])) {
            $queryBuilder
                ->innerJoin('o.variants', 'variant')
                ->innerJoin('variant.channelPricings', 'channelPricing')
                ->andWhere('channelPricing.channelCode = :channelCode')
                ->setParameter('channelCode', $channel->getCode());
        }

        return $queryBuilder;

    }


}