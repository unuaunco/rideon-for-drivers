<?php

namespace App\DataTables;

use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Html\Editor\Editor;

use App\Models\User;

use DB;

class AffiliateDataTable extends DataTable
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
                $edit = (LOGIN_USER_TYPE=='company' || auth('admin')->user()->can('update_affiliate')) ? '<a href="'.url(LOGIN_USER_TYPE.'/edit_affiliate/'.$users->id).'" class="btn btn-xs btn-primary"><i class="glyphicon glyphicon-edit"></i></a>&nbsp;' : '';
                $delete = (auth()->guard('company')->user()!=null || auth('admin')->user()->can('delete_affiliate')) ? '<a data-href="'.url(LOGIN_USER_TYPE.'/delete_affiliate/'.$users->id).'" class="btn btn-xs btn-danger" data-toggle="modal" data-target="#confirm-delete"><i class="glyphicon glyphicon-trash"></i></a>&nbsp;':'';
                return $edit.$delete;
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
        return $model->select(
            'users.id as id',
            'users.first_name as first_name',
            'users.last_name as last_name',
            'users.email as email',
            'users.country_code as country_code',
            'users.referral_code as referral_code',
            DB::raw('CONCAT("+", users.country_code, " ", users.mobile_number) AS full_mobile_number'), 
            'users.status as status',
            'users.created_at as created_at',
            'affiliates.trading_name as trading_name',
            DB::raw('CONCAT("XXXXXX",Right(users.mobile_number,4)) AS hidden_mobile'),
            DB::raw('CONCAT(driver_address.city, ", ", driver_address.state) AS driver_location')
            )
            ->leftJoin('companies', function($join) {
                $join->on('users.company_id', '=', 'companies.id');
            })
            ->leftJoin('driver_address', function($join) {
                $join->on('users.id', '=', 'driver_address.user_id');
            })
            ->leftJoin('affiliates', function($join) {
                $join->on('users.id', '=', 'affiliates.user_id');
            })
            ->where('user_type','Affiliate');
            
            
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        
        return $this->builder()
                    ->setTableId('affiliate-table')
                    ->columns($this->getColumns())
                    ->pageLength(25)
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
            Column::make('id')->title('ID'),
            Column::make('referral_code')->title('Affiliate Code'),
            Column::make('first_name'),
            Column::make('last_name'),
            Column::make('trading_name')->name('affiliates.trading_name'),
            Column::make('driver_location')->name('affiliates.trading_name')->title('Location'),
            Column::make('full_mobile_number')->title('Contact phone'),
            Column::make('email'),
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
        return 'Affiliate_' . date('YmdHis');
    }
}
