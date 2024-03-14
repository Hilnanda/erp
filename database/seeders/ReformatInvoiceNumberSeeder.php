<?php

namespace Database\Seeders;

use App\InvoiceScheme;
use App\Transaction;
use App\User;
use App\Utils\TransactionUtil;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReformatInvoiceNumberSeeder extends Seeder
{
	/**
	 * Constructor
	 *
	 * @param  ProductUtils  $product
	 * @return void
	 */
	public function __construct(protected TransactionUtil $transactionUtil)
	{
	}

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::beginTransaction();
		try {
			$scheme = InvoiceScheme::find(env('INVOICE_SCHEME_ID', 1));
			$scheme->invoice_count = 0;
			$scheme->save();
			$business_id = env('BUSINESS_ID', 1);
			$transactions = Transaction::whereNotNull('invoice_no')->get();
			foreach ($transactions as $transaction) {
				$invoice_no = $this->transactionUtil->getFormattedInvoiceNumber($transaction->invoice_no, $business_id, null, env('INVOICE_SCHEME_ID', 1));
				$transaction->invoice_no = $invoice_no;
				$transaction->save();
				$scheme->invoice_count = $scheme->invoice_count+1;
				$scheme->save();
			}

			DB::commit();
		} catch (\Throwable $th) {
			DB::rollback();
			throw $th;
		}
    }
}
