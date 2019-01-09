<?php

namespace AppBundle\Command;


use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Sylius\Component\Addressing\Factory\ZoneFactoryInterface;
use Sylius\Component\Addressing\Model\Country;
use Sylius\Component\Addressing\Model\ZoneInterface;
use Sylius\Component\Addressing\Model\ZoneMember;
use Sylius\Component\Product\Model\ProductAssociationTypeInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class ImportGeoCommand extends ContainerAwareCommand
{
    private const URL = 'https://goo.gl/wZFCnT';
    private $locale = 'en_US';

    /**
     * @var FactoryInterface
     */
    private $countryFactory;
    /**
     * @var ObjectManager
     */
    private $countryManager;
    /**
     * @var FactoryInterface
     */
    private $provinceFactory;
    /**
     * @var ObjectManager
     */
    private $provinceManager;


    /**
     * @var ZoneFactoryInterface
     */
    private $zoneFactory;

    /**
     * @var ObjectManager
     */
    private $zoneManager;


    protected function configure()
    {
        $this
            ->setName('import:countries')
            ->setDescription('Import taxons from URL');
//            ->addArgument(
//                'csvFilePath',
//                InputArgument::REQUIRED,
//                'Specify path to CSV file'
//            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $this->countryFactory = $this->getContainer()->get('sylius.factory.country');
        $this->countryManager = $this->getContainer()->get('sylius.manager.country');
        $this->provinceFactory = $this->getContainer()->get('sylius.factory.province');
        $this->provinceManager = $this->getContainer()->get('sylius.manager.province');
        $this->zoneFactory = $this->getContainer()->get('sylius.factory.zone');
        $this->zoneManager = $this->getContainer()->get('sylius.manager.zone');

        $this->channel = $this->getContainer()->get('sylius.context.channel')->getChannel();
        /* CSV */
        //$csvFilePath = $input->getArgument('csvFilePath');
        $csv = new CsvIterator(self::URL);
        if ($csv->getFile() === false) {
            die(sprintf('CSV file not valid. path: %s'.PHP_EOL, self::URL));
        }
        $country = [];
        $count = 0;
        $st = false;
        foreach ($csv->parse() as $row) {
            if ($row['row1'] != '---' && !$st) {
                $country[$row['row1']] = new \StdClass();
                $country[$row['row1']]->code = $row['row2'];
                $country[$row['row1']]->name = $row['row4'];
                $country[$row['row1']]->state = [];
            } else {
                if ($row['row1'] != '---') {
                    $country[$row['row3']]->state[$row['row2']] = $row['row4'];
                } else {
                    $st = true;
                }
            }
        }

        foreach ($country as $cntr) {
            $output->writeln($cntr->code.'::'.$cntr->name);

            /** @var RepositoryInterface $rep1 */
            $rep1 = $this->getContainer()->get('sylius.repository.country');
            $rep2 = $this->getContainer()->get('sylius.repository.province');
            $rep3 = $this->getContainer()->get('sylius.repository.zone');
            $c = $rep1->findOneBy(['code' => $cntr->code]) ?? $this->countryFactory->createNew();
            $c->enable();
            $c->setCode($cntr->code);


            $this->countryManager->persist($c);

            if ($cntr->state ?? null) {
                foreach ($cntr->state as $provinceCode => $provinceName) {
                    if ($provinceCode == '' || $provinceName == '') {
                        continue;
                    }
                    $output->writeln($provinceCode.'::'.$provinceName);
                    /** @var ProvinceInterface $province */
                    $province = $rep2->findOneBy(['code' => $provinceCode.'_'.$c->getCode()]) ?? $this->provinceFactory->createNew();
                    $province->setCode($provinceCode.'_'.$c->getCode());
                    $province->setName($provinceName);
                    $c->addProvince($province);
                    $this->provinceManager->persist($province);
                }
            }
            /** @var ZoneInterface $zone */
            $zone = $rep3->findOneBy(['code' => 'world_wide']) ?? $this->zoneFactory->createNew();
            $zone->setCode('world_wide');
            $zone->setName('World wide');
            $zone->setType(ZoneInterface::TYPE_COUNTRY);
            $this->zoneManager->persist($zone);


            /** @var EntityManager  $em*/
            $em = $this->getContainer()->get('doctrine.orm.entity_manager');

            /** @var ZoneMember $member */
            $member = $em->getRepository(ZoneMember::class)->findOneBy(['code'=>$c->getCode(),'belongsTo'=>$zone->getId()])
            ?? $this->getContainer()->get('sylius.factory.zone_member')->createNew();
            if ($member->getCode()!=$c->getCode()) $member->setCode($c->getCode());
            if ($member->getBelongsTo()!=$zone) $member->setBelongsTo($zone);

            $em->persist($member);

        }
        $em->flush();
        $this->countryManager->flush();
        $this->provinceManager->flush();
        $this->zoneManager->flush();

    }


    private function cleanString($string)
    {
        return strtolower(str_replace([' '], '_', $string));
    }
}