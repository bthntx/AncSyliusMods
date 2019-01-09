<?php

namespace AppBundle\Command;


use Sylius\Component\Product\Model\ProductAssociationTypeInterface;
use Sylius\Component\Taxonomy\Model\TaxonInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class ImportTaxonsCommand extends ContainerAwareCommand
{
    private const URL = 'https://goo.gl/HKrR1L';
    private $locale = 'en_US';
    private $productFactory;
    private $productRepository;
    private $productManager;
    private $pricingFactory;
    private $associationFactory;
    private $associationRepository;
    private $associationTypeFactory;
    private $associationTypeRepository;
    private $taxonFactory;
    private $taxonRepository;
    private $taxonManager;
    private $channel;

    protected function configure()
    {
        $this
            ->setName('import:taxons')
            ->setDescription('Import taxons from URL');
//            ->addArgument(
//                'csvFilePath',
//                InputArgument::REQUIRED,
//                'Specify path to CSV file'
//            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->productFactory = $this->getContainer()->get('sylius.factory.product');
        $this->productRepository = $this->getContainer()->get('sylius.repository.product');
        $this->productManager = $this->getContainer()->get('sylius.manager.product');
        $productVariantFactory = $this->getContainer()->get('sylius.factory.product_variant');
        $productOptionsRepository = $this->getContainer()->get('sylius.repository.product_option');
        $this->pricingFactory = $this->getContainer()->get('sylius.factory.channel_pricing');
        $this->associationFactory = $this->getContainer()->get('sylius.factory.product_association');
        $this->associationRepository = $this->getContainer()->get('sylius.repository.product_association');
        $this->associationTypeFactory = $this->getContainer()->get('sylius.factory.product_association_type');
        $this->associationTypeRepository = $this->getContainer()->get('sylius.repository.product_association_type');
        $this->taxonFactory = $this->getContainer()->get('sylius.factory.taxon');
        $this->taxonRepository = $this->getContainer()->get('sylius.repository.taxon');
        $this->taxonManager = $this->getContainer()->get('sylius.manager.taxon');
        $this->channel = $this->getContainer()->get('sylius.context.channel')->getChannel();
        /* AssociationType */
        /** @var ProductAssociationTypeInterface $associationType */
        if (!$associationType = $this->associationTypeRepository->findOneBy(['code' => 'related_product'])) {
            $associationType = $this->associationTypeFactory->createNew();
            $associationType->setCode('related_product');
            $associationType->setName('Related Product');
            $this->associationTypeRepository->add($associationType);
        };
        /* CSV */
        //$csvFilePath = $input->getArgument('csvFilePath');
        $csv = new CsvIterator(self::URL);
        if ($csv->getFile() === false) {
            die(sprintf('CSV file not valid. path: %s'.PHP_EOL, self::URL));
        }
        $count = 0;
        foreach ($csv->parse() as $row) {
            $code = trim($row['Product Code']);
            if ($code == '') continue;
            $name = trim($row['Name']);

            /* Taxons - Tree */
            $taxonTreeArray = array_filter(array(
                    $row['Type'],
                    $row['Period'],
                    $row['Region'],
                    $row['width'],
                )
            );
            $this->createTaxonTree($taxonTreeArray,['Category','Period','Region','width']);

        }


    }


    private function createTaxonTree($taxonTreeArray,$parentTaxonsArray = ['Category','Period','Region','width'])
    {
        $parent = null;
        $taxonArray = [];
        $i=0;
        foreach ($taxonTreeArray as $taxonName) {
            $taxonSubList = explode('|', $taxonName);
            foreach ($taxonSubList as $row) {
                $taxonArray[$parentTaxonsArray[$i]][] = trim($row);
            }
            ++$i;
        }

        foreach ($taxonArray as  $parent => $taxonList) {
            $parent = $this->taxonRepository->findOneBySlug($this->cleanString($parent), $this->locale);
            foreach ($taxonList as $taxonName) {
                if ($taxon = $this->taxonRepository->findOneBySlug($this->cleanString($taxonName), $this->locale)) {
                    //dump($taxon);die;
                    continue;
                }
                echo $this->cleanString($taxonName)." taxon not exist \n";
                /** @var TaxonInterface $taxon */
                $taxon = $this->taxonFactory->createNew();
                $taxon->setCode($this->cleanString($taxonName));
                $taxon->setName($taxonName);
                $taxon->setSlug($this->cleanString($taxonName));
                if ($parent) {
                    $taxon->setParent($parent);
                }
                $this->taxonManager->persist($taxon);
            }
        }
        $this->taxonManager->flush();
    }


    private function cleanString($string)
    {
        return strtolower(str_replace([' '], '_', $string));
    }
}