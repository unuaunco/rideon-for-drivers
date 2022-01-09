<?php

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TrackingPageController extends Controller
{
    public function index($trackingId){
        return view('tracking.index')->with('trackingId', $trackingId);
    }
}
