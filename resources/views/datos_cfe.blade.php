@extends('layouts.app')

@section('title', 'Datos CFE')

@section('content')

<link rel="stylesheet" href="{{ asset('css/datos_cfe.css') }}">

<div class="page-wrap">

  <!-- Top selectors card -->
  <div class="filters-card">
    <div class="filter-item">
      <label for="region_select">Región (tarifa_region)</label>
      <select id="region_select" name="region_select">
        <option value="">Selecciona región</option>
        <option>BAJA CALIFORNIA</option>
        <option>BAJA CALIFORNIA SUR</option>
        <option>BAJIO</option>
        <option>CENTRO OCCIDENTE</option>
        <option>CENTRO ORIENTE</option>
        <option>CENTRO SUR</option>
        <option>GOLFO CENTRO</option>
        <option>GOLFO NORTE</option>
        <option>JALISCO</option>
        <option>NOROESTE</option>
        <option>NORTE</option>
        <option>ORIENTE</option>
        <option>PENINSULAR</option>
        <option>SURESTE</option>
        <option>VALLE DE MEXICO CENTRO</option>
        <option>VALLE DE MEXICO NORTE</option>
        <option>VALLE DE MEXICO SUR</option>
      </select>
    </div>

    <div class="filter-item">
      <label for="fijo">Fijo (cargo fijo)</label>
      <input id="fijo" name="fijo" type="text" value="0" />
      <div class="small-note">Cargo fijo por región</div>
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
        <button class="btn btn-light" id="importCsv">... Importar CSV</button>
      </div>
    </div>

    <!-- Form: envia los arrays de meses -->
    <form method="POST" action="{{ route('cfe.store') }}" id="cfeForm">
      @csrf

      <div class="table-wrap">
        <table class="values-table">
          <thead>
            <tr>
              <th>Mes</th>
              <th><div class="col-title">Base<br><small>Variable (Energía)</small></div></th>
              <th><div class="col-title">Intermedia<br><small>Variable (Energía)</small></div></th>
              <th><div class="col-title">Punta<br><small>Variable (Energía)</small></div></th>
              <th><div class="col-title">Distribución</div></th>
              <th><div class="col-title">Capacidad</div></th>
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
          <a class="btn btn-outline" id="btnValidar" href="javascript:void(0)">Validar</a>
          <button class="btn btn-primary" type="submit">Guardar</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Scripts: calcula totales, carga region y formatea -->
<script>
  (function(){
    function floatVal(v){
      var n = parseFloat(String(v).replace(/,/g,'.'));
      return isNaN(n) ? 0 : n;
    }

    function updateTotals(){
      const cols = ['base','intermedia','punta','distribucion','capacidad'];
      cols.forEach(function(col){
        let sum = 0;
        document.querySelectorAll('input[name^="'+col+'["]').forEach(function(inp){
          sum += floatVal(inp.value);
        });
        const el = document.querySelector('.total[data-col="'+col+'"]');
        if(el) el.textContent = sum.toFixed(4);
      });
    }

    // sanitize inputs and event attach
    document.querySelectorAll('.num').forEach(function(inp){
      inp.addEventListener('input', function(){
        this.value = this.value.replace(/[^0-9\.,\-]/g,'');
        updateTotals();
      });
      inp.addEventListener('blur', function(){
        var v = parseFloat(this.value.replace(/,/g,'.'));
        this.value = isNaN(v) ? 0 : v;
        updateTotals();
      });
    });

    // initial totals
    document.addEventListener('DOMContentLoaded', function(){
      updateTotals();
    });

    // when region changes => fetch defaults
    const regionSelect = document.getElementById('region_select');
    regionSelect && regionSelect.addEventListener('change', function(){
      const region = this.value;
      if(!region) return;
      // fetch defaults for region
      fetch('/cfe/region?region=' + encodeURIComponent(region))
        .then(r => r.json())
        .then(data => {
          if(!data) return;
          // set fijo
          document.getElementById('fijo').value = (data.fijo !== null && data.fijo !== undefined) ? data.fijo : 0;

          // if payload has variable_* values, seed all months with those values
          const v_base = data.variable_base ?? 0;
          const v_inter = data.variable_intermedia ?? 0;
          const v_punta = data.variable_punta ?? 0;
          const v_dist = data.distribucion ?? 0;
          const v_cap = data.capacidad ?? 0;

          document.querySelectorAll('input[name^="base["]').forEach(i => i.value = v_base);
          document.querySelectorAll('input[name^="intermedia["]').forEach(i => i.value = v_inter);
          document.querySelectorAll('input[name^="punta["]').forEach(i => i.value = v_punta);
          document.querySelectorAll('input[name^="distribucion["]').forEach(i => i.value = v_dist);
          document.querySelectorAll('input[name^="capacidad["]').forEach(i => i.value = v_cap);

          updateTotals();
        })
        .catch(err => {
          console.error('Error al cargar región', err);
        });
    });

    // if user changes the fijo field, allow numbers only
    const fijoInp = document.getElementById('fijo');
    fijoInp && fijoInp.addEventListener('input', function(){
      this.value = this.value.replace(/[^0-9\.,\-]/g,'');
    });

  })();
</script>

@endsection
