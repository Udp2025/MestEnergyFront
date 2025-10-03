@extends('layouts.app')

@section('title', 'Inicio')

@section('content')

<link rel="stylesheet" href="{{ asset('css/inicio.css') }}">

<div class="dashboard">
  
  <section class="summary-cards cardbg">
    <div class="summary-card cardbg">
      <span class="summary-number">2</span>
      <span class="summary-label">Sites</span>
    </div>
    <div class="summary-card cardbg">
      <span class="summary-number">46.9 <small>kW</small></span>
      <span class="summary-label">Managed load</span>
    </div>
    <div class="summary-card cardbg">
      <span class="summary-number">18</span>
      <span class="summary-label">Sensors</span>
    </div>
    <div class="summary-card cardbg">
      <span class="summary-number">0</span>
      <span class="summary-label">External meters</span>
    </div>
    <div class="summary-card cardbg">
      <span class="summary-number">7</span>
      <span class="summary-label">Loggers</span>
    </div>
  </section>


  <!-- Filter and View Options -->
  <div class="filter-bar cardbg">
    <input type="text" id="filterInput" placeholder="Search..." onkeyup="filterCards()">
    <div class="view-icons">
      <i class="fas fa-map" onclick="changeView('map')"></i>
      <i class="fas fa-th-large" onclick="changeView('grid')"></i>
      <i class="fas fa-list" onclick="changeView('list')"></i>
    </div>
  </div>

  <!-- Cards Container -->
  <div class="cards-container cardbg">
    <!-- Tarjeta inferior 1 -->
    <div class="site-card cardbg" data-site="Visualize" data-sites="1" data-loggers="0" data-sensors="131" data-bridges="0">
      <h3>DKDA SHOES</h3>
      <p><i class="fas fa-location-arrow"></i> San Francisco del Rincón, Mexico</p>
      <p>
        <i class="fas fa-cogs"></i>
        <span class="site-label">Sites:</span> <span class="site-value">1</span> | 
        <span class="loggers-label">Loggers:</span> <span class="loggers-value">0</span> | 
        <span class="sensors-label">Sensors:</span> <span class="sensors-value">131</span> | 
        <span class="bridges-label">Bridges:</span> <span class="bridges-value">0</span>
      </p>
      <div class="arrow-icon">
        <a href="{{ route('visualize') }}">
          <i class="fas fa-arrow-circle-right"></i>
        </a>
      </div>
    </div>

    <!-- Tarjeta inferior 2 -->
    <div class="site-card cardbg" data-site="Optimize" data-sites="1" data-loggers="0" data-sensors="131" data-bridges="40">
      <h3>LAPROBA EL ÁGUILA SA DE CV</h3>
      <p><i class="fas fa-location-arrow"></i> A Santa Ana del Conde , León de los Aldama, Mexico</p>
      <p>
        <i class="fas fa-cogs"></i>
        <span class="site-label">Sites:</span> <span class="site-value">1</span> | 
        <span class="loggers-label">Loggers:</span> <span class="loggers-value">0</span> | 
        <span class="sensors-label">Sensors:</span> <span class="sensors-value">131</span> | 
        <span class="bridges-label">Bridges:</span> <span class="bridges-value">40</span>
      </p>
      <div class="arrow-icon">
        <a href="{{ route('optimize') }}">
          <i class="fas fa-arrow-circle-right"></i>
        </a>
      </div>
    </div>
  </div>
</div>

<script>
  
  const SITE = @json($auth_user_site ?? session('site') ?? null);

  console.log('site:', SITE);
  
</script>

<script src="{{ asset('js/inicio.js') }}"></script>


<script src="{{ asset('js/inicio.js') }}"></script>

@endsection
