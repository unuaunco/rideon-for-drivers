<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use JWTAuth;
use Validator;

class SettingsController extends Controller
{
    public function __construct()
    {
        $this->request_helper = resolve("App\Http\Helper\RequestHelper");
    }

    /**
     * Get url for driver submission
     *
     * @param array $request  Input values
     * @return Json
     */
    public function getSubmissionsUrl(Request $request)
    {

        $sub_url = site_settings('driver_submission_url');

        $sub_data = array(
            'status_code'        => '1',
            'driver_submission_url'      => $sub_url,
        );

        return response()->json($sub_data, 200);
    }
}
