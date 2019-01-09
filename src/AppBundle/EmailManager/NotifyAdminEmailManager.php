<?php
/**
 * Created by PhpStorm.
 * User: beathan
 * Date: 26.07.2018
 * Time: 13:11
 */
declare(strict_types=1);

namespace AppBundle\EmailManager;

use Sylius\Bundle\CoreBundle\Mailer\Emails;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Mailer\Sender\SenderInterface;

final class NotifyAdminEmailManager
{
    /**
     * @var SenderInterface
     */
    private $emailSender;
    /** @var array $emailRecipient */
    private $emailRecipients;

    /**
     * @param SenderInterface $emailSender
     */
    public function __construct(SenderInterface $emailSender, array $emailRecipients)
    {
        $this->emailSender = $emailSender;
        $this->emailRecipients = $emailRecipients;
    }

    /**
     * {@inheritdoc}
     */
    public function sendPaymentNotificationEmail(OrderInterface $order): void
    {
        $this->emailSender->send('payment_confirmation', $this->emailRecipients,['order' => $order]);
    }

}