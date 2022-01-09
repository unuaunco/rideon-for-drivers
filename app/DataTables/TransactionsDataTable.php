<?php

/**
 * Transactions DataTable
 *
 * @package     Rideon Driver
 * @subpackage  DataTable
 * @category    HomeDelivery
 * @author      pardusurbanus@protonmail.com
 * @version     2.2
 * @link        https://rideon.co
 */

namespace App\DataTables;

use App\Models\Transaction;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Html\Editor\Editor;

use Illuminate\Support\Facades\HtmlFacade;

use DB;

class TransactionsDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return \Yajra\DataTables\DataTableAbstract
     */
    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->filterColumn('user_name', function($query, $keyword) {
                    $sql = 'CONCAT(users.first_name, " ", users.last_name) LIKE ?';
                    $query->whereRaw($sql, ["%{$keyword}%"]);
            })
            ->setRowClass(function ($transactions) {
                if($transactions->status_description == 'Invoice failed' || $transactions->status_description == 'Transfer failed' || $transactions->status == 'Failed'){
                    return 'warning';
                }
                else{
                    return 'success';
                }
            })->editColumn('object_link', function ($model) {
                return "<a href=\"{$model->object_link}\" target=\"_blank\">$model->object_link</a>";
            })
            ->rawColumns(['object_link']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\Transaction $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Transaction $model)
    {
        return  $model->join('users', function($join) {
                    $join->on('users.id', '=', 'transactions.user_id');
                })->join('currency', function($join) {
                    $join->on('currency.id', '=', 'transactions.currency');
                })
                ->select([
                    'transactions.id as id',
                    'transactions.type as type',
                    DB::raw('CONCAT(users.first_name, " ", users.last_name) AS user_name'),
                    'transactions.status as status',
                    'transactions.status_description as status_description',
                    'transactions.description as description',
                    'transactions.amount as amount',
                    'transactions.amount_with_tax as amount_with_tax',
                    'transactions.calculation_date as calculation_date',
                    'currency.code as currency_code',
                    'transactions.object_link as object_link',
                    'transactions.created_at as created_at',
                ]);
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        return $this->builder()
                    ->setTableId('transactions-table')
                    ->columns($this->getColumns())
                    ->lengthMenu([ [10, 25, 50 -1], [10, 25, 50, "All"] ])
                    ->pageLength(25)
                    ->minifiedAjax()
                    ->dom('lBfr<"table-responsive"t>ip')
                    ->orderBy(0)
                    ->buttons(
                        Button::make('csv'),
                        Button::make('excel'),
                        Button::make('print'),
                        Button::make('reset'),
                        Button::make('reload')
                    );
    }

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        return [
            Column::make('id')->title('Transaction ID'),
            Column::make('type'),
            Column::make('user_name')->title('User \ Contact person'),
            Column::make('status'),
            Column::make('status_description')->title('Status message'),
            Column::make('description'),
            Column::make('amount'),
            Column::make('amount_with_tax'),
            Column::make('calculation_date'),
            Column::make('currency_code')->name('currency.code')->title('Currency'),
            Column::make('object_link')->title('Link')->searchable('false'),
            Column::make('created_at'),
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'Transactions_' . date('YmdHis');
    }
}
