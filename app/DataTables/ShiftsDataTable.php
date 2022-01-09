<?php
/**
 * Shifts DataTable
 *
 * @package     Rideon Driver
 * @subpackage  DataTable
 * @category    Shifts
 * @author      ketan@fill_email.com & pardusurbanus@protonmail.com
 * @version     2.2
 * @link        https://rideon.co
 */

namespace App\DataTables;

use App\Models\Shift;

use Yajra\DataTables\QueryDataTable;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Html\Editor\Editor;

use DB;

class ShiftsDataTable extends DataTable
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
            ->addColumn('action', function ($query) {
                $edit = (LOGIN_USER_TYPE=='company' || auth('admin')->user()->can('update_shift')) ? '<a data-href="'.url(LOGIN_USER_TYPE.'/edit_shift/'.$query->id).'" class="btn btn-xs btn-primary edit-shift-btn" data-toggle="modal" data-target="#confirm-shift-edit" title="Edit shift"><i class="glyphicon glyphicon-edit"></i></a>&nbsp;' : '';
                $delete = (auth()->guard('company')->user()!=null || auth('admin')->user()->can('delete_shift')) ? '<a data-href="'.url(LOGIN_USER_TYPE.'/delete_shift/'.$query->id).'" class="btn btn-xs btn-danger" data-toggle="modal" data-target="#confirm-delete" title="Delete shift"><i class="glyphicon glyphicon-trash"></i></a>':'';
                return $edit.$delete;
            })
            ->filterColumn('driver', function($query, $keyword) {
                $sql = 'CONCAT(shifts.driver_id," - ",driver.first_name) LIKE ?';
                $query->whereRaw($sql, ["%{$keyword}%"]);
            });
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Shift $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Shift $model)
    {
        return Shift::whereIn('shifts.status', ['scheduled', 'in_progress', 'finished', 'cancelled'])
            ->leftJoin('users as driver', function($join) {
                $join->on('driver.id', '=', 'shifts.driver_id');
            })
            ->leftjoin('currency', function($join) {
                $join->on('currency.id', '=', 'shifts.currency');
            })
            ->select([
                'shifts.id as id',
                'shifts.amount as amount',
                'currency.code as currency',
                'shifts.shift_start as shift_start',
                'shifts.shift_end as shift_end',
                'shifts.amount as amount',
                'shifts.status as status',
                DB::raw('CONCAT(shifts.driver_id," - ",driver.first_name) as driver')
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
                    ->setTableId('shifts-table')
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
            Column::make('driver'),
            Column::make('shift_start'),
            Column::make('shift_end'),
            Column::make('amount'),
            Column::make('currency'),
            Column::make('status'),
            Column::computed('action', 'Action')
                ->exportable(false)
                ->printable(false)
                ->orderable(false)
                ->searchable(false)
                ->addClass('text-center')->addClass('th-lg')
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'Shifts_' . date('YmdHis');
    }
}
