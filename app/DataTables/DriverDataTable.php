<?php

/**
 * Driver DataTable
 *
 * @package     RideOnForDrivers
 * @subpackage  DataTable
 * @category    Driver
 * @author      RideOn Team (2020)
 * @version     2.2
 * @link        https://www.joinrideon.com/
 */

namespace App\DataTables;

use App\Models\User;
use Yajra\DataTables\Services\DataTable;
use DB;

class DriverDataTable extends DataTable
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
            ->of($query)
            ->filterColumn('full_mobile_number', function($query, $keyword) {
                    $sql = 'CONCAT("+", users.country_code, " ", users.mobile_number) LIKE ?';
                    $query->whereRaw($sql, ["%{$keyword}%"]);
            })
            ->filterColumn('driver_location', function($query, $keyword) {
                    $sql = 'CONCAT(driver_address.city, ", ", driver_address.state) LIKE ?';
                    $query->whereRaw($sql, ["%{$keyword}%"]);
            })
            ->addColumn('email', function ($users) {
                return protectedString($users->email);
            })
            ->addColumn('action', function ($users) {
                $edit = (LOGIN_USER_TYPE=='company' || auth('admin')->user()->can('update_driver')) ? '<a href="'.url(LOGIN_USER_TYPE.'/edit_driver/'.$users->id).'" class="btn btn-xs btn-primary"><i class="glyphicon glyphicon-edit"></i></a>&nbsp;' : '';
                $delete = (auth()->guard('company')->user()!=null || auth('admin')->user()->can('delete_driver')) ? '<a data-href="'.url(LOGIN_USER_TYPE.'/delete_driver/'.$users->id).'" class="btn btn-xs btn-primary" data-toggle="modal" data-target="#confirm-delete"><i class="glyphicon glyphicon-trash"></i></a>&nbsp;':'';
                return $edit.$delete;
            });
    }

    /**
     * Get query source of dataTable.
     *
     * @param User $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(User $model)
    {
        /* only for Package */
        /*$users = DB::Table('users')->select('users.id as id', 'users.first_name', 'users.last_name','users.email','users.country_code','users.mobile_number', 'users.status','companies.name as company_name','users.created_at',DB::raw('CONCAT("+",users.country_code," ",users.mobile_number) AS mobile'))
            ->leftJoin('companies', function($join) {
                $join->on('users.company_id', '=', 'companies.id');
            })->where('user_type','Driver')->groupBy('id');*/

        $users = $model->select(
            'users.id as id',
            'users.first_name',
            'users.last_name',
            'users.email',
            'users.country_code',
            DB::raw('CONCAT("+", users.country_code, " ", users.mobile_number) AS full_mobile_number'), 
            'users.status',
            'companies.name as company_name',
            'stripe_subscription_plans.plan_name',
            'users.created_at',
            DB::raw('CONCAT("XXXXXX",Right(users.mobile_number,4)) AS hidden_mobile'),
            DB::raw('CONCAT(driver_address.city, ", ", driver_address.state) AS driver_location'),
            DB::raw('(SELECT COUNT(d2.id) FROM delivery_orders AS d2 WHERE d2.driver_id = users.id AND delivered_at < CURDATE() AND delivered_at > DATE_SUB(CURDATE(), INTERVAL 7 DAY)) as deliveries_count')
            )
            ->leftJoin('companies', function($join) {
                $join->on('users.company_id', '=', 'companies.id');
            })
            ->leftJoin('stripe_subscriptions', function($join) {
                $join->on('users.id', '=', 'stripe_subscriptions.user_id');
            })
            ->leftJoin('stripe_subscription_plans', function($join) {
                $join->on('stripe_subscriptions.plan', '=', 'stripe_subscription_plans.id');
            })
            ->leftJoin('driver_address', function($join) {
                $join->on('users.id', '=', 'driver_address.user_id');
            })
            ->where('user_type','Driver')
            ->whereIn('plan_name', ['Driver Only', 'Driver Member'])
            ->groupBy('id');

        //If login user is company then get that company drivers only
        if (LOGIN_USER_TYPE=='company') {
            $users = $users->where('company_id',auth()->guard('company')->user()->id);
        }
        return $users;
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        return $this->builder()
                    ->columns($this->getColumns())
                    ->minifiedAjax()
                    ->dom('lBfr<"table-responsive"t>ip')
                    ->orderBy(0)
                    ->buttons(
                        ['csv', 'excel', 'print', 'reset']
                    );
    }

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        $mobile_number_column = (isLiveEnv())?'hidden_mobile':'full_mobile_number';
        $columns = [
            ['data' => 'id', 'name' => 'users.id', 'title' => 'Id'],
            ['data' => 'first_name', 'name' => 'users.first_name', 'title' => 'First Name'],
            ['data' => 'last_name', 'name' => 'users.last_name', 'title' => 'Last Name'],
            ['data' => 'driver_location', 'name' => 'driver_location', 'title' => 'Driver Location'],
            ['data' => 'deliveries_count', 'name' => 'deliveries_count', 'title' => 'Deliveries past week', 'searchable' => false,],
        ];
        if (LOGIN_USER_TYPE!='company') {
            $columns[] = ['data' => 'company_name', 'name' => 'companies.name', 'title' => 'Company Name'];
        }
        $more_columns = [
            ['data' => 'email', 'name' => 'users.email', 'title' => 'Email'],
            ['data' => 'status', 'name' => 'users.status', 'title' => 'Status'],
            ['data' => $mobile_number_column, 'name' => 'full_mobile_number', 'title' => 'Mobile Number'],
            ['data' => 'plan_name', 'name' => 'stripe_subscription_plans.plan_name', 'title' => 'Subscription Name'],
            ['data' => 'created_at', 'name' => 'users.created_at', 'title' => 'Created At'],
            ['data' => 'action', 'name' => 'action', 'title' => 'Action', 'orderable' => false, 'searchable' => false, 'exportable' => false],
        ];

        return array_merge($columns,$more_columns);
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'drivers_' . date('YmdHis');
    }
}