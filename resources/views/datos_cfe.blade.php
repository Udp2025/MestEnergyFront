@extends('layouts.app')

@section('title', 'Datos CFE')

@section('content')

<link rel="stylesheet" href="{{ asset('css/datos_cfe.css') }}">

@if(session('success'))
  <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
  <div class="alert alert-danger">{{ session('error') }}</div>
@endif

@if ($errors->any())
  <div class="alert alert-warning">
    <strong>Errores de validación:</strong>
    <ul>
      @foreach ($errors->all() as $err)
        <li>{{ $err }}</li>
      @endforeach
    </ul>
  </div>
@endif


<div class="page-wrap">

  <!-- Top selectors card -->
   <!-- Top selectors card -->
  <div class="filters-card">
    <div class="filter-item">
      <label for="region_select">Región (tarifa_region)</label>
      <select id="region_select" name="region_select">
        <option value="">Selecciona región</option>
        @if(isset($regions) && $regions->count())
          @foreach($regions as $reg)
            <option value="{{ $reg->id }}" {{ old('region_select') == $reg->id ? 'selected' : '' }}>
              {{ $reg->region }}
            </option>
          @endforeach
        @endif
      </select>
    </div>

    <div class="filter-item">
      <label for="fijo">Fijo (cargo fijo)</label>
      <input id="fijo" name="fijo" type="text" value="{{ old('fijo', '0') }}" />
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

      <input type="hidden" name="region_select" id="region_select_hidden" value="{{ old('region_select', '') }}">
      <input type="hidden" name="fijo" id="fijo_hidden" value="{{ old('fijo', '0') }}">

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

    // referencias
    const regionSelect = document.getElementById('region_select');         // select visible (puede estar fuera del form)
    const regionHidden = document.getElementById('region_select_hidden'); // hidden dentro del form
    const fijoInp = document.getElementById('fijo');                     // input visible (puede estar fuera del form)
    const fijoHidden = document.getElementById('fijo_hidden');           // hidden dentro del form
    const cfeForm = document.getElementById('cfeForm');

    // sanitize inputs and event attach (numerical inputs en la tabla)
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

    // Inicializar totales al cargar
    document.addEventListener('DOMContentLoaded', function(){
      // asegurar que los hidden reflejen los visibles al cargar
      if(regionSelect && regionHidden) regionHidden.value = regionSelect.value || regionHidden.value;
      if(fijoInp && fijoHidden) fijoHidden.value = fijoInp.value || fijoHidden.value;

      updateTotals();
    });

    // cuando cambia el select de región -> sincronizar hidden y obtener defaults
    if(regionSelect){
      regionSelect.addEventListener('change', function(){
        const regionId = this.value;
        if(regionHidden) regionHidden.value = regionId || '';

        if(!regionId) return;
        fetch('/cfe/region?region_id=' + encodeURIComponent(regionId))
          .then(r => r.json())
          .then(data => {
            if(!data) return;
            var fijoVal = (data.fijo !== null && data.fijo !== undefined) ? data.fijo : 0;

            // actualizar visible y hidden de fijo
            if(fijoInp) fijoInp.value = fijoVal;
            if(fijoHidden) fijoHidden.value = fijoVal;

            // seed meses con valores retornados
            const v_base = (data.variable_base !== undefined && data.variable_base !== null) ? data.variable_base : 0;
            const v_inter = (data.variable_intermedia !== undefined && data.variable_intermedia !== null) ? data.variable_intermedia : 0;
            const v_punta = (data.variable_punta !== undefined && data.variable_punta !== null) ? data.variable_punta : 0;
            const v_dist = (data.distribucion !== undefined && data.distribucion !== null) ? data.distribucion : 0;
            const v_cap = (data.capacidad !== undefined && data.capacidad !== null) ? data.capacidad : 0;

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
    }

    // sincronizar el input fijo visible con su hidden cuando el usuario escriba
    if(fijoInp && fijoHidden){
      // inicial
      fijoHidden.value = fijoInp.value || fijoHidden.value;

      fijoInp.addEventListener('input', function(){
        this.value = this.value.replace(/[^0-9\.,\-]/g,'');
        fijoHidden.value = this.value;
      });

      fijoInp.addEventListener('blur', function(){
        var v = parseFloat(this.value.replace(/,/g,'.'));
        this.value = isNaN(v) ? 0 : v;
        fijoHidden.value = this.value;
        updateTotals();
      });
    }

    // Antes de enviar el formulario, aseguramos que los hidden contienen los valores visibles
    if(cfeForm){
      cfeForm.addEventListener('submit', function(){
        if(regionSelect && regionHidden) regionHidden.value = regionSelect.value || regionHidden.value;
        if(fijoInp && fijoHidden) fijoHidden.value = fijoInp.value || fijoHidden.value;
      });
    }

  })();
</script>


@endsection
