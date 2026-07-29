<?php

declare(strict_types=1);

namespace Twint\Magento\Service;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Container\InvoiceIdentity;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class InvoiceService
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $repository,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly InvoiceSender $sender,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @throws Throwable
     * @throws LocalizedException
     */
    public function create(Order $order, Transaction $transaction): ?Invoice
    {
        if ($order->canInvoice()) {
            try {
                $invoice = $order->prepareInvoice();
                $invoice->getOrder()
                    ->setIsInProcess(true);

                // set transaction id so you can do a online refund from credit memo
                $invoice->setTransactionId($transaction->getTxnId());
                $invoice->register()
                    ->pay();


                $this->repository->save($invoice);
            } catch (Throwable $e) {
                $this->logger->error('Cannot create invoice: ' . $e->getMessage());
                throw $e;
            }

            $invoiceAutoMail = (bool) $this->scopeConfig->isSetFlag(
                InvoiceIdentity::XML_PATH_EMAIL_ENABLED,
                ScopeInterface::SCOPE_STORE,
                $order->getStoreId()
            );

            if ($invoiceAutoMail) {
                $this->sendInvoiceMail($invoice);
            }

            return $invoice;
        }

        return null;
    }

    public function sendInvoiceMail(Invoice $invoice): void
    {
        try {
            $this->sender->send($invoice);
        } catch (Throwable $exception) {
            $this->logger->error(
                'Exception in Send Mail in Magento. This is an issue in the the core of Magento' .
                $exception->getMessage()
            );
        }
    }
}
