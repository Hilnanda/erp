<?php

namespace App\Utils;

use App\Addons\LSenderAddon;
use Illuminate\Http\Request;

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

        $message = 'Dear ' . trim($customer?->name . ' ') . ','
            . $this->newLine . 'Terima kasih telah melakukan transaksi pembelian produk di PT. MAC'
            . $this->newLine
            . $this->newLine . 'Tanggal : ' . $transactionUtil->format_date($transaction->transaction_date, true)
            . $this->newLine . 'ME : ' . $transaction->added_by
            . $this->newLine . 'No Invoice : ' . $transaction->invoice_no
            . $this->newLine . 'Item : ' . ($transaction->sell_lines->count() == 1 ? $transaction->sell_lines->first()->product->name : '');

        if ($transaction->sell_lines->count() > 1) {
            foreach ($transaction->sell_lines as $index => $line) {
                $message = $message . $this->newLine . ($index+1) . '. ' . $line->product->name;
            }
        }

        $message = $message
            . $this->newLine . 'Total Transaksi : ' . $transactionUtil->num_f($transaction->final_total, true)
            . $this->newLine . 'Total Paid : ' . $transactionUtil->num_f($paid_amount, true)
            . $this->newLine . 'Belum Terbayar : ' . $transactionUtil->num_f($total_payable, true)
            . $this->newLine . 'Jatuh Tempo : ' . $transactionUtil->format_date($transaction->transaction_date)
            . $this->newLine
            . $this->newLine . 'Anda dapat melakukan pembayaran melalui transfer ke rekening di bawah sebelum ' . $transactionUtil->format_date($transaction->due_date, true)
            . $this->newLine
            . $this->newLine . 'BCA : 316-034-5470'
            . $this->newLine . 'a/n. Ayu Nani Hartiana'
            . $this->newLine . 'Mandiri : 141-00-2283-1812'
            . $this->newLine . 'a/n. Ayu Nani Hartiana'
            . $this->newLine
            . $this->newLine . '*NOTE : Pembayaran dianggap sah apabila di lakukan melalui transfer ke nomor rekening yang tertera pada invoice*';

        if (env('REMINDER_WITH_MEDIA', 0)) {
            $media_url = $transactionUtil->saveInvoice(new Request(), $transaction->id);
            $response = $this->sendWhatsappMedia($receiver, $media_url, 'file', $message);
        } else {
            $response = $this->sendWhatsappText($receiver, $message);
        }

        $transactionUtil->activityLog($transaction, 'whatsapp_notification', null, $response);
        return $response;
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

        $message = $message . $this->newLine . 'Total Transaksi : ' . $transactionUtil->num_f($transaction->final_total, true)
            . $this->newLine . 'Total Paid : ' . $transactionUtil->num_f($paid_amount, true)
            . $this->newLine . 'Belum Terbayar : ' . $transactionUtil->num_f($total_payable, true);

        if (env('REMINDER_WITH_MEDIA', 0)) {
            $media_url = $transactionUtil->saveInvoice(new Request(), $transaction->id);
            $response = $this->sendWhatsappMedia($receiver, $media_url, 'file', $message);
        } else {
            $response = $this->sendWhatsappText($receiver, $message);
        }

        $transactionUtil->activityLog($transaction, 'whatsapp_notification', null, $response);
        return $response;
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
        $receivers = [owner_mobile(), $customer?->mobile];
        $paid_amount = $transactionUtil->getTotalPaid($transaction->id);
        $status = $paid_amount == $transaction->final_total ? 'Lunas' : 'Sebagian';
        $payment_types = $transactionUtil->payment_types(null, false, $business_id);
        $metode = $payment_types[$payment->method];

        $message = '*Reminder Pembayaran SO*'
            . $this->newLine . 'Dear ' . trim($customer?->name . ' ') . ','
            . $this->newLine
            . $this->newLine . 'Terima kasih anda telah melakukan pembayaran invoice pada,'
            . $this->newLine
            . $this->newLine . 'Tanggal : ' . $transactionUtil->format_date($payment->created_at, true)
            . $this->newLine . 'Customer : ' . $customer->name
            . $this->newLine . 'No Invoice : ' . $transaction->invoice_no
            . $this->newLine . 'Total : ' . $transactionUtil->num_f($payment->amount, true)
            . $this->newLine . 'Metode Pembayaran : ' . $metode
            . $this->newLine
            . $this->newLine . 'Terima kasih atas kepercayaan anda pada PT.MAC';

        $responses = [];
        foreach ($receivers as $receiver) {
            if (env('REMINDER_WITH_MEDIA', 0)) {
                $media_url = $transactionUtil->saveInvoice(new Request(), $transaction->id);
                array_push($responses, $this->sendWhatsappMedia($receiver, $media_url, 'file', $message));
            } else {
                array_push($responses, $this->sendWhatsappText($receiver, $message));
            }
        }

        if (!is_array($responses)) {
            $responses = [$responses];
        }

        foreach ($responses as $response) {
            $transactionUtil->activityLog($transaction, 'whatsapp_notification', null, $response);
        }
        return $responses;
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

        $message = 'Dear ' . trim($customer?->name) . ','
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
            . $this->newLine . 'Total Transaksi : ' . $transactionUtil->num_f($transaction->final_total, true, $business_details)
            . $this->newLine . 'Total Paid : ' . $transactionUtil->num_f($paid_amount, true, $business_details)
            . $this->newLine . 'Belum Terbayar : ' . $transactionUtil->num_f($total_payable, true, $business_details)
            . $this->newLine
            . $this->newLine . 'Jatuh tempo pada tanggal ' . $transactionUtil->format_date($due_date, true, $business_details)
            . $this->newLine . 'Mohon segera melakukan pembayaran ke rekening di bawah'
            . $this->newLine
            . $this->newLine . 'BCA : 316-034-5470'
            . $this->newLine . 'a/n. Ayu Nani Hartiana'
            . $this->newLine . 'Mandiri : 141-00-2283-1812'
            . $this->newLine . 'a/n. Ayu Nani Hartiana'
            . $this->newLine
            . $this->newLine . '*NOTE : Pembayaran dianggap sah apabila di lakukan melalui transfer ke nomor rekening yang tertera pada invoice*';

        if (env('REMINDER_WITH_MEDIA', 0)) {
            $media_url = $transactionUtil->saveInvoice(new Request(), $transaction->id);
            $response = $this->sendWhatsappMedia($receiver, $media_url, 'file', $message);
        } else {
            $response = $this->sendWhatsappText($receiver, $message);
        }

        $transactionUtil->activityLog($transaction, 'whatsapp_notification', null, $response);
        return $response;
    }
}
