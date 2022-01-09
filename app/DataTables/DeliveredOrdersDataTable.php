<?php

namespace App\DataTables;

use App\DeliveredOrder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Html\Editor\Editor;

class DeliveredOrdersDataTable extends DataTable
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
            ->addColumn('action', 'deliveredorders.action');
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\DeliveredOrder $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(DeliveredOrder $model)
    {
        return HomeDeliveryOrder::where('delivery_orders.status','delivered')
            ->join('users as rider', function($join) {
                $join->on('rider.id', '=', 'delivery_orders.customer_id');
            })
            ->leftJoin('users as driver', function($join) {
                $join->on('driver.id', '=', 'delivery_orders.driver_id');
            })
            ->join('request as ride_request', function($join) {
                $join->on('ride_request.id', '=', 'delivery_orders.ride_request');
            })
            ->join('merchants', function($join) {
                $join->on('merchants.id', '=', 'delivery_orders.merchant_id');
            })
            ->leftJoin('trips', function($join) {
                $join->on('trips.request_id', '=', 'ride_request.id')->whereNotIn('trips.status',['Cancelled']);
            })
            ->leftJoin('payment', function($join) {
                $join->on('payment.trip_id', '=', 'trips.id');
            })
            ->select([
                'delivery_orders.id as id',
                'delivery_orders.eta as eta',
                DB::raw('CONCAT(delivery_orders.estimate_time," mins") as estimate_time'),
                DB::raw('DATE_ADD(delivery_orders.created_at, INTERVAL delivery_orders.estimate_time MINUTE) as delivery_time'),
                DB::raw('CONCAT(delivery_orders.driver_id," - ",driver.first_name) as driver_id'),
                'payment.driver_payout_status as payout_status',
                'delivery_orders.created_at as created_at',
                'merchants.name as merchant_name',
                'delivery_orders.order_description as order_description',
                DB::raw('CONCAT(delivery_orders.distance/1000," KM") as distance'),
                'delivery_orders.fee as fee',
                'delivery_orders.status as status',
                DB::raw('CASE WHEN delivery_orders.status = "delivered" THEN 1 ELSE 0 END AS status_group'),
                'ride_request.pickup_location as pick_up_location',
                'ride_request.drop_location as drop_off_location',
                DB::raw('CONCAT(rider.first_name," ",rider.last_name) as customer_name'),
                DB::raw('CONCAT("+",rider.country_code,rider.mobile_number) as mobile_number'),
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
                    ->setTableId('deliveredorders-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax()
                    ->dom('Bfrtip')
                    ->orderBy(1)
                    ->buttons(
                        Button::make('create'),
                        Button::make('export'),
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
            Column::computed('action')
                  ->exportable(false)
                  ->printable(false)
                  ->width(60)
                  ->addClass('text-center'),
            Column::make('id'),
            Column::make('add your columns'),
            Column::make('created_at'),
            Column::make('updated_at'),
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'DeliveredOrders_' . date('YmdHis');
    }
}
