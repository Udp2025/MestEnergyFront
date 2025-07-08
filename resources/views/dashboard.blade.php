@extends('layouts.complete')

@section('title', 'Dashboard')

@section('content')
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Energy Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/principal.css') }}">

     <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
 
     </head>
<body>
    <div class="dashboard-content">
        <!-- Header -->
        <header class="dashboard-header text-center">
            <h1>Energy</h1>
         </header>

        <!-- Statistics Row -->
        <div class="row g-3 mt-4">
            <div class="col-lg-3 col-md-6">
                <div class="dashboard-stat-card">
                    <h3>Total Consumption</h3>
                    <p class="stat-value">1,240 kWh</p>
                    <p class="text-muted">This month</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="dashboard-stat-card">
                    <h3>Peak Usage</h3>
                    <p class="stat-value">320 kWh</p>
                    <p class="text-muted">Yesterday</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="dashboard-stat-card">
                    <h3>Average Daily</h3>
                    <p class="stat-value">41 kWh</p>
                    <p class="text-muted">Last 7 days</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="dashboard-stat-card">
                    <h3>Cost Estimate</h3>
                    <p class="stat-value">$98.5</p>
                    <p class="text-muted">This month</p>
                </div>
            </div>
        </div>

        <!-- Graphs Row -->
        <div class="row g-3 mt-4">
            <div class="col-lg-6">
                <div class="dashboard-card">
                    <h3>Hourly Consumption</h3>
                    <canvas id="hourlyChart"></canvas>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="dashboard-card">
                    <h3>Monthly Trends</h3>
                    <canvas id="monthlyChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Second Graphs Row -->
        <div class="row g-3 mt-4">
            <div class="col-lg-6">
                <div class="dashboard-card">
                    <h3>Energy Sources</h3>
                    <canvas id="sourcesChart"></canvas>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="dashboard-card">
                    <h3>Weekly Comparison</h3>
                    <canvas id="comparisonChart"></canvas>
                </div>
            </div>
        </div>
    </div>

     <script>
        
    </script>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="{{ asset('js/principal.js') }}"></script>

  
    

 </body>
</html>



@endsection
