<?php

namespace App\Services;

use Xendit\Configuration;
use Xendit\Invoice\InvoiceApi;
use Xendit\Invoice\CreateInvoiceRequest;

class XenditService
{
    private InvoiceApi $invoiceApi;

    public function __construct()
    {
        Configuration::setXenditKey(config('services.xendit.secret_key'));
        $this->invoiceApi = new InvoiceApi();
    }

    /**
     * Create a Xendit Invoice for subscription payment.
     *
     * Returns the Invoice object with getInvoiceUrl() and getId().
     */
    public function createInvoice(
        string $externalId,
        int    $amount,
        string $description,
        string $payerEmail,
        array  $metadata = [],
    ): object {
        $request = new CreateInvoiceRequest([
            'external_id'      => $externalId,
            'amount'           => $amount,
            'description'      => $description,
            'payer_email'      => $payerEmail,
            'invoice_duration' => config('subscription.invoice_duration', 86400),
            'currency'         => 'IDR',
            'payment_methods'  => [
                'BCA', 'BNI', 'BRI', 'MANDIRI', 'PERMATA',
                'OVO', 'DANA', 'SHOPEEPAY', 'LINKAJA',
                'QRIS',
            ],
            'metadata' => $metadata,
        ]);

        return $this->invoiceApi->createInvoice($request);
    }

    /**
     * Get invoice detail by Xendit Invoice ID.
     */
    public function getInvoice(string $invoiceId): object
    {
        return $this->invoiceApi->getInvoiceById($invoiceId);
    }
}
