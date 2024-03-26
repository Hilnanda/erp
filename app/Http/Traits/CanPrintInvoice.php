<?php

namespace App\Http\Traits;

use App\BusinessLocation;
use App\Transaction;
use App\Utils\BusinessUtil;
use App\Utils\TransactionUtil;
use Illuminate\Http\Request;
use PDF;

trait CanPrintInvoice
{
    protected $businessUtil;

    protected $transactionUtil;

    public function initTrait()
    {
        if (!$this->businessUtil) {
            $this->businessUtil = new BusinessUtil;
        }
        if (!$this->transactionUtil) {
            $this->transactionUtil = new TransactionUtil;
        }
    }
    /**
     * Prints invoice for sell
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function printInvoice(Request $request, $transaction_id)
    {
        if (request()->ajax()) {
            return $this->getInvoice($request, $transaction_id);
        }
    }

    /**
     * Download invoice for sell
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function downloadInvoice(Request $request, $transaction_id, $saveOnly = false)
    {
        $output = $this->getInvoice($request, $transaction_id, true);
        $html = \Arr::get($output, 'receipt.html_content');
        $css_paths = [
            $_SERVER["DOCUMENT_ROOT"].'/css/app.css',
            $_SERVER["DOCUMENT_ROOT"].'/css/rtl.css',
            $_SERVER["DOCUMENT_ROOT"].'/css/vendor.css',
        ];
        foreach ($css_paths as $css_path) {
            $html = '<link href="'.$css_path.'" rel="stylesheet" />' . $html;
        }

        if (!file_exists($_SERVER["DOCUMENT_ROOT"] . '/downloads')) {
            mkdir($_SERVER["DOCUMENT_ROOT"].'/downloads', 0777, true);
        }
        $pdf = PDF::loadHTML($html);
        $filename = 'invoice.pdf';
        if ($saveOnly) {
            $path = $_SERVER["DOCUMENT_ROOT"].'/downloads/'.$filename;
            $pdf->save($path);
            return url($path);
        }
        return $pdf->download($filename);
    }

    /**
     * Save invoice for sell
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function saveInvoice(Request $request, $transaction_id)
    {
        return $this->downloadInvoice($request, $transaction_id, true);
    }

    /**
     * Prints invoice for sell
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getInvoice(Request $request, $transaction_id, $is_download = false)
    {
        try {
            $output = ['success' => 0,
                'msg' => trans('messages.something_went_wrong'),
            ];

            $business_id = $request->session()->get('user.business_id');

            $transaction = Transaction::where('business_id', $business_id)
                            ->where('id', $transaction_id)
                            ->with(['location'])
                            ->first();

            if (empty($transaction)) {
                return $output;
            }

            $printer_type = 'browser';
            if (! empty(request()->input('check_location')) && request()->input('check_location') == true) {
                $printer_type = $transaction->location->receipt_printer_type;
            }

            $is_package_slip = ! empty($request->input('package_slip')) ? true : false;
            $is_delivery_note = ! empty($request->input('delivery_note')) ? true : false;

            $invoice_layout_id = $transaction->is_direct_sale ? $transaction->location->sale_invoice_layout_id : null;
            $receipt = $this->receiptContent($business_id, $transaction->location_id, $transaction_id, $printer_type, $is_package_slip, false, $invoice_layout_id, $is_delivery_note, is_download: $is_download);

            if (! empty($receipt)) {
                $output = ['success' => 1, 'receipt' => $receipt];
            }
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => 0,
                'msg' => trans('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    /**
     * Returns the content for the receipt
     *
     * @param  int  $business_id
     * @param  int  $location_id
     * @param  int  $transaction_id
     * @param  string  $printer_type = null
     * @return array
     */
    private function receiptContent(
        $business_id,
        $location_id,
        $transaction_id,
        $printer_type = null,
        $is_package_slip = false,
        $from_pos_screen = true,
        $invoice_layout_id = null,
        $is_delivery_note = false,
        $is_download = false,
    ) {
        $output = ['is_enabled' => false,
            'print_type' => 'browser',
            'html_content' => null,
            'printer_config' => [],
            'data' => [],
        ];

        $business_details = $this->businessUtil->getDetails($business_id);
        $location_details = BusinessLocation::find($location_id);

        if ($from_pos_screen && $location_details->print_receipt_on_invoice != 1) {
            return $output;
        }
        //Check if printing of invoice is enabled or not.
        //If enabled, get print type.
        $output['is_enabled'] = true;

        $invoice_layout_id = ! empty($invoice_layout_id) ? $invoice_layout_id : $location_details->invoice_layout_id;
        $invoice_layout = $this->businessUtil->invoiceLayout($business_id, $invoice_layout_id);

        //Check if printer setting is provided.
        $receipt_printer_type = is_null($printer_type) ? $location_details->receipt_printer_type : $printer_type;

        $receipt_details = $this->transactionUtil->getReceiptDetails($transaction_id, $location_id, $invoice_layout, $business_details, $location_details, $receipt_printer_type);

        $currency_details = [
            'symbol' => $business_details->currency_symbol,
            'thousand_separator' => $business_details->thousand_separator,
            'decimal_separator' => $business_details->decimal_separator,
        ];
        $receipt_details->currency = $currency_details;

        if ($is_package_slip) {
            $output['html_content'] = view('sale_pos.receipts.packing_slip', compact('receipt_details'))->render();

            return $output;
        }

        if ($is_delivery_note) {
            $output['html_content'] = view('sale_pos.receipts.delivery_note', compact('receipt_details'))->render();

            return $output;
        }

        $output['print_title'] = $receipt_details->invoice_no;
        //If print type browser - return the content, printer - return printer config data, and invoice format config
        if ($receipt_printer_type == 'printer') {
            $output['print_type'] = 'printer';
            $output['printer_config'] = $this->businessUtil->printerConfig($business_id, $location_details->printer_id);
            $output['data'] = $receipt_details;
        } else {
            $layout = ! empty($receipt_details->design) ? 'sale_pos.receipts.'.$receipt_details->design : 'sale_pos.receipts.classic';

            $output['html_content'] = view($layout, compact('receipt_details', 'is_download'))->render();
        }

        return $output;
    }
}