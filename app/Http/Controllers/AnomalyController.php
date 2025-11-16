<?php

namespace App\Http\Controllers;

class AnomalyController extends Controller
{
    /**
     * Display the anomaly detection view.
     */
    public function index()
    {
        return view('anomaly');
    }
}
