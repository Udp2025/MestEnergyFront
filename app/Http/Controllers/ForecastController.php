<?php

namespace App\Http\Controllers;

class ForecastController extends Controller
{
    /**
     * Display the forecast page.
     */
    public function index()
    {
        return view('forecast');
    }
}
