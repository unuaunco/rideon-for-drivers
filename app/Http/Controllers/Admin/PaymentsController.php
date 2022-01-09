<?php

/**
 * Payments Controller
 *
 * @package     RideOnForDrivers
 * @subpackage  Controller
 * @category    Payments
 * @author      RideOn Team (2020)
 * @version     2.2
 * @link        https://www.joinrideon.com/
 */

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\DataTables\PaymentsDataTable;

class PaymentsController extends Controller
{
    /**
     * Load Datatable for Rating
     *
     * @param array $dataTable  Instance of PaymentsDataTable
     * @return datatable
     */
    public function index(PaymentsDataTable $dataTable)
    {
        return $dataTable->render('admin.payments.payments');
    }
}