<?php

namespace App\DataTables;

use App\Models\User;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Html\Editor\Editor;

use DB;

class CommunityLeadersDataTable extends DataTable
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
            ->addColumn('action', function ($user) {
                $detail = '<a href="'.url(LOGIN_USER_TYPE.'/community_leader/'.$user->id).'" class="btn btn-xs btn-primary"><i class="fa fa-eye" ></i></a>&nbsp;';
                $edit = (LOGIN_USER_TYPE=='company' || auth('admin')->user()->can('update_community_leader')) ? '<a href="'.url(LOGIN_USER_TYPE.'/edit_community_leader/'.$user->id).'" class="btn btn-xs btn-primary"><i class="glyphicon glyphicon-edit"></i></a>&nbsp;' : '';
                $delete = (auth()->guard('company')->user()!=null || auth('admin')->user()->can('delete_community_leader')) ? '<a data-href="'.url(LOGIN_USER_TYPE.'/delete_community_leader/'.$user->id).'" class="btn btn-xs btn-primary" data-toggle="modal" data-target="#confirm-delete"><i class="glyphicon glyphicon-trash"></i></a>&nbsp;':'';
                return $detail.$edit.$delete;
            });
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\User $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(User $model)
    {
        $query_result = DB::Table('users')->select([
                'users.id as id', 
                'users.first_name', 
                'users.last_name',
                'users.email',
                'users.country_code',
                DB::raw('CONCAT("+", users.country_code, " ", users.mobile_number) AS full_mobile_number'), 
                'users.status', 
                'users.referral_code', 
                'companies.name as company', 
                'stripe_subscription_plans.plan_name', 
                'users.created_at',
                DB::raw('CONCAT("XXXXXX",Right(users.mobile_number,4)) AS hidden_mobile'),
                DB::raw('(SELECT COUNT(u2.id) FROM users AS u2 WHERE u2.used_referral_code = users.referral_code AND u2.user_type = "Merchant") as merchants_count'),
                DB::raw('(SELECT COUNT(u2.id) FROM users AS u2 WHERE u2.used_referral_code = users.referral_code AND u2.user_type = "Driver") as drivers_count'),
                DB::raw('(SELECT COUNT(o2.id) FROM delivery_orders AS o2 WHERE o2.driver_id IN (SELECT u2.id FROM users AS u2 WHERE u2.used_referral_code = users.referral_code AND u2.user_type = "Driver") AND o2.status = "delivered") as deliveries_count')
            ])
            ->leftJoin('companies', function($join) {
                $join->on('users.company_id', '=', 'companies.id');
            })
            ->leftJoin('stripe_subscriptions', function($join) {
                $join->on('users.id', '=', 'stripe_subscriptions.user_id');
            })
            ->leftJoin('stripe_subscription_plans', function($join) {
                $join->on('stripe_subscriptions.plan', '=', 'stripe_subscription_plans.id');
            })
            ->where('user_type','Driver')
            ->whereIn('plan_name', ['Regular', 'Founder', 'Executive'])
            ->groupBy('id');

        if (LOGIN_USER_TYPE=='company') {
            $query_result = $query_result->where('users.company_id', auth()->guard('company')->user()->id);
        }

        return $query_result;
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        return $this->builder()
                    ->setTableId('communityleaders-table')
                    ->columns($this->getColumns())
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
            Column::make('id'),
            Column::make('company')->name('companies.name'),
            Column::make('first_name'),
            Column::make('last_name'),
            Column::make('email'),
            Column::make('status'),
            Column::make('full_mobile_number')->title('Mobile phone'),
            Column::make('plan_name')->name('stripe_subscription_plans.plan_name')->searchable(false),
            Column::make('merchants_count')->searchable(false),
            Column::make('drivers_count')->searchable(false),
            Column::make('deliveries_count')->searchable(false),
            Column::make('created_at'),
            Column::make('action')
                ->exportable(false)
                ->printable(false)
                ->orderable(false)
                ->searchable(false)
                ->addClass('text-center'),
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'CommunityLeaders_' . date('YmdHis');
    }
}
