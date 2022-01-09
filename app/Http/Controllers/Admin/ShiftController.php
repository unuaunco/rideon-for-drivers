<?php

/**
 * Shift Controller
 *
 * @package     RideOnForDrivers
 * @subpackage  Controller
 * @category    Shift
 * @author      RideOn Team (2020)
 * @version     2.2
 * @link        https://www.joinrideon.com/
 */

namespace App\Http\Controllers\Admin;

use App\DataTables\ShiftsDataTable;
use App\Http\Controllers\Controller;
use App\Http\Start\Helpers;
use App\Models\Admin;
use App\Models\Currency;
use App\Models\Shift;
use App\Models\User;
use Auth;
use Illuminate\Http\Request;
use Redirect;
use Validator;

class ShiftController extends Controller
{
    protected $helper; // Global variable for instance of Helpers

    public function __construct()
    {
        $this->helper = new Helpers;
    }

    /**
     * Load Datatable for Admin Users
     *
     * @param array $dataTable  Instance of AdminuserDataTable
     * @return datatable
     */
    public function view(ShiftsDataTable $dataTable)
    {
        return $dataTable->render('admin.shifts.view');
    }

    /**
     * Add shift Details
     *
     * @param array $request    Input values
     * @return redirect     to shift View
     */
    public function add(Request $request)
    {
        // Add shift validation rules
        $rules = array(
            'driver_id' => 'required',
            'shift_start' => 'required',
            'shift_end' => 'required',
            'amount' => 'required',
            'currency' => 'required',
            'status' => 'required',
        );

        // Add shift Validation Custom Names
        $attributes = array(
            'driver_id' => 'DriverName',
            'shift_start' => 'ShiftStart',
            'shift_end' => 'ShiftEnd',
            'amount' => 'Amount',
            'currency' => 'Currency',
            'status' => 'Status',
        );

        $validator = Validator::make($request->all(), $rules);
        $validator->setAttributeNames($attributes);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput(); // Form calling with Errors and Input values
        } else {
            $driver_id = $request->get('driver_id');
            $shift_start = date('Y-m-d H:i:s', strtotime($request->get('shift_start')));
            $shift_end = date('Y-m-d H:i:s', strtotime($request->get('shift_end')));
            $amount = $request->get('amount');
            $currency = $request->get('currency');
            $status = $request->get('status');

            $objShift = new Shift();
            $objShift->driver_id = $driver_id;
            $objShift->shift_start = $shift_start;
            $objShift->shift_end = $shift_end;
            $objShift->amount = $amount;
            $objShift->currency = $currency;
            $objShift->status = $status;
            $objShift->save();
            // Call flash message function
            $this->helper->flash_message('success', 'Added Successfully.');
            return redirect('admin/shifts');
        }
        $this->helper->flash_message('danger', 'Something went wrong.');
        return redirect('admin/shifts');
    }

    /**
     * Update shift Details
     *
     * @param array $request    Input values
     * @return redirect     to shift View
     */
    public function update(Request $request)
    {

        if($request->isMethod("GET")) {
            //Inactive Company could not add driver
            if (LOGIN_USER_TYPE=='company' && Auth::guard('company')->user()->status != 'Active') {
                abort(404);
            }

            $data = Shift::find($request->id);

            if($data) {
                return response()->json($data);
            }

            flashMessage('danger', 'Invalid ID');
            return redirect(LOGIN_USER_TYPE.'/shifts');
        }
        if($request->isMethod("POST")) {
            logger($request->id);
            // edit shift validation rules
            $rules = array(
                'driver_id' => 'required',
                'shift_start' => 'required',
                'shift_end' => 'required',
                'amount' => 'required',
                'currency' => 'required',
                'status' => 'required',
            );

            // edit shift Validation Custom Names
            $attributes = array(
                'driver_id' => 'DriverName',
                'shift_start' => 'ShiftStart',
                'shift_end' => 'ShiftEnd',
                'amount' => 'Amount',
                'currency' => 'Currency',
                'status' => 'Status',
            );

            $validator = Validator::make($request->all(), $rules);
            $validator->setAttributeNames($attributes);

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput(); // Form calling with Errors and Input values
            } else {
                $driver_id = $request->get('driver_id');
                $shift_start = date('Y-m-d H:i:s', strtotime($request->get('shift_start')));
                $shift_end = date('Y-m-d H:i:s', strtotime($request->get('shift_end')));
                $amount = $request->get('amount');
                $currency = $request->get('currency');
                $status = $request->get('status');

                $objShift = Shift::find($request->id);
                $objShift->driver_id = empty($driver_id) ? $objShift->driver_id : $driver_id;
                $objShift->shift_start = empty($shift_start) ? $objShift->shift_start : $shift_start;
                $objShift->shift_end = empty($shift_end) ? $objShift->shift_end : $shift_end;
                $objShift->amount = empty($amount) ? $objShift->amount : $amount;
                $objShift->currency = empty($currency) ? $objShift->currency : $currency;
                $objShift->status = empty($status) ? $objShift->status : $status;
                $objShift->save();
                // Call flash message function
                $this->helper->flash_message('success', 'Updated Successfully.');
                return redirect('admin/shifts');
            }
            $this->helper->flash_message('danger', 'Something went wrong.');
            return redirect('admin/shifts');
        }
    }

    /**
     * Delete shift
     *
     * @param array $request    Input values
     * @return redirect     to shift View
     */
    public function delete(Request $request)
    {
        // delete shift validation rules
        $rules = array(
            'id' => 'required',
        );
        Shift::find($request->id)->delete();
        $this->helper->flash_message('success', 'Deleted Successfully.');
        return redirect('admin/shifts');
    }

    /**
     * Import shifts from csv
     *
     * @param array $request  csv file
     * @return redirect     to Import shifts view
     */
    public function importShifts(Request $request)
    {
        if (!$_POST) {
            return view('admin.imports.import_shift.import');
        } else {
            if ($request->input('submit') != null) {
                $file = $request->file('file');

                // File Details
                $filename = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();

                // Valid File Extensions
                $valid_extension = array("csv","xlsx");

                // Check file extension
                if (in_array(strtolower($extension), $valid_extension)) {

                    // File upload location
                    $location = 'uploads';

                    // Upload file
                    $file->move($location, $filename);

                    // Import CSV to Database
                    $filepath = public_path($location . "/" . $filename);

                    // Reading file
                    $file = fopen($filepath, "r");

                    $importData_arr = array();
                    $i = 0;

                    while (($filedata = fgetcsv($file, 1000, ",")) !== false) {
                        $num = count($filedata);

                        // Skip first row (Remove below comment if you want to skip the first row)
                        if ($i == 0) {
                            $i++;
                            continue;
                        }
                        for ($c = 0; $c < $num; $c++) {
                            $importData_arr[$i][] = $filedata[$c];
                        }
                        $i++;
                    }
                    fclose($file);

                    $shifts_inserted = 0;

                    // Insert to MySQL database
                    foreach ($importData_arr as $index => $importData) {
                        if (isset($importData[0])) {

                            $driver_id = $importData[0];
                            $shift_start = $importData[1];
                            $shift_end = $importData[2];
                            $amount = $importData[3];
                            $currency = $importData[4];
                            $status = $importData[6];

                            $shift_data = [
                                'driver_id' => $driver_id,
                                'shift_start' => $shift_start,
                                'shift_end' => $shift_end,
                                'amount' => $amount,
                                'currency' => $currency,
                                'status' => $status,
                            ];

                            Shift::insert($shift_data);
                            $shifts_inserted += 1;
                        }
                    }

                    //Send response
                    $this->helper->flash_message('success', 'Succesfully imported: ' . $shifts_inserted . ' shifts'); // Call flash message function
                    return redirect(LOGIN_USER_TYPE . '/import_shifts');
                } else {
                    //Send response
                    $this->helper->flash_message('danger', 'Invalid file type'); // Call flash message function
                    return redirect(LOGIN_USER_TYPE . '/import_shifts');
                }
            }
        }
    }
}
