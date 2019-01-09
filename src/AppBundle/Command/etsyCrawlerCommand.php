<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;


class etsyCrawlerCommand extends ContainerAwareCommand
{
    private const URL = 'https://www.etsy.com/shop/FiorentinaCostuming/items';

    //private const URL = 'https://www.etsy.com/shop/WaryaTshirts?ref=l2-shopheader-name';


    protected function configure()
    {
        $this
            ->setName('etsy:get')
            ->setDescription('Etsy crawler');
        //->addArgument('url', InputArgument::REQUIRED, 'Shop URL');


    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $html = file_get_contents($this::URL);
        $crawler = new Crawler($html);
        $crawler->getUri();
        $crawler = $crawler->filterXPath('//*[@id="items"]/div/div[3]/div[3]/ul/li');

        foreach ($crawler as $domElement) {
            $urlList[] = $domElement->getElementsByTagName('a')[0]->attributes->getNamedItem('href')->value;
        }
        $handle = fopen('dress.csv', 'w+');
        // Add the header of the CSV file
        fputcsv($handle, [
            'Product Code',
            'Slug',
            'Name',
            'Full Description',
            'Region',
            'Period',
            'Gender',
            'Enabled',
            'Hidden',
            'Type',
            'PriceNP',
            'PriceSP',
            'PriceGP',
            'udfWebImage',
            'width',
            'length',
            'weight',
        ], ';');
        sleep(5);
        foreach ($urlList as $url) {
            echo $url."\n";
            $html = file_get_contents($url);
            try {
                $crawler = null;
                $crawler = new Crawler($html);
                $product = null;
                $product['desc'] = $crawler->filterXPath('//*[@id="description-text"]/div/div/div')->text();
                if (strpos($html, 'id="listing-title"') > 0) {
                    $product['name'] = $crawler->filterXPath('//*[@id="listing-title"]/span')->text();
                    echo 'using id="listing-title"'."\n";
                } else {
                    $product['name'] = $crawler->filterXPath('//*[@id="listing-page-cart"]/div[1]/div/h1')->text();
                }
                if (strpos($html, 'id="listing-price"') > 0) {
                    $product['price'] = $crawler->filterXPath('//*[@id="listing-price"]/meta')->extract(['content'])[1];
                    echo 'using id="listing-price"'."\n";
                } else {
                    $product['price'] = $crawler->filterXPath('//*[@id="listing-page-cart"]/div[1]/div/div[1]/p/span[1]')->text();
                }
                $code = explode("/", $url);
                $product['slug'] = explode('?', array_pop($code))[0];
                $product['code'] = array_pop($code);
                $imagesDom = $crawler->filterXPath('//*[@id="image-carousel"]')->children();


                foreach ($imagesDom as $imageDom) {
                    //if ($imageDom->getElementsByTagName('img')[0])
                    $product['images'][] = $imageDom->attributes->getNamedItem('data-full-image-href')->value;
                }
                $product['images'] = implode(',', $product['images']);
                $product['desc'] = str_replace("\n", '<br>', $product['desc']);

                fputcsv(
                    $handle, // The file pointer
                    array(
                        trim($product['code']),
                        trim($product['slug']),
                        trim($product['name']),
                        trim($product['desc']),
                        '',
                        '',
                        'f',
                        '1',
                        '0',
                        'Dress',
                        substr(trim($product['price']),0,-1),
                        '',
                        '',
                        $product['images'],
                        '',
                        '',
                        '',
                    )
                    , // The fields
                    ';' // The delimiter
                );


                $output->writeln($product['code']."\t".$product['slug']."\t".$product['name']."\n");
                sleep(5);


            } catch (\Exception $e) {
                file_put_contents('debug.html', $html.'<pre>'.print_r($product, true).'</pre>');
                var_dump($e->getMessage());
                die;
            }

        }

        fclose($handle);


    }


    private function cleanPrice($price)
    {
        return (int)str_replace([
            utf8_decode("£"),
            utf8_decode("."),
        ], '', $price);
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