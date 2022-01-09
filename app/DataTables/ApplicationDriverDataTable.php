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
use DB;

use Yajra\DataTables\QueryDataTable;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Html\Editor\Editor;

class ApplicationDriverDataTable extends DataTable
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
            ->addColumn('email', function ($application) {
                return protectedString($application->email);
            })
            ->addColumn('action', function ($application) {
                $download = (LOGIN_USER_TYPE=='company' || auth('admin')->user()->can('update_driver')) ? '<a href="'.$application->pdf.'" class="btn btn-xs btn-primary" target="_blank"><i class="glyphicon glyphicon-download-alt"></i></a>&nbsp;' : '';
                if ($application->status == 'Active'){
                    $check = '';
                }
                else{
                    $check = (auth()->guard('company')->user()!=null || auth('admin')->user()->can('update_driver')) ? '<a data-href="'.url(LOGIN_USER_TYPE.'/active_driver_application/'.$application->id).'" class="btn btn-xs btn-primary" data-toggle="modal" data-target="#confirm-active"><i class="glyphicon glyphicon-check"></i></a>&nbsp;':'';
                }
                return $download.$check;
            });
    }

    /**
     * Get query source of dataTable.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query()
    {
        return DB::Table('applications')
            ->where('applications.type', 'Driver')
            ->join('users', function($join){
                $join->on('applications.user_id', '=', 'users.id')
                    ->where('users.user_type', 'Driver');
            })
            ->select([
                'applications.id as id', 
                'applications.pdf as pdf', 
                'users.first_name as first_name', 
                'users.last_name as last_name',
                'users.email as email',
                'users.status as status',
                'users.created_at as created_at',
                DB::raw('CONCAT("+",users.country_code,users.mobile_number) as mobile_number')
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
            ->setTableId('driver-applications-table')
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
            Column::make('id')
                ->title('Application ID'),
            Column::make('first_name'),
            Column::make('last_name'),
            Column::make('email'),
            Column::make('mobile_number'),
            Column::make('status'),
            Column::make('created_at')
                ->title('Submitted at'),
            Column::make('action', 'Action')
                ->exportable(false)
                ->printable(false)
                ->orderable(false)
                ->searchable(false)
                ->addClass('text-center')
                ->addClass('th-lg'),
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'drivers_applications_' . date('YmdHis');
    }
}