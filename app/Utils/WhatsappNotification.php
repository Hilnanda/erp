<?php

namespace App\Utils;

use App\Addons\LSenderAddon;

trait WhatsappNotification
{
    use LSenderAddon;

    protected $newLine = "\r\n";

    /**
     * notify on sales order created
     *
     * @param  Transaction  $transaction
     * @return object  $response
     */
    public function whatsappNotifySalesCreated($transaction)
    {
        $transactionUtil = new \App\Utils\TransactionUtil;
        $customer = $transaction->contact;
        $receiver = $customer?->mobile;
        $paid_amount = $transactionUtil->getTotalPaid($transaction->id);
        $total_payable = $transaction->final_total - $paid_amount;

        $message = 'Dear ' . $customer?->name . ','
            . $this->newLine . 'Terima kasih telah bertransaksi di MAC'
            . $this->newLine
            . $this->newLine . 'Tanggal : ' . $transactionUtil->format_date($transaction->transaction_date, true)
            . $this->newLine . 'No Invoice : ' . $transaction->invoice_no
            . $this->newLine . 'Item : ' . ($transaction->sell_lines->count() == 1 ? $transaction->sell_lines->first()->product->name : '');

        if ($transaction->sell_lines->count() > 1) {
            foreach ($transaction->sell_lines as $index => $line) {
                $message = $message . $this->newLine . ($index+1) . '. ' . $line->product->name;
            }
        }

        $message = $message
            . $this->newLine . 'Total Transaksi : ' . $transactionUtil->num_f($total_payable)
            . $this->newLine
            . $this->newLine . 'Jika belum melakukan pembayaran silahkan transfer ke rekening di bawah sebelum ' . $transactionUtil->format_date($transaction->due_date, true)
            . $this->newLine
            . $this->newLine . 'BCA : 316-034-5470'
            . $this->newLine . 'a/n. Ayu Hani Hartiana'
            . $this->newLine . 'Mandiri : 141-00-2283-1812'
            . $this->newLine . 'a/n. Ayu Hani Hartiana'
            . $this->newLine
            . $this->newLine . '*NOTE : Pembayaran dianggap sah apabila di lakukan melalui transfer ke nomor rekening yang tertera pada invoice*';

        return $this->sendWhatsappText($receiver, $message);
    }

    /**
     * notify on purchase order created
     *
     * @param  Transaction  $transaction
     * @return object  $response
     */
    public function whatsappNotifyPurchaseCreated($transaction)
    {
        $transactionUtil = new \App\Utils\TransactionUtil;
        $supplier = $transaction->contact;
        $receiver = owner_mobile();
        $paid_amount = $transactionUtil->getTotalPaid($transaction->id);
        $total_payable = $transaction->final_total - $paid_amount;

        $message = '*Reminder Pembuatan PO*'
            . $this->newLine . 'Anda memiliki pembuatan PO baru dengan nomor PO ' . $transaction->ref_no . ' kepada supplier ' . $supplier->name
            . $this->newLine
            . $this->newLine . 'Tanggal : ' . $transactionUtil->format_date($transaction->transaction_date, true)
            . $this->newLine . 'Item : ' . ($transaction->purchase_lines->count() == 1 ? $transaction->purchase_lines->first()->product->name : '');

        if ($transaction->purchase_lines->count() > 1) {
            foreach ($transaction->purchase_lines as $index => $line) {
                $message = $message . $this->newLine . ($index+1) . '. ' . $line->product->name;
            }
        }

        $message = $message . $this->newLine . 'Total Transaksi : ' . $transactionUtil->num_f($total_payable);

        return $this->sendWhatsappText($receiver, $message);
    }

    /**
     * notify on sales order payment
     *
     * @param  Transaction  $transaction
     * @return object  $response
     */
    public function whatsappNotifySalesPayment($transaction, $payment, $business_id)
    {
        $transactionUtil = new \App\Utils\TransactionUtil;
        $customer = $transaction->contact;
        $receiver = owner_mobile();
        $paid_amount = $transactionUtil->getTotalPaid($transaction->id);
        $status = $paid_amount == $transaction->final_total ? 'Lunas' : 'Sebagian';
        $payment_types = $transactionUtil->payment_types(null, false, $business_id);
        $metode = $payment_types[$payment->method];

        $message = '*Reminder Pembayaran SO*'
            . $this->newLine . 'Pembayaran ' . $status . ' telah dilakukan'
            . $this->newLine
            . $this->newLine . 'Tanggal : ' . $transactionUtil->format_date($payment->created_at, true)
            . $this->newLine . 'Customer : ' . $customer->name
            . $this->newLine . 'No Invoice : ' . $transaction->invoice_no
            . $this->newLine . 'Total : ' . $transactionUtil->num_f($payment->amount)
            . $this->newLine . 'Metode Pembayaran : ' . $metode;

        return $this->sendWhatsappText($receiver, $message);
    }

    /**
     * notify on sales order due
     *
     * @param  Transaction  $transaction
     * @return object  $response
     */
    public function whatsappNotifySalesDue($transaction)
    {
        $transactionUtil = new \App\Utils\TransactionUtil;
        $businessUtil = new \App\Utils\BusinessUtil;
        $customer = $transaction->contact;
        $receiver = $customer?->mobile;
        $paid_amount = $transactionUtil->getTotalPaid($transaction->id);
        $total_payable = $transaction->final_total - $paid_amount;
        $due_date = $transaction->due_date ? $transaction->due_date : date('Y-m-d H:i:s', strtotime($transaction->transaction_date
            . ' + ' . $transaction->pay_term_number . ' ' . $transaction->pay_term_type));
        $business_details = $businessUtil->getDetails($transaction->business_id);

        $message = 'Dear ' . $customer?->name . ','
            . $this->newLine . 'Transaksi anda'
            . $this->newLine
            . $this->newLine . 'Tanggal : ' . $transactionUtil->format_date($transaction->transaction_date, true, $business_details)
            . $this->newLine . 'No Invoice : ' . $transaction->invoice_no
            . $this->newLine . 'Item : ' . ($transaction->sell_lines->count() == 1 ? $transaction->sell_lines->first()->product->name : '');

        if ($transaction->sell_lines->count() > 1) {
            foreach ($transaction->sell_lines as $index => $line) {
                $message = $message . $this->newLine . ($index+1) . '. ' . $line->product->name;
            }
        }

        $message = $message
            . $this->newLine . 'Total Transaksi : ' . $transactionUtil->num_f($total_payable, false, $business_details)
            . $this->newLine
            . $this->newLine . 'Jatuh tempo pada tanggal ' . $transactionUtil->format_date($due_date, true, $business_details)
            . $this->newLine . 'Mohon segera melakukan pembayaran ke rekening di bawah'
            . $this->newLine
            . $this->newLine . 'BCA : 316-034-5470'
            . $this->newLine . 'a/n. Ayu Hani Hartiana'
            . $this->newLine . 'Mandiri : 141-00-2283-1812'
            . $this->newLine . 'a/n. Ayu Hani Hartiana'
            . $this->newLine
            . $this->newLine . '*NOTE : Pembayaran dianggap sah apabila di lakukan melalui transfer ke nomor rekening yang tertera pada invoice*';

        return $this->sendWhatsappText($receiver, $message);
    }
}
