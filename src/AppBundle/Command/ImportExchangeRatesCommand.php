<?php

namespace AppBundle\Command;


use Doctrine\ORM\EntityManager;
use Sylius\Component\Currency\Model\ExchangeRate;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;


class ImportExchangeRatesCommand extends ContainerAwareCommand
{
    private const URL = 'https://goo.gl/wZFCnT';
    private $locale = 'en_US';


    protected function configure()
    {
        $this
            ->setName('currency:update')
            ->setDescription('Update currency exchange rates');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $output->writeln('Fetching data from external database.');
        $container = $this->getContainer();
        /** @var EntityManager $em */
        $em = $container->get('doctrine.orm.entity_manager');
        $rates = $em->getRepository(ExchangeRate::class)->findAll();
        foreach ($rates as $rate) {
            $newrate = $this->getRate($rate->getTargetCurrency()->getCode());
            if ($newrate > 0) {
                $rate->setRatio($newrate);
            }
            $output->writeln($rate->getTargetCurrency()->getCode().'::'.$newrate);
        }
        $em->flush();


    }

    public function getRate($code)
    {
        $html = file_get_contents('https://xe.com/currencyconverter/convert/?Amount=1&From=USD&To='.$code);
        $crawler = new Crawler();
        $crawler->addHtmlContent($html);

        $result = $crawler->filterXPath('//span[@class="uccResultAmount"]')->text();

        return round($result, 2);
    }

}