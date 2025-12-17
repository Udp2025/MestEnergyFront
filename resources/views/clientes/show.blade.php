@extends('layouts.complete')

@section('title', 'Cliente - ' . $cliente->nombre)

@section('content')

@php
    $user = $cliente->user;
    $files = $cliente->files ?? collect();
    $infoFiscal = $cliente->infoFiscal;
    $plan = $cliente->planUsuario;
    $canManageContract = auth()->user()?->isSuperAdmin();

    $clientFields = [
        'Nombre' => $cliente->nombre,
        'RFC' => $cliente->rfc,
        'Email' => $cliente->email,
        'Teléfono' => $cliente->telefono ?? '-',
        'Calle' => $cliente->calle ?? '-',
        'Número' => $cliente->numero ?? '-',
        'Colonia' => $cliente->colonia ?? '-',
        'Código Postal' => $cliente->codigo_postal ?? '-',
        'Ciudad' => $cliente->ciudad ?? '-',
        'Estado' => $cliente->estado ?? '-',
        'País' => $cliente->pais ?? '-',
        'Cambio Dólar' => $cliente->cambio_dolar ?? '-',
        'Site (ID)' => $cliente->site ?? '-',
        'Tarifa Región' => $cliente->tarifa_region ?? '-',
        'Factor de Carga' => $cliente->factor_carga ?? '-',
        'Latitud' => $cliente->latitud ?? '-',
        'Longitud' => $cliente->longitud ?? '-',
        'Contacto Nombre' => $cliente->contacto_nombre ?? '-',
        'Capacitación' => is_null($cliente->capacitacion) ? '-' : ($cliente->capacitacion ? 'Sí' : 'No'),
        'Creado' => optional($cliente->created_at)->format('d/m/Y H:i') ?? '-',
        'Última actualización' => optional($cliente->updated_at)->format('d/m/Y H:i') ?? '-',
    ];
@endphp

<link rel="stylesheet" href="{{ asset('css/clientes_show.css') }}">

<div class="clientes-header">
    <h1 class="clientes-titulo">Información del cliente</h1>
</div>

<div class="container">

    <!-- Perfil -->
    <div class="profile-header">
        <div class="profile-image">
            @php
                $profilePath = $user->profile_image ?? null;
                $profileUrl = null;
                if ($profilePath) {
                    $defaultDisk = config('filesystems.default', 'public');
                    $storage = \Illuminate\Support\Facades\Storage::disk($defaultDisk);
                    if ($storage->exists($profilePath)) {
                        $profileUrl = $storage->url($profilePath);
                    } elseif (\Illuminate\Support\Facades\Storage::disk('public')->exists($profilePath)) {
                        $profileUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($profilePath);
                    }
                }
            @endphp
            @if($profileUrl)
                <img src="{{ $profileUrl }}" alt="{{ $user->name }}">
            @else
                <span>{{ strtoupper(substr($cliente->nombre, 0, 1)) }}</span>
            @endif
        </div>
        <div class="profile-info">
            <h2>{{ $cliente->nombre }}</h2>
            <p>{{ $user?->name ?? ($cliente->contacto_nombre ?? '-') }}</p>
        </div>
    </div>

    <!-- Estadísticas (usando las clases que ya tenías) -->
    <div class="client-stats">
        <div class="stat-card">
            <h3>{{ $files->count() }}</h3>
            <p>Archivos</p>
        </div>
        <div class="stat-card">
            <h3>{{ $user ? 1 : 0 }}</h3>
            <p>Usuarios</p>
        </div>
        <div class="stat-card">
            <h3>0</h3>
            <p>Actividades</p>
        </div>
    </div>

    <!-- Datos del cliente mostrados como tarjetas iguales a data-item -->
    <div class="profile-data">
        @foreach($clientFields as $label => $value)
            <div class="data-item">
                <label>{{ $label }}:</label>
                <span>{{ $value === null || $value === '' ? '-' : $value }}</span>
            </div>
        @endforeach
    </div>

    <!-- Información Fiscal usando data-item (mismo estilo) -->
    <div class="contract-section" style="margin-top:16px;">
        <h2>Información Fiscal</h2>

        @if($infoFiscal)
            <div class="profile-data" style="margin-top:8px;">
                <div class="data-item">
                    <label>Razón social:</label>
                    <span>{{ $infoFiscal->razon_social ?? '-' }}</span>
                </div>
                <div class="data-item">
                    <label>Régimen fiscal:</label>
                    <span>{{ $infoFiscal->regimen_fiscal ?? '-' }}</span>
                </div>
                <div class="data-item">
                    <label>Domicilio fiscal:</label>
                    <span>{{ $infoFiscal->domicilio_fiscal ?? '-' }}</span>
                </div>
                <div class="data-item">
                    <label>Uso CFDI:</label>
                    <span>{{ $infoFiscal->uso_cfdi ?? '-' }}</span>
                </div>
                <div class="data-item">
                    <label>Notas:</label>
                    <span>{{ $infoFiscal->notas ?? '-' }}</span>
                </div>
                <div class="data-item">
                    <label>Contrato (CSF):</label>
                    <span>
                        @if($infoFiscal->csf)
                            <a href="{{ route('clientes.contract.download', $cliente) }}">Descargar</a>
                        @else
                            No disponible
                        @endif
                    </span>
                </div>
            </div>
        @else
            <p>No hay información fiscal registrada.</p>
        @endif
    </div>

    <!-- Plan de facturación también con data-item -->
    <div class="contract-section" style="margin-top:16px;">
        <h2>Plan de Facturación</h2>

        @if($plan)
            <div class="profile-data" style="margin-top:8px;">
                <div class="data-item">
                    <label>Plan:</label>
                    <span>{{ $plan->plan ?? '-' }}</span>
                </div>
                <div class="data-item">
                    <label>Monto:</label>
                    <span>{{ $plan->monto ?? '-' }}</span>
                </div>
                <div class="data-item">
                    <label>Ciclo:</label>
                    <span>{{ $plan->ciclo ?? '-' }}</span>
                </div>
                <div class="data-item">
                    <label>Fecha de corte (día):</label>
                    <span>{{ $plan->fecha_corte ?? '-' }}</span>
                </div>
                <div class="data-item">
                    <label>Método de pago:</label>
                    <span>{{ $plan->metodo_pago ?? '-' }}</span>
                </div>
                <div class="data-item">
                    <label>Facturación automática:</label>
                    <span>
                        @if(is_null($plan->fact_automatica))
                            -
                        @else
                            {{ $plan->fact_automatica ? 'Sí' : 'No' }}
                        @endif
                    </span>
                </div>
            </div>
        @else
            <p>No hay plan registrado para este cliente.</p>
        @endif
    </div>

    <!-- Subida de Archivos (igual que tenías) -->
    <div class="upload-section" id="uploadSection" style="margin-top:18px;">
        <form action="{{ route('clientes.store_file', $cliente) }}" method="POST" enctype="multipart/form-data" id="uploadForm">
            @csrf
            <input type="file" name="uploaded_file" id="fileInput">
            <div id="uploadDisplay">
                <i class="fas fa-upload"></i>
                <p>Selecciona un archivo</p>
            </div>
            <button type="submit" id="submitButton" style="display:none;">Guardar Archivo</button>
        </form>
    </div>

    <!-- Lista de archivos -->
    <div class="file-list" style="margin-top:12px;">
        @if($files->count() > 0)
            @foreach($files as $file)
                <div class="file-item">
                    <i class="fas fa-file"></i>
                    <a href="{{ route('clientes.download_file', [$cliente->id, $file->id]) }}">{{ $file->file_name }}</a>
                </div>
            @endforeach
        @else
            <p>No hay archivos subidos.</p>
        @endif
    </div>

</div>

<!-- Script de upload (idéntico al tuyo) -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const fileInput = document.getElementById('fileInput');
        const uploadSection = document.getElementById('uploadSection');
        const submitButton = document.getElementById('submitButton');
        const uploadDisplay = document.getElementById('uploadDisplay');

        uploadSection.addEventListener('click', function(event) {
            if(event.target !== submitButton) {
                fileInput.click();
            }
        });

        fileInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                uploadDisplay.querySelector('p').textContent = `Archivo seleccionado: ${file.name}`;
                submitButton.style.display = 'block';
            } else {
                uploadDisplay.querySelector('p').textContent = 'Selecciona un archivo';
                submitButton.style.display = 'none';
            }
        });
    });
</script>

@endsection
