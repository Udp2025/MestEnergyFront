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

    <div class="main-card">
        <div class="card-header">
            <div>
                <h2>Industria — valores editables por mes</h2>
            </div>
            <div class="card-actions">
                <button class="btn btn-light" id="importCsv">📥 Importar CSV</button>
                <button class="btn btn-light" id="exportCsv">📤 Exportar CSV</button>
                <input type="file" id="csvFile" accept=".csv" style="display:none" />
            </div>
        </div>

        <form method="POST" action="{{ route('cfe.store') }}" id="cfeForm">
            @csrf
            <input type="hidden" name="region_select" id="region_select_hidden" value="{{ old('region_select', '') }}">
            <input type="hidden" name="fijo" id="fijo_hidden" value="{{ old('fijo', '0') }}">

            <div class="table-wrap">
                <table class="values-table">
                    <thead>
                        <tr>
                            <th>Mes</th>
                            <th><div class="col-title">Base<br></div></th>
                            <th><div class="col-title">Intermedia<br></div></th>
                            <th><div class="col-title">Punta<br></div></th>
                            <th><div class="col-title">Distribución</div></th>
                            <th><div class="col-title">Capacidad</div></th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $meses = [
                                'Enero' => 1, 'Febrero' => 2, 'Marzo' => 3, 'Abril' => 4,
                                'Mayo' => 5, 'Junio' => 6, 'Julio' => 7, 'Agosto' => 8,
                                'Septiembre' => 9, 'Octubre' => 10, 'Noviembre' => 11, 'Diciembre' => 12
                            ];
                        @endphp
                        @foreach($meses as $nombreMes => $numeroMes)
                            <tr>
                                <td class="mes">{{ $nombreMes }}</td>
                                <td><input type="text" class="num" name="base[{{ $nombreMes }}]" value="0" /></td>
                                <td><input type="text" class="num" name="intermedia[{{ $nombreMes }}]" value="0" /></td>
                                <td><input type="text" class="num" name="punta[{{ $nombreMes }}]" value="0" /></td>
                                <td><input type="text" class="num" name="distribucion[{{ $nombreMes }}]" value="0" /></td>
                                <td><input type="text" class="num" name="capacidad[{{ $nombreMes }}]" value="0" /></td>
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

<script>
(function(){
    // Definir meses como array de objetos {nombre, numero}
    const meses = [
        {nombre: 'Enero', numero: 1},
        {nombre: 'Febrero', numero: 2},
        {nombre: 'Marzo', numero: 3},
        {nombre: 'Abril', numero: 4},
        {nombre: 'Mayo', numero: 5},
        {nombre: 'Junio', numero: 6},
        {nombre: 'Julio', numero: 7},
        {nombre: 'Agosto', numero: 8},
        {nombre: 'Septiembre', numero: 9},
        {nombre: 'Octubre', numero: 10},
        {nombre: 'Noviembre', numero: 11},
        {nombre: 'Diciembre', numero: 12}
    ];

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

    const regionSelect = document.getElementById('region_select');
    const regionHidden = document.getElementById('region_select_hidden');
    const fijoInp = document.getElementById('fijo');
    const fijoHidden = document.getElementById('fijo_hidden');
    const cfeForm = document.getElementById('cfeForm');

    function attachNumEvents(){
        document.querySelectorAll('.num').forEach(function(inp){
            if (inp._hasEvents) return;
            inp._hasEvents = true;
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
    }
    attachNumEvents();

    document.addEventListener('DOMContentLoaded', function(){
        if(regionSelect && regionHidden) regionHidden.value = regionSelect.value || regionHidden.value;
        if(fijoInp && fijoHidden) fijoHidden.value = fijoInp.value || fijoHidden.value;
        updateTotals();
    });

    // region change -> obtener defaults (ahora trae el set más reciente por meses)
    if(regionSelect){
        regionSelect.addEventListener('change', function(){
            const regionId = this.value;
            if(regionHidden) regionHidden.value = regionId || '';
            if(!regionId) return;

            fetch('/cfe/region?region_id=' + encodeURIComponent(regionId))
                .then(r => r.json())
                .then(data => {
                    if(!data) return;

                    // si el backend retornó los meses individuales -> aplicar por mes
                    if(data.months && typeof data.months === 'object'){
                        // aplicar fijo si viene
                        if(typeof data.fijo !== 'undefined' && fijoInp){
                            fijoInp.value = data.fijo;
                            if(fijoHidden) fijoHidden.value = data.fijo;
                        }

                        Object.keys(data.months).forEach(function(m){
                            const v = data.months[m] || {};
                            // proteger con existencia de input
                            const baseEl = document.querySelector('input[name="base['+m+']"]');
                            if(baseEl) baseEl.value = (v.variable_base !== undefined ? v.variable_base : 0);
                            const interEl = document.querySelector('input[name="intermedia['+m+']"]');
                            if(interEl) interEl.value = (v.variable_intermedia !== undefined ? v.variable_intermedia : 0);
                            const puntaEl = document.querySelector('input[name="punta['+m+']"]');
                            if(puntaEl) puntaEl.value = (v.variable_punta !== undefined ? v.variable_punta : 0);
                            const distEl = document.querySelector('input[name="distribucion['+m+']"]');
                            if(distEl) distEl.value = (v.distribucion !== undefined ? v.distribucion : 0);
                            const capEl = document.querySelector('input[name="capacidad['+m+']"]');
                            if(capEl) capEl.value = (v.capacidad !== undefined ? v.capacidad : 0);
                        });
                        attachNumEvents();
                        updateTotals();
                        return;
                    }

                    // compatibilidad: si backend devolviera los valores uniformes (variable_base, etc.) — aplicar a todos
                    var fijoVal = (data.fijo !== null && data.fijo !== undefined) ? data.fijo : 0;
                    if(fijoInp) fijoInp.value = fijoVal;
                    if(fijoHidden) fijoHidden.value = fijoVal;

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
                    attachNumEvents();
                    updateTotals();
                })
                .catch(err => {
                    console.error('Error al cargar región', err);
                });
        });
    }

    // sincronizar fijo
    if(fijoInp && fijoHidden){
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

    if(cfeForm){
        cfeForm.addEventListener('submit', function(){
            if(regionSelect && regionHidden) regionHidden.value = regionSelect.value || regionHidden.value;
            if(fijoInp && fijoHidden) fijoHidden.value = fijoInp.value || fijoHidden.value;
        });
    }

    // -------------------
    // IMPORT CSV (cliente) -> ahora admite Fijo en primera linea "Fijo,697.99" o columna 'Fijo'
    // -------------------
    const importBtn = document.getElementById('importCsv');
    const fileInput = document.getElementById('csvFile');

    function parseCSVText(text){
        const rows = text.split(/\r?\n/).map(r => r.trim()).filter(r => r.length > 0);
        if(rows.length === 0) return {error: 'CSV vacío'};

        // posible primer linea: "Fijo,<valor>" o header con columnas incluyendo 'Fijo'
        let fijoFromCsv = null;
        // detectar primero si la primera línea es "Fijo,valor"
        const firstCols = rows[0].split(',').map(c => c.trim());
        if(firstCols[0].toLowerCase() === 'fijo' && firstCols.length >= 2){
            fijoFromCsv = firstCols[1];
            rows.shift(); // quitar la linea de fijo
        }

        // ahora evaluar si hay header
        const first = rows[0].split(',').map(h => h.trim().toLowerCase());
        const hasHeader = first.some(h => ['mes','base','intermedia','punta','distribucion','distribución','capacidad','fijo'].includes(h));

        let header = null;
        let dataRows = [];
        if(hasHeader){
            header = rows[0].split(',').map(h => h.trim().toLowerCase());
            dataRows = rows.slice(1);
        } else {
            dataRows = rows;
        }

        const result = {};
        // Inicializar con los nombres de los meses
        meses.forEach(m => {
            result[m.nombre] = { base:0, intermedia:0, punta:0, distribucion:0, capacidad:0 };
        });

        dataRows.forEach((r, idx) => {
            const cols = r.split(',').map(c => c.trim());
            if(hasHeader){
                const obj = {};
                header.forEach((h,i) => obj[h] = cols[i] !== undefined ? cols[i] : '');
                let mes = obj['mes'] || obj['month'] || '';
                mes = mes.trim();
                if(!mes){
                    mes = meses[idx]?.nombre || null;
                }
                if(!mes) return;

                const capital = mes.charAt(0).toUpperCase() + mes.slice(1).toLowerCase();
                
                // Usar mn.nombre en lugar de mn directamente
                const targetMes = meses.find(mn => 
                    mn.nombre.toLowerCase() === capital.toLowerCase() || 
                    mn.nombre.toLowerCase().startsWith(capital.toLowerCase().slice(0,3))
                );
                const keyMes = targetMes ? targetMes.nombre : capital;

                result[keyMes] = {
                    base: obj['base'] || obj['energia_base'] || obj['variable_base'] || obj['b'] || 0,
                    intermedia: obj['intermedia'] || obj['variable_intermedia'] || obj['i'] || 0,
                    punta: obj['punta'] || obj['variable_punta'] || obj['p'] || 0,
                    distribucion: obj['distribucion'] || obj['distribución'] || obj['d'] || 0,
                    capacidad: obj['capacidad'] || obj['c'] || 0
                };

                // también si el header trae 'fijo' lo guardamos
                if(obj['fijo'] !== undefined && obj['fijo'] !== '') {
                    fijoFromCsv = obj['fijo'];
                }
            } else {
                if(cols.length >= 6){
                    const mesRaw = cols[0];
                    const capital = mesRaw.charAt(0).toUpperCase() + mesRaw.slice(1).toLowerCase();
                    
                    // Usar mn.nombre en lugar de mn directamente
                    const targetMes = meses.find(mn => 
                        mn.nombre.toLowerCase() === capital.toLowerCase() || 
                        mn.nombre.toLowerCase().startsWith(capital.toLowerCase().slice(0,3))
                    );
                    const keyMes = targetMes ? targetMes.nombre : capital;

                    result[keyMes] = {
                        base: cols[1] || 0,
                        intermedia: cols[2] || 0,
                        punta: cols[3] || 0,
                        distribucion: cols[4] || 0,
                        capacidad: cols[5] || 0
                    };
                } else if (cols.length === 5) {
                    // fila sin mes -> tomar por orden
                    const mIndex = idx;
                    const keyMes = meses[mIndex]?.nombre || meses[0].nombre;
                    result[keyMes] = {
                        base: cols[0] || 0,
                        intermedia: cols[1] || 0,
                        punta: cols[2] || 0,
                        distribucion: cols[3] || 0,
                        capacidad: cols[4] || 0
                    };
                }
            }
        });
        return {data: result, fijo: fijoFromCsv};
    }

    importBtn && importBtn.addEventListener('click', function(){
        fileInput.click();
    });

    fileInput && fileInput.addEventListener('change', function(e){
        const f = e.target.files[0];
        if(!f) return;
        const reader = new FileReader();
        reader.onload = function(ev){
            const text = ev.target.result;
            const parsed = parseCSVText(text);
            if(parsed.error){
                alert('Error importando CSV: ' + parsed.error);
                return;
            }
            const data = parsed.data;
            
            // Recorrer los meses para asignar valores
            meses.forEach(function(mesObj){
                const mesKey = mesObj.nombre;
                const mesData = data[mesKey];
                
                if(mesData) {
                    const baseInput = document.querySelector('input[name="base['+mesKey+']"]');
                    if(!baseInput) return;
                    
                    baseInput.value = mesData.base || 0;
                    document.querySelector('input[name="intermedia['+mesKey+']"]').value = mesData.intermedia || 0;
                    document.querySelector('input[name="punta['+mesKey+']"]').value = mesData.punta || 0;
                    document.querySelector('input[name="distribucion['+mesKey+']"]').value = mesData.distribucion || 0;
                    document.querySelector('input[name="capacidad['+mesKey+']"]').value = mesData.capacidad || 0;
                }
            });
            
            // aplicar fijo si vino en CSV
            if(parsed.fijo !== null && parsed.fijo !== undefined && fijoInp){
                fijoInp.value = parsed.fijo;
                if(fijoHidden) fijoHidden.value = parsed.fijo;
            }
            
            attachNumEvents();
            updateTotals();
            alert('CSV importado y aplicado en la tabla.');
            fileInput.value = '';
        };
        reader.readAsText(f, 'UTF-8');
    });

    // -------------------
    // EXPORT CSV (cliente) -> ahora incluye Fijo en primera linea y nombre de region en filename
    // -------------------
    const exportBtn = document.getElementById('exportCsv');
    exportBtn && exportBtn.addEventListener('click', function(){
        const fijoVal = fijoInp ? (fijoInp.value || 0) : 0;
        const headers = ['Mes','Base','Intermedia','Punta','Distribucion','Capacidad'];
        let csv = '';
        
        // linea de fijo primero
        csv += ['Fijo', fijoVal].join(',') + '\n\n';
        csv += headers.join(',') + '\n';
        
        meses.forEach(function(mesObj){
            const m = mesObj.nombre;
            const b = document.querySelector('input[name="base['+m+']"]').value || 0;
            const i = document.querySelector('input[name="intermedia['+m+']"]').value || 0;
            const p = document.querySelector('input[name="punta['+m+']"]').value || 0;
            const d = document.querySelector('input[name="distribucion['+m+']"]').value || 0;
            const c = document.querySelector('input[name="capacidad['+m+']"]').value || 0;
            csv += [m, b, i, p, d, c].join(',') + '\n';
        });
        
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        
        // nombre del archivo con region
        let regionName = 'region';
        if(regionSelect && regionSelect.selectedIndex >= 0){
            regionName = regionSelect.options[regionSelect.selectedIndex].text || regionName;
        }
        // sanitizar nombre
        regionName = regionName.replace(/[^0-9a-zA-Z-_]/g, '_');
        const filename = 'tarifas_cfe_' + regionName + '_' + (new Date()).toISOString().slice(0,10) + '.csv';
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        setTimeout(function(){
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }, 1000);
    });
})();
</script>
@endsection