<?php

namespace AppBundle\Command;

use Sylius\Component\Core\Model\Order;
use Sylius\Component\Core\Model\Shipment;
use Sylius\Component\Order\OrderTransitions;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\GenericEvent;


class exportUnshippedOrders extends ContainerAwareCommand
{
    private const URL = 'https://www.etsy.com/shop/FiorentinaCostuming/items';

    //private const URL = 'https://www.etsy.com/shop/WaryaTshirts?ref=l2-shopheader-name';


    protected function configure()
    {
        $this
            ->setName('orders:unshipped:export')
            ->setDescription('Export all unshipped orders to google spreadsheets.');
        //->addArgument('url', InputArgument::REQUIRED, 'Shop URL');


    }

    public function getOrderListFromDB(): ?array
    {
        $orders = $this->getContainer()->get('sylius.repository.order')->findBy(['state' => 'in_production']);
        $result = [];
        foreach ($orders as $order) {
            $result[(int)($order->getNumber())] = $order;
        }

        return $result;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $auth = $this->getContainer()->getParameter('google.api.credentials.json');
        $client = new \Google_Client();
        $client->setApplicationName('AnC workflow');
        $client->setScopes([\Google_Service_Sheets::SPREADSHEETS]);
        $client->setAccessType('offline');
        $client->setAuthConfig(json_decode($auth, true));
        $sheets = new \Google_Service_Sheets($client);
        $spreadsheetId = $this->getContainer()->getParameter('google.api.spreadsheet.id');
        $range = 'Shipping!A2:C';
        $rows = $sheets->spreadsheets_values->get($spreadsheetId, $range, ['majorDimension' => 'ROWS']);

        $rangeArchive = 'Archive!A2:C';
        $rowsArchive = $sheets->spreadsheets_values->get($spreadsheetId, $rangeArchive, ['majorDimension' => 'ROWS']);
        $currentRowArchive = count($rowsArchive->values??[]);
        //$orderWithTracking = [];
        $waitingForShipping = $this->getOrderListFromDB();
        $currentRow = 2 + $currentRowArchive;

        if ($rows->values) {
            foreach ($rows->values as $row) {
                if (count($row) > 2 && isset($waitingForShipping[$row[0]])) {
                    /** @var Order $order */
                    $order = $waitingForShipping[$row[0]];
                    $output->writeln("Processing order #".$order->getNumber());
                    //Mark order as shipped
                    /** @var Shipment $shipment */
                    $shipment = $this->getContainer()->get('sylius.factory.shipment')->createNew();
                    $shipment->setTracking($row[2]);

                    $shipment->setMethod($this->getContainer()->get('sylius.repository.shipping_method')->findOneBy(['code' => 'airmail']));
                    $order->addShipment($shipment);
                    $order->setShippingState('shipped');
                    $stateMachine = $this->getContainer()->get('sm.factory')->get($order, OrderTransitions::GRAPH);

                    $stateMachine->apply(OrderTransitions::TRANSITION_FULFILL);

                    $orderManager = $this->getContainer()->get('sylius.manager.order');
                    $orderManager->flush($order);
                    //Dispatch message
                    $event = new GenericEvent($shipment);
                    $this->getContainer()->get('event_dispatcher')->dispatch('sylius.shipment.post_ship', $event);



                        /** @var Order $row */
                        $updateRange = 'Archive!A:E'.$currentRow;
                        $this->addRowToSheet($sheets, $spreadsheetId, $updateRange, [
                            (int)$order->getNumber(),
                            $order->getShippingAddress()->getFirstName().' '.$order->getShippingAddress()->getLastName(),$row[2],$order->getCreatedAt()->format('Y-m-d'),date('Y-m-d'),
                        ]);
                        $currentRow++;



                }
            }
        }


        $range = 'Shipping!A2:D';
        $requestBody = new \Google_Service_Sheets_ClearValuesRequest();
        $sheets->spreadsheets_values->clear($spreadsheetId, $range, $requestBody);

        $waitingForShipping = $this->getOrderListFromDB();
        $currentRow = 2;

        foreach ($waitingForShipping as $row) {
            /** @var Order $row */
            $updateRange = 'Shipping!A:D'.$currentRow;
            $this->addRowToSheet($sheets, $spreadsheetId, $updateRange, [
                (int)$row->getNumber(),
                $row->getShippingAddress()->getFirstName().' '.$row->getShippingAddress()->getLastName(),'',$row->getCreatedAt()->format('Y-m-d')
            ]);
            $currentRow++;
        }


    }

    function addRowToSheet($sheets, $sheetId, $updateRange, $data)
    {
        $updateBody = new \Google_Service_Sheets_ValueRange([
            'range' => $updateRange,
            'majorDimension' => 'ROWS',
            'values' => ['values' => $data],
        ]);
        $sheets->spreadsheets_values->update(
            $sheetId,
            $updateRange,
            $updateBody,
            ['valueInputOption' => 'USER_ENTERED']
        );

    }

    function printMemoryUsage($output)
    {
        $output->writeln(sprintf('Memory usage (currently) %dKB/ (max) %dKB', round(memory_get_usage(true) / 1024),
            memory_get_peak_usage(true) / 1024));
    }


}