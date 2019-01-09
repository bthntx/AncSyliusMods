<?php

namespace AppBundle\Command;

use Sylius\Component\Core\Model\ChannelPricingInterface;
use Sylius\Component\Core\Model\Product;
use Sylius\Component\Core\Model\ProductVariant;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Product\Factory\ProductVariantFactoryInterface;
use Sylius\Component\Product\Generator\ProductVariantGeneratorInterface;
use Sylius\Component\Product\Model\ProductAssociationInterface;
use Sylius\Component\Product\Model\ProductAssociationTypeInterface;
use Sylius\Component\Product\Model\ProductVariantTranslation;
use Sylius\Component\Taxonomy\Model\TaxonInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class CsvIterator
{
    const DELIM = ",";
    const QUOTE = '"';
    protected $file;
    protected $rows = array();

    public function __construct($file)
    {
        $this->file = fopen($file, 'r');
    }

    public function getFile()
    {
        return $this->file;
    }

    public function parse()
    {
        $headers = array_map('trim', fgetcsv($this->file, 4096, self::DELIM, self::QUOTE));
        while (!feof($this->file)) {
            $row = array_map('trim', (array)fgetcsv($this->file, 4096, self::DELIM, self::QUOTE));
            if (count($headers) !== count($row)) {
                continue;
            }
            $this->rows[] = array_combine($headers, $row);
        }

        return $this->rows;
    }
}

class ImportProductsCommand extends ContainerAwareCommand
{
    private const URL = 'https://goo.gl/HKrR1L';
    //private const URL = 'https://docs.google.com/spreadsheets/d/e/2PACX-1vQfITbZM9WUSm3iUlyH0Ab4O4SK5iVZufmUazyLyTRGzU5mtJv2hh8LNU9BUnwQlPNTAbSEYbLv6rzg/pub?output=csv&t=122';
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
            ->setName('import:products')
            ->setDescription('Import products from URL')
            ->addOption(
                'overwriteImages',
                'i',
                InputOption::VALUE_NONE,
                'Overwrite images for product or not'
            )
            ->addOption(
                'onlyNew',
                'new',
                InputOption::VALUE_NONE,
                'add only notexisting products'
            )
            ->addOption(
                'testCodes',
                't',
                InputOption::VALUE_NONE,
                'Check duplicate codes'
            );

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->productFactory = $this->getContainer()->get('sylius.factory.product');
        $this->productRepository = $this->getContainer()->get('sylius.repository.product');
        $taxCategory = $this->getContainer()->get('sylius.repository.tax_category')->findByName('Sale Tax Tx')[0];
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
//        if (!$associationType = $this->associationTypeRepository->findOneBy(['code' => 'related_product'])) {
//            $associationType = $this->associationTypeFactory->createNew();
//            $associationType->setCode('related_product');
//            $associationType->setName('Related Product');
//            $this->associationTypeRepository->add($associationType);
//        };
        /* CSV */
        //$csvFilePath = $input->getArgument('csvFilePath');
        $csv = new CsvIterator(self::URL);
        if ($csv->getFile() === false) {
            die(sprintf('CSV file not valid. path: %s'.PHP_EOL, self::URL));
        }
        $count = 0;
        $duplicateCodes = [];
        $duplicateSlugs = [];

        if ($input->getOption('testCodes')) {
            foreach ($csv->parse() as $row) {
                $code = trim($row['Product Code']);
                if (array_key_exists($code, $duplicateCodes)) {
                    $duplicateCodes[$code] = $code;
                    if ($code != '') {
                        $output->writeln("Duplicate Code:: ".$code);
                    }
                    continue;
                }

                if (array_key_exists($row['Slug'], $duplicateSlugs)) {
                    $duplicateSlugs[$row['Slug']] = $row['Slug'];
                    if ($row['Slug'] != '') {
                        $output->writeln("Duplicate Slug:: ".$row['Slug']);
                    }
                    continue;
                }

                $duplicateCodes[$code] = $code;
                $duplicateSlugs[$row['Slug']] = $row['Slug'];


            }
            die;
        }


        $duplicateCodes = [];
        $duplicateSlugs = [];

        foreach ($csv->parse() as $row) {
            $code = trim($row['Product Code']);
            if ($code == '') {
                continue;
            }
            if (array_key_exists($code, $duplicateCodes)) {
                $duplicateCodes[$code] = $code;
                echo "Duplicate Code! $code \n";
                continue;
            }

            if (array_key_exists($row['Slug'], $duplicateSlugs)) {
                $duplicateSlugs[$row['Slug']] = $row['Slug'];
                echo "Duplicate Slug! ".$row['Slug']." \n";
                continue;
            }

            $name = trim($row['Name']);
            /* Product - load or create */
            /** @var Product $product */
            if (!$product = $this->productRepository->findOneByCode($code, $this->locale)) {
                if ($row['PriceSP'] != '' || $row['PriceGP'] != '') {
                    $product = $this->productFactory->createWithVariant();
                } else {
                    $product = $this->productFactory->createNew();
                }
            } else {
                if ($input->getOption('onlyNew')) {
                    continue;
                }
            };
            //$output->writeln($product->getCode());

            $duplicateCodes[$code] = $code;
            $duplicateSlugs[$row['Slug']] = $row['Slug'];
            $product->setCode($code); // Both Product and Variant needs a valid 'Code'
            $product->setName($name);
            $product->setEnabled((int)abs($row['Hidden'] - 1));


            $description = str_replace("<br />", "\n", $row['Full Description']);
            $description = str_replace("<br/>", "\n", $description);
            $description = str_replace("</li>", "\n", $description);
            $description = str_replace("<br>", "\n", $description);
            $description = str_replace("</p>", "\n", $description);
            $description = str_replace("</ul>", "\n", $description);


            $product->setDescription(strip_tags($description));
            $product->setSlug($row['Slug']);
            $product->setVariantSelectionMethod($product::VARIANT_SELECTION_CHOICE);

            $product->addChannel($this->channel);

            /* Variant */

            if ($row['Enabled']) {
                if ($row['PriceSP'] != '' || $row['PriceGP'] != '') {
                    $product->addOption($productOptionsRepository->findOneBy(['code' => 'plating']));

                    /** @var ProductVariantGeneratorInterface $variantGenerator */
                    $variantGenerator = $this->getContainer()->get('sylius.generator.product_variant');
                    $variantGenerator->generate($product);
                    $rowTitle = ['no_plating' => 'PriceNP', 'silver_plating' => 'PriceSP', 'gold_plating' => 'PriceGP'];
                    $product_variants = $product->getVariants();
                    /** @var ProductVariant $variant */
                    foreach ($product_variants as $variant) {
                        $code_modifier = ($variant->getOptionValues()[0]);
                        $translation = new ProductVariantTranslation();
                        $translation->setLocale($this->locale);
                        $variant->addTranslation($translation);
                        $variant->setCode($code.'_base');
                        $variant->setWidth((int)$row['width']);
                        $variant->setHeight((int)$row['length']);
                        $variant->setWeight((int)$row['weight']);
                        $variant->setTaxCategory($taxCategory);
                        $priceRow = $rowTitle['no_plating'];
                        if ($code_modifier) {
                            $code_modifier = $code_modifier->getCode();
                            $variant->setCode($code.'_'.$code_modifier);
                            $priceRow = $rowTitle[$code_modifier];
                            if ($row[$priceRow] == '') {
                                $product->removeVariant($variant);
                                continue;
                            }
                            /* Pricing - set per channel (only one in our case) */
                            /** @var ChannelPricingInterface $channelPricing */
                            if (!$channelPricing = $variant->getChannelPricingForChannel($this->channel)) {
                                $channelPricing = $this->pricingFactory->createNew();
                                $variant->addChannelPricing($channelPricing);

                            };
                            $channelPricing->setChannelCode($this->channel->getCode());
                            //$channelPricing->setPrice($this->cleanPrice($row[$priceRow]));
                            $channelPricing->setPrice($row[$priceRow] * 100);
                            //$output->writeln($priceRow.'-'.$row[$priceRow]);
                        } else {
                            $product->removeVariant($variant);
                        }

                    }
                } else {
                    /** @var ProductVariantFactoryInterface $productVariantFactory * */
                    $productVariantFactory = $this->getContainer()->get('sylius.factory.product_variant');
                    /** @var ProductVariantInterface $productVariant */
                    $productVariant = $product->getVariants()->first();
                    if (!$productVariant) {
                        $productVariant = $productVariantFactory->createNew();
                        $this->getContainer()->get('sylius.manager.product_variant')->persist($productVariant);
                    }
                    $productVariant->setCode($code);
                    $productVariant->setWidth((int)$row['width']);
                    $productVariant->setHeight((int)$row['length']);
                    $productVariant->setWeight((int)$row['weight']);
                    $this->productManager->persist($product);
                    $productVariant->setProduct($product);
                    $productVariant->setTaxCategory($taxCategory);


                    /** @var ChannelPricingInterface $channelPricing */
                    if (!$channelPricing = $productVariant->getChannelPricingForChannel($this->channel)) {
                        $channelPricing = $this->pricingFactory->createNew();
                        $productVariant->addChannelPricing($channelPricing);

                    };
                    $channelPricing->setChannelCode($this->channel->getCode());
                    $channelPricing->setPrice($row['PriceNP'] * 100);

                    /** @var RepositoryInterface $productVariantRepository */
                    $productVariantRepository = $this->getContainer()->get('sylius.repository.product_variant');
                    $productVariantRepository->add($productVariant);

                }
            } else {
                $variants = $product->getVariants();
                foreach ($variants as $variant) {
                    $product->removeVariant($variant);
                }
            }


//            /* Taxons - Tree */
//            $taxonTreeArray = array_filter(array(
//                    $row['Type'],
//                    $row['Period'],
//                    $row['Region'],
//                )
//            );
//            $this->createTaxonTree($taxonTreeArray);
//            /* Taxons - Main */
            $taxon = $this->taxonRepository->findOneBySlug($this->cleanString($row['Type']), $this->locale);

            if ($product->getMainTaxon() != $taxon) {
                $product->setMainTaxon($taxon);
            }
            $taxonsList = $product->getTaxons();
            if ($row['width'] != '') {
                $taxonWidths = explode('|', $row['width']);
                foreach ($taxonWidths as $region) {
                    $taxonWidth = $this->taxonRepository->findOneBySlug($this->cleanString(trim($region)),
                        $this->locale);
                    if ($taxonWidth && !$taxonsList->contains($taxonWidth)) {
                        /** @var ProductTaxonInterface $productTaxon */
                        $productTaxon = $this->getContainer()->get('sylius.factory.product_taxon')->createNew();
                        $productTaxon->setTaxon($taxonWidth);
                        $productTaxon->setProduct($product);
                        $product->addProductTaxon($productTaxon);
                    }
                }
            }
            if ($row['Gender'] != 'x') {
                if ($row['Gender'] == '') {
                    $row['Gender'] = 'm|f';
                }
                if ($row['Gender'] != '') {
                    $taxonRegions = explode('|', $row['Gender']);
                    foreach ($taxonRegions as $region) {
                        $taxonRegion = $this->taxonRepository->findOneBySlug($this->cleanString(trim($region)),
                            $this->locale);
                        if ($taxonRegion && !$taxonsList->contains($taxonRegion)) {
                            /** @var ProductTaxonInterface $productTaxon */
                            $productTaxon = $this->getContainer()->get('sylius.factory.product_taxon')->createNew();
                            $productTaxon->setTaxon($taxonRegion);
                            $productTaxon->setProduct($product);
                            $product->addProductTaxon($productTaxon);
                        }
                    }
                }
            }
            if ($row['Region'] != '') {
                $taxonRegions = explode('|', $row['Region']);
                foreach ($taxonRegions as $region) {
                    $taxonRegion = $this->taxonRepository->findOneBySlug($this->cleanString(trim($region)),
                        $this->locale);
                    if ($taxonRegion && !$taxonsList->contains($taxonRegion)) {
                        /** @var ProductTaxonInterface $productTaxon */
                        $productTaxon = $this->getContainer()->get('sylius.factory.product_taxon')->createNew();
                        $productTaxon->setTaxon($taxonRegion);
                        $productTaxon->setProduct($product);
                        $product->addProductTaxon($productTaxon);
                    }
                }
            }
            if ($row['Period'] != '') {
                $taxonPeriods = explode('|', $row['Period']);
                foreach ($taxonPeriods as $period) {
                    $taxonsList = $product->getTaxons();
                    $taxonPeriod = $this->taxonRepository->findOneBySlug($this->cleanString(trim($period)),
                        $this->locale);
                    if ($taxonPeriod) {
                        $taxonPeriodParentsTree = $taxonPeriod->getAncestors();
                        $taxonPeriodParentsTree->add($taxonPeriod);
                        foreach ($taxonPeriodParentsTree as $parent) {
                            if (!$taxonsList->contains($parent)) {
                                echo $parent->getName()."\n";
                                /** @var ProductTaxonInterface $productTaxon2 */
                                $productTaxon2 = $this->getContainer()->get('sylius.factory.product_taxon')->createNew();
                                $productTaxon2->setTaxon($parent);
                                $productTaxon2->setProduct($product);
                                $product->addProductTaxon($productTaxon2);
                            }
                        }
                        // && !$taxonsList->contains($taxonPeriod)) {

                    }
                }
            }
            echo "-----------------------\n";
            $taxonParentsTree = $taxon->getAncestors();
            $taxonParentsTree->add($taxon);
            foreach ($taxonParentsTree as $parent) {
                if (!$taxonsList->contains($parent)) {
                    /** @var ProductTaxonInterface $productTaxon */
                    $productTaxon = $this->getContainer()->get('sylius.factory.product_taxon')->createNew();
                    $productTaxon->setTaxon($parent);
                    $productTaxon->setProduct($product);
                    $product->addProductTaxon($productTaxon);
                }
            }
            /* Image */
            if ($input->getOption('overwriteImages')) {
                $private_dir = dirname(__DIR__.'/../../../../').'/private/';
                $import_dir = $private_dir.'ProductImages/';
                $imageUrlList = explode(',', $row['udfWebImage']);

                $oldImgList = $product->getImages();
                foreach ($oldImgList as $item) {
                    $product->removeImage($item);
                }
                foreach ($imageUrlList as $_row) {
                    if ($_row == '') {
                        continue;
                    }
                    $output->writeln('From:: '.$_row);
                    if (strpos($_row,'http://' ) !== false || strpos($_row,'https://') !== false) {
                        $fileName = explode('/', $_row);
                        $fileName = array_pop($fileName);
                        if (strpos($fileName,'?')!==false) $fileName = substr($fileName,0,strpos($fileName,'?'));
                        $image = file_get_contents($_row);
                        file_put_contents($private_dir.'ProductImages/'.$fileName, $image);
                        $output->writeln('Image:: '.$fileName);
                        $_row = $fileName;
                    }
                    $imageUrl = $import_dir.$_row;
                    $enlImageUrl = $import_dir.explode('.', $_row)[0].'_enl.'.explode('.', $_row)[1];
                    if (file_exists($enlImageUrl)) {
                        continue;
                    }
                    if (file_exists($imageUrl)) {
                        $product->addImage($this->getImage($imageUrl));
                        $output->writeln('Image:: '.$imageUrl);
                    } else {
                        $output->writeln('Image not found:: '.$imageUrl);
                    }
                }
            }
            $output->writeln($product->getCode());
            /* Associations */
//            $associatedProductCodes = array_filter(array(
//                    $row['Related Product 1'],
//                    $row['Related Product 2'],
//                    $row['Related Product 3'],
//                    $row['Related Product 4'],
//                    $row['Related Product 5'],
//                )
//            );
            /** @var ProductAssociationInterface $productAssociation */
//            $productAssociation = $this->associationFactory->createNew();
//            $productAssociation->setType($associationType);
//            foreach ($associatedProductCodes as $associatedProductCode) {
//                if ($associatedProduct = $this->productRepository->findOneByCode($associatedProductCode)) {
//                    $productAssociation->addAssociatedProduct($associatedProduct);
//                }
//            }
//            $product->addAssociation($productAssociation);
//            $this->associationRepository->add($productAssociation);

            /* Saving */
            try {
                $this->productManager->persist($product);
                if (0 === ++$count % 1) {
                    $this->productManager->flush();
                    //$this->productManager->clear();
                    //gc_collect_cycles();
                }
            } catch (\Exception $exception) {
                dump($exception);
                die;
            }


        }
        try {
            $this->productManager->flush();
            //$this->productManager->clear();
            //gc_collect_cycles();
        } catch (\Exception $exception) {
            dump($exception);
            die;
        }


    }


    private function createTaxonTree($taxonTreeArray)
    {
        $parent = null;
        $taxonArray = [];
        foreach ($taxonTreeArray as $taxonName) {
            $taxonSubList = explode(';', $taxonName);
            foreach ($taxonSubList as $row) {
                $taxonArray[] = trim($row);
            }
        }
        foreach ($taxonArray as $taxonName) {
            if ($taxon = $this->taxonRepository->findOneBySlug($this->cleanString($taxonName), $this->locale)) {
                $parent = $taxon;
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
            $parent = $taxon;
            $this->taxonManager->persist($taxon);
        }
        $this->taxonManager->flush();


    }

    private function cleanPrice($price)
    {
        return (int)str_replace([
            utf8_decode("£"),
            utf8_decode("."),
        ], '', $price);
    }

    private function getImage($imageUrl)
    {
        if (strpos($imageUrl, 'http') === false) {
            $fileName = substr($imageUrl, strrpos($imageUrl, '/') + 1);
        } else {
            $fileName = array_pop(explode('/', $imageUrl));
        }

        $img = sys_get_temp_dir().'/'.$fileName;

        file_put_contents($img, file_get_contents($imageUrl));
        //copy($imageUrl, $img);
        $imageEntity = $this->getContainer()->get('sylius.factory.product_image')->createNew();
        $imageEntity->setFile(new UploadedFile($img, $fileName));
        $this->getContainer()->get('sylius.image_uploader')->upload($imageEntity);

        return $imageEntity;
    }

    function printMemoryUsage($output)
    {
        $output->writeln(sprintf('Memory usage (currently) %dKB/ (max) %dKB', round(memory_get_usage(true) / 1024),
            memory_get_peak_usage(true) / 1024));
    }

    private function cleanString($string)
    {
        return strtolower(str_replace([' '], '_', $string));
    }
}