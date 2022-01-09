<?php

/**
 * HomeDeliveryOrders DataTable
 *
 * @package     Rideon Driver
 * @subpackage  DataTable
 * @category    HomeDelivery
 * @author      pardusurbanus@protonmail.com
 * @version     2.2
 * @link        https://rideon.co
 */

namespace App\DataTables;

use App\Models\User;
use App\Models\HomeDeliveryOrder;
use App\Models\DriverLocation;
use App\Models\Request as RideRequest;

use Yajra\DataTables\QueryDataTable;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Html\Editor\Editor;

use DB;

use Carbon\Carbon;

class HomeDeliveryOrderDataTable extends DataTable
{
    public function __construct()
    {
        $this->helper = resolve('App\Http\Start\Helpers');
        $this->request_helper = resolve('App\Http\Helper\RequestHelper');
	}
    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return \Yajra\DataTables\DataTableAbstract
     */
    public function dataTable($query)
    {
        return datatables()
            ->of($query)
            ->addColumn('eta', function ($order){
                try {
                    if ($order->eta){
                        $time = $order->eta . ' minutes';
                    }
                    else{
                        $time = 'unknown';
                    }
                } catch (\Exception $e) {
                    $time = 'unknown';
                }

                $direction = '';
                if($order->status == 'assigned'){
                    $direction = 'to pick up';
                }
                else if($order->status == 'picked_up'){
                    $direction = 'to drop off';
                }
                return $time . " " . $direction;
            })
            ->addColumn('fee_gst', function ($order){
                $fee = $order->fee;
                $final_payout = 0;
                $tax_gst = 1 + site_settings('tax_gst') / 100; //tax or gst
                if ($order->subscription == '1'){
                    $commission_percent = site_settings('regular_driver_booking_fee') / 100;
                    $commission = $fee * $commission_percent;
                    $final_payout = ($fee - $commission) * $tax_gst;
                }
                else{
                    $final_payout = $fee * $tax_gst;
                }
                return round((float)$final_payout, 2);
            })
            ->addColumn('order_type', function ($order){
                if($order->peak_fare != 0){
                    return 'Manual';
                }
                else{
                    return 'Automatic';
                }
            })
            ->editColumn('fee', function ($model) {
                if($model->peak_fare != 0){
                    return $model->fee + site_settings('manual_surchange');
                }
                else{
                    return $model->fee;
                }
            })
            ->filterColumn('driver_id', function($query, $keyword) {
                    $sql = 'CONCAT(delivery_orders.driver_id," - ",driver.first_name) LIKE ?';
                    $query->whereRaw($sql, ["%{$keyword}%"]);
            })
            ->addColumn('action', function ($orders) {
                $detail = '<a href="'.url(LOGIN_USER_TYPE.'/home_delivery_orders/'.$orders->id).'" class="btn btn-xs btn-info" title="Order details"><i class="fa fa-eye"></i></a>&nbsp;';
                $edit = (LOGIN_USER_TYPE=='company' || auth('admin')->user()->can('update_delivery')) ? '<a href="'.url(LOGIN_USER_TYPE.'/edit_home_delivery/'.$orders->id).'" class="btn btn-xs btn-primary" title="Edit order details"><i class="glyphicon glyphicon-edit"></i></a><br>' : '';
                $suspend = ( (auth()->guard('company')->user()!=null || auth('admin')->user()->can('manage_payments') ) && $orders->payout_status != 'Suspended') ? '<a data-href="'.url(LOGIN_USER_TYPE.'/suspend_home_delivery/'.$orders->id).'" class="btn btn-xs btn-warning" data-toggle="modal" data-target="#confirm-suspend" title="Suspend driver payout"><i class="glyphicon glyphicon-pause"></i></a>&nbsp;':'';
                $resume = ( (auth()->guard('company')->user()!=null || auth('admin')->user()->can('manage_payments') ) && $orders->payout_status == 'Suspended') ? '<a data-href="'.url(LOGIN_USER_TYPE.'/resume_home_delivery/'.$orders->id).'" class="btn btn-xs btn-success" data-toggle="modal" data-target="#confirm-resume" title="Resume driver payout"><i class="glyphicon glyphicon-play"></i></a>&nbsp;':'';
                $delete = (auth()->guard('company')->user()!=null || auth('admin')->user()->can('delete_delivery')) ? '<a data-href="'.url(LOGIN_USER_TYPE.'/delete_home_delivery/'.$orders->id).'" class="btn btn-xs btn-danger" data-toggle="modal" data-target="#confirm-delete" title="Delete order"><i class="glyphicon glyphicon-trash"></i></a>':'';
                return $detail.$edit."<hr style='margin: 2px 0px; border: 0px;'>".$resume.$suspend.$delete;
            })
            ->setRowClass(function ($orders) {
                $diff = Carbon::now();
                if($orders->created_at->addMinutes($orders->estimate_time) < $diff && $orders->status != 'delivered' ){
                    return 'danger';
                }
                elseif ($orders->status == 'assigned' || $orders->status == 'picked_up'){
                    return 'warning';
                }
                elseif($orders->status == 'delivered'){
                    return 'success';
                }
            });
    }

    /**
     * Get query source of dataTable.
     *
     * @param \HomeDeliveryOrder $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(HomeDeliveryOrder $model)
    {
        return HomeDeliveryOrder::whereIn('delivery_orders.status',['new','assigned','picked_up','delivered','expired', 'pre_order'])
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
            ->leftJoin('stripe_subscriptions', function($join) {
                $join->on('stripe_subscriptions.user_id', '=', 'driver.id')->whereIn('stripe_subscriptions.status',['subscribed']);
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
                'delivery_orders.delivered_at as delivered_at',
                'delivery_orders.status as status',
                DB::raw('CASE WHEN delivery_orders.status = "delivered" THEN 1 ELSE 0 END AS status_group'),
                'ride_request.pickup_location as pick_up_location',
                'ride_request.drop_location as drop_off_location',
                'ride_request.peak_fare as peak_fare',
                DB::raw('CONCAT(rider.first_name," ",rider.last_name) as customer_name'),
                DB::raw('CONCAT("+",rider.country_code,rider.mobile_number) as mobile_number'),
                'stripe_subscriptions.plan as subscription'
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
            ->setTableId('delivery-orders-table')
            ->columns($this->getColumns())
            ->pageLength(50)
            ->lengthMenu([ [10, 25, 50, -1], [10, 25, 50, "All"] ])
            ->minifiedAjax()
            ->dom('lBfr<"table-responsive"t>ip')
            ->orderBy(0)
            ->buttons(
                Button::make('csv'),
                Button::make('excel'),
                Button::make('print'),
                Button::make('reset'),
                Button::make('reload')
            )->parameters([
                'order' => [[ 0, 'desc' ]],
                'orderFixed' => [
                    'pre' => [ 5, 'asc' ],
                ]
           ]);
    }

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        return [
            Column::make('id')
                ->title('Order ID'),
            Column::make('order_type'),
            Column::make('merchant_name')
                ->title('Merchant')
                ->name('merchants.name'),
            Column::make('created_at')
                ->title('Create at'),
            Column::make('delivery_time')
                ->title('Deliver up to')
                ->searchable(false),
            Column::make('status_group')
                ->visible(false)
                ->exportable(false)
                ->printable(false)
                ->searchable(false),
            Column::make('status')
                ->title('Status'),
            Column::make('driver_id')
                ->title('Assigned Driver'),
            Column::make('delivered_at')
                ->title('Read Drop Date'),
            Column::make('payout_status')
                ->title('Payout to driver status')
                ->name('payment.driver_payout_status'),
            Column::make('estimate_time')
                ->title('Estimate time'),
            Column::make('eta')->title('ETA')
                ->exportable(false)
                ->printable(false)
                ->orderable(false)
                ->searchable(false),
            Column::make('fee')
                ->title('Fee'),
            Column::make('fee_gst')->title('Driver\'s payout with GST')
                ->searchable(false),
            Column::make('pick_up_location')
                ->title('Pick Up')
                ->name('ride_request.pickup_location'),
            Column::make('drop_off_location')
                ->title('Drop Off')
                ->name('ride_request.drop_location'),
            Column::make('distance')
                ->title('Distance'),
            Column::make('order_description')
                ->title('Order Description'),
            Column::make('customer_name')
                ->title('Customer Name')
                ->name('rider.first_name'),
            Column::make('mobile_number')
                ->title('Customer Phone')
                ->name('rider.mobile_number'),
            Column::make('action', 'Action')
                ->exportable(false)
                ->printable(false)
                ->orderable(false)
                ->searchable(false)
                ->addClass('text-center')->addClass('th-lg'),
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'home_delivery_' . date('YmdHis');
    }
}