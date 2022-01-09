<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use App\DataTables\TransactionsDataTable;
use App\DataTables\InvoicesDataTable;
use App\DataTables\PayoutsDataTable;

class TransactionsController extends Controller
{
    /**
     * Load Datatable for Transactions
     *
     * @param array $dataTable  Instance of Transactions DataTable
     * @return datatable
     */
    // public function index(TransactionsDataTable $dataTable)
    // {
    //     return $dataTable->render('admin.transactions.view');
    // }

    /**
     * Load Datatable for Transactions
     *
     * @param array $dataTable  Instance of Transactions DataTable
     * @return datatable
     */
    public function get_invoices(InvoicesDataTable $dataTable)
    {
        return $dataTable->render('admin.transactions.invoices_view');
    }

    /**
     * Load Datatable for Transactions
     *
     * @param array $dataTable  Instance of Transactions DataTable
     * @return datatable
     */
    public function get_payouts(PayoutsDataTable $dataTable)
    {
        return $dataTable->render('admin.transactions.payouts_view');
    }

    /**
     * Load Finantial Data
     *
     * @param array $dataTable  Instance of Transactions DataTable
     * @return datatable
     */
    public function payoutStatistics(Request $request)
    {
        $payout_service = resolve('App\Services\Payouts\StripePayout');
        $data['available_funds'] = $payout_service->getAvailableFunds();
        $data['test'] = 'test';
        return view('admin.transactions.stripe_statistics', $data);
    }
    
}
