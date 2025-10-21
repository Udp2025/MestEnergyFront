

@extends('layouts.app')

@section('title', 'Datos CFE')

@section('content')

<link rel="stylesheet" href="{{ asset('css/datos_cfe.css') }}">

<div class="page-wrap">
  <!-- Top selectors card -->
  <div class="filters-card">
    <div class="filter-item">
      <label for="factor_carga">factor_carga (Grupo Tarifario)</label>
      <select id="factor_carga" name="factor_carga">
        <option value="">Selecciona grupo tarifario</option>
      </select>
    </div>


<div class="filter-item">
  <label for="id_tarifa_region">id_tarifa_region</label>
  <select id="id_tarifa_region" name="id_tarifa_region">
    <option value="">TR-41 — Bajío</option>
  </select>
  <div class="small-note">Región: Bajío</div>
</div>

<div class="filter-item">
  <label for="site_id">site_id</label>
  <select id="site_id" name="site_id">
    <option value="">Selecciona el sitio / cliente</option>
  </select>
</div>


  </div>

  <!-- Main card -->

  <div class="main-card">
    <div class="card-header">
      <div>
        <h2>Industria — valores editables por mes</h2>
        <p class="muted">Columnas: Base, Intermedia, Punta (Variable Energía), Distribución, Capacidad</p>
      </div>
      <div class="card-actions">
        <button class="btn btn-light" id="importCsv">
          <!-- simple svg icon -->
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden>
            <path d="M12 3v10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M8 7l4-4 4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M21 21H3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          Importar CSV
        </button>
      </div>
    </div>


<div class="table-wrap">
  <table class="values-table">
    <thead>
      <tr>
        <th>Mes</th>
        <th>
          <div class="col-title">Base<br><small>Variable (Energía)</small></div>
        </th>
        <th>
          <div class="col-title">Intermedia<br><small>Variable (Energía)</small></div>
        </th>
        <th>
          <div class="col-title">Punta<br><small>Variable (Energía)</small></div>
        </th>
        <th>
          <div class="col-title">Distribución</div>
        </th>
        <th>
          <div class="col-title">Capacidad</div>
        </th>
      </tr>
    </thead>
    <tbody>
      @php
        $meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
      @endphp

      @foreach($meses as $m)
      <tr>
        <td class="mes">{{ $m }}</td>
        <td><input type="text" class="num" name="base[{{ $m }}]" value="0" /></td>
        <td><input type="text" class="num" name="intermedia[{{ $m }}]" value="0" /></td>
        <td><input type="text" class="num" name="punta[{{ $m }}]" value="0" /></td>
        <td><input type="text" class="num" name="distribucion[{{ $m }}]" value="0" /></td>
        <td><input type="text" class="num" name="capacidad[{{ $m }}]" value="0" /></td>
      </tr>
      @endforeach

      <tr class="totals-row">
        <td>Total anual</td>
        <td class="total" data-col="base">0.0000</td>
        <td class="total" data-col="intermedia">0.0000</td>
        <td class="total" data-col="punta">0.0000</td>
        <td class="total" data-col="distribucion">0.0000</td>
        <td class="total" data-col="capacidad">0.0000</td>
      </tr>
    </tbody>
  </table>
</div>

<div class="card-footer">
  <div></div>
  <div class="actions-right">
    <button class="btn btn-outline">Validar</button>
    <button class="btn btn-primary">Guardar</button>
  </div>
</div>


  </div>
</div>

<!-- Scripts: calcula totales y formatea -->

<script>
  (function(){
    function floatVal(v){
      var n = parseFloat(String(v).replace(/,/g,'.'));
      return isNaN(n) ? 0 : n;
    }

    function updateTotals(){
      const cols = ['base','intermedia','punta','distribucion','capacidad'];
      cols.forEach(function(col, idx){
        let sum = 0;
        document.querySelectorAll('input[name^="'+col+'["]').forEach(function(inp){
          sum += floatVal(inp.value);
        });
        const el = document.querySelector('.total[data-col="'+col+'"]');
        if(el) el.textContent = sum.toFixed(4);
      });
    }

    // attach events
    document.querySelectorAll('.num').forEach(function(inp){
      inp.addEventListener('input', function(e){
        // allow numbers, . and , and -
        this.value = this.value.replace(/[^0-9\.,\-]/g,'');
        updateTotals();
      });
      inp.addEventListener('blur', function(){
        // normalize comma -> dot
        this.value = (parseFloat(this.value.replace(/,/g,'.')) || 0);
        updateTotals();
      });
    });

    // initial totals
    document.addEventListener('DOMContentLoaded', updateTotals);
    updateTotals();
  })();
</script>

@endsection

