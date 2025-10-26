@extends('layouts.complete')

@section('title', 'Cliente - ' . $cliente->nombre)

@section('content')

@php
    // Si $cliente->users es null, se asigna una colección vacía para evitar errores
    $relatedUsers = $cliente->users ?? collect();
    $primaryUser = $relatedUsers->first();

    // De igual forma para los archivos
    $files = $cliente->files ?? collect();
    $infoFiscal = $cliente->infoFiscal;
    $canManageContract = auth()->user()?->isSuperAdmin();
@endphp

<style>
    /* Animaciones */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Global */
    body {
        margin: 0;
        padding: 0;
        background: #eef2f7;
        font-family: 'Roboto', sans-serif;
        color: #333;
        -webkit-font-smoothing: antialiased;
    }

    .container {
        max-width: 1100px;
        margin: 50px auto;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        padding: 40px;
        animation: fadeIn 0.8s ease-out;
    }

    /* Encabezado del Perfil */
    .profile-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid #ddd;
        padding-bottom: 20px;
        margin-bottom: 30px;
    }
    .profile-image {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        overflow: hidden;
        background: #dce1e7;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 48px;
        color: #555;
        flex-shrink: 0;
        border: 3px solid #f1f3f5;
    }
    .profile-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .profile-info {
        flex: 1;
        margin-left: 30px;
    }
    .profile-info h2 {
        margin: 0;
        font-size: 32px;
        font-weight: 700;
        color: #222;
    }
    .profile-info p {
        font-size: 18px;
        margin: 8px 0 0;
        color: #666;
    }

    /* Sección de Estadísticas */
    .client-stats {
        display: flex;
        justify-content: space-around;
        margin-bottom: 40px;
    }
    .client-stats .stat-card {
        background: #f9f9f9;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
        flex: 1;
        margin: 0 10px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .client-stats .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .client-stats .stat-card h3 {
        margin: 0;
        font-size: 24px;
        color: #333;
    }
    .client-stats .stat-card p {
        margin: 10px 0 0;
        font-size: 18px;
        color: #666;
    }

    /* Datos del Cliente */
    .profile-data {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }
    .profile-data .data-item {
        background: #f9f9f9;
        padding: 15px 20px;
        border-radius: 8px;
        transition: transform 0.3s ease;
        border: 1px solid #eaeaea;
    }
    .profile-data .data-item:hover {
        transform: translateY(-5px);
    }
    .profile-data .data-item label {
        font-weight: 600;
        display: block;
        margin-bottom: 8px;
        color: #444;
        font-size: 14px;
    }
    .profile-data .data-item span {
        font-size: 16px;
        color: #555;
    }

    /* Sección de Subida de Archivos */
    .upload-section {
        margin-top: 40px;
        padding: 30px;
        border: 2px dashed #bbb;
        border-radius: 10px;
        text-align: center;
        position: relative;
        transition: background 0.3s ease, border-color 0.3s ease;
        cursor: pointer;
    }
    .upload-section:hover {
        background: #f1f1f1;
        border-color: #999;
    }
    .upload-section input[type="file"] {
        display: none;
    }
    .upload-section i {
        font-size: 50px;
        color: #777;
    }
    .upload-section p {
        margin-top: 20px;
        font-size: 18px;
        color: #666;
    }
    .upload-section button {
        margin-top: 20px;
        padding: 12px 25px;
        border: none;
        background: #333;
        color: #fff;
        border-radius: 5px;
        font-size: 16px;
        transition: background 0.3s ease;
        cursor: pointer;
    }
    .upload-section button:hover {
        background: #555;
    }

    .contract-section {
        margin-top: 40px;
        padding: 30px;
        background: #f9f9f9;
        border-radius: 12px;
        border: 1px solid #eee;
    }

    .contract-section h2 {
        margin-bottom: 16px;
        font-size: 24px;
        color: #333;
    }

    .contract-actions {
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    .contract-form {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .contract-label {
        font-weight: 600;
        color: #555;
    }

    /* Sección de Archivos Subidos */
    .file-list {
        margin-top: 40px;
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
    }
    .file-item {
        background: #fafafa;
        border: 1px solid #ddd;
        border-radius: 10px;
        padding: 20px;
        width: 150px;
        text-align: center;
        transition: box-shadow 0.3s ease, transform 0.3s ease;
    }
    .file-item:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        transform: translateY(-3px);
    }
    .file-item i {
        font-size: 40px;
        color: #777;
        margin-bottom: 10px;
    }
    .file-item a {
        display: block;
        color: #0069d9;
        text-decoration: none;
        font-weight: 500;
        font-size: 14px;
        word-wrap: break-word;
    }

    /* Sección de Usuarios Relacionados */
    .related-users {
        margin-top: 50px;
    }
    .related-users h2 {
        font-size: 26px;
        font-weight: 600;
        margin-bottom: 20px;
        color: #222;
    }
    .related-users table {
        width: 100%;
        border-collapse: collapse;
    }
    .related-users th,
    .related-users td {
        padding: 15px 10px;
        border-bottom: 1px solid #eee;
        text-align: left;
        font-size: 15px;
    }
    .related-users th {
        background: #f5f5f5;
        font-weight: 600;
        color: #555;
    }
    .related-users tbody tr:hover {
        background: #f9f9f9;
    }
    .related-users img {
        border-radius: 50%;
        width: 55px;
        height: 55px;
        object-fit: cover;
        border: 2px solid #e0e0e0;
    }
    .related-users i {
        font-size: 28px;
        color: #777;
    }

    /* Sección de Actividad (Timeline) */
    .activity-timeline {
        margin-top: 50px;
    }
    .activity-timeline h2 {
        font-size: 26px;
        font-weight: 600;
        margin-bottom: 20px;
        color: #222;
    }
    .timeline {
        position: relative;
        padding-left: 40px;
    }
    .timeline::before {
        content: '';
        position: absolute;
        left: 20px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #ddd;
    }
    .timeline-item {
        position: relative;
        margin-bottom: 20px;
    }
    .timeline-item:last-child {
        margin-bottom: 0;
    }
    .timeline-item::before {
        content: '';
        position: absolute;
        left: -10px;
        background: #fff;
        border: 3px solid #333;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        top: 0;
    }
    .timeline-item h4 {
        margin: 0 0 5px;
        font-size: 18px;
        color: #333;
    }
    .timeline-item p {
        margin: 0;
        font-size: 14px;
        color: #666;
    }

    /* Botón "Volver arriba" */
    #backToTop {
        position: fixed;
        bottom: 30px;
        right: 30px;
        background: #333;
        color: #fff;
        border: none;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        font-size: 22px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        opacity: 0.7;
        transition: opacity 0.3s ease, transform 0.3s ease;
    }
    #backToTop:hover {
        opacity: 1;
        transform: translateY(-5px);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .profile-header {
            flex-direction: column;
            text-align: center;
        }
        .profile-info {
            margin-left: 0;
            margin-top: 20px;
        }
        .client-stats {
            flex-direction: column;
        }
        .client-stats .stat-card {
            margin: 10px 0;
        }
    }

    .clientes-header {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-bottom: 10px;
        margin-top: 15px;
    }

    .clientes-titulo {
        font-size: 50px;
        font-weight: bold;
        color: #91462E;
        margin: 0;
    }
</style>

<div class="clientes-header">
    <h1 class="clientes-titulo">Client information</h1>
</div>

<div class="container">
    
    <!-- Perfil -->
    <div class="profile-header">
        <div class="profile-image">
            @if($primaryUser && $primaryUser->profile_image)
                <img src="{{ asset($primaryUser->profile_image) }}" alt="{{ $primaryUser->name }}">
            @else
                <span>{{ strtoupper(substr($cliente->nombre, 0, 1)) }}</span>
            @endif
        </div>
        <div class="profile-info">
            <h2>{{ $cliente->nombre }}</h2>
            <p>{{ $cliente->razon_social }}</p>
        </div>
    </div>

    <!-- Estadísticas del Cliente -->
    <div class="client-stats">
        <div class="stat-card">
            <h3>{{ $files->count() }}</h3>
            <p>Archivos</p>
        </div>
        <div class="stat-card">
            <h3>{{ $relatedUsers->count() }}</h3>
            <p>Usuarios</p>
        </div>
        <div class="stat-card">
            <h3>123</h3>
            <p>Actividades</p>
        </div>
    </div>

    <!-- Datos del Cliente -->
    <div class="profile-data">
        <div class="data-item">
            <label>Nombre:</label>
            <span>{{ $cliente->nombre }}</span>
        </div>
        <div class="data-item">
            <label>Razón Social:</label>
            <span>{{ $cliente->razon_social }}</span>
        </div>
        <div class="data-item">
            <label>Correo:</label>
            <span>{{ $cliente->email }}</span>
        </div>
        <div class="data-item">
            <label>Teléfono:</label>
            <span>{{ $cliente->telefono }}</span>
        </div>
        <div class="data-item">
            <label>Calle:</label>
            <span>{{ $cliente->calle }}</span>
        </div>
        <div class="data-item">
            <label>Número:</label>
            <span>{{ $cliente->numero }}</span>
        </div>
        <div class="data-item">
            <label>Colonia:</label>
            <span>{{ $cliente->colonia }}</span>
        </div>
        <div class="data-item">
            <label>Código Postal:</label>
            <span>{{ $cliente->codigo_postal }}</span>
        </div>
        <div class="data-item">
            <label>Ciudad:</label>
            <span>{{ $cliente->ciudad }}</span>
        </div>
        <div class="data-item">
            <label>Estado:</label>
            <span>{{ $cliente->estado }}</span>
        </div>
        <div class="data-item">
            <label>País:</label>
            <span>{{ $cliente->pais }}</span>
        </div>
        <div class="data-item">
            <label>Cambio de Dólar:</label>
            <span>{{ $cliente->cambio_dolar }}</span>
        </div>
        <div class="data-item">
            <label>Creado el:</label>
            @if($primaryUser)
                <span>{{ $primaryUser->created_at->format('d/m/Y') }}</span>
            @else
                <span>Sin fecha</span>
            @endif
        </div>
    </div>

    <!-- Subida de Archivos -->
    <div class="upload-section" id="uploadSection">
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

    <div class="contract-section">
        <h2>Contrato</h2>
        @if($infoFiscal && $infoFiscal->csf)
            <div class="contract-actions">
                <a class="btn btn-primary" href="{{ route('clientes.contract.download', $cliente) }}">Descargar contrato</a>
                @if($canManageContract)
                    <form class="contract-form" method="POST" action="{{ route('clientes.contract.update', $cliente) }}" enctype="multipart/form-data">
                        @csrf
                        <label class="contract-label">Actualizar contrato (PDF, máx 10 MB)</label>
                        <input class="form-control" type="file" name="contrato" accept="application/pdf" required>
                        <button type="submit" class="btn btn-secondary">Reemplazar</button>
                    </form>
                    <form class="contract-form" method="POST" action="{{ route('clientes.contract.delete', $cliente) }}" onsubmit="return confirm('¿Eliminar contrato actual?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Eliminar</button>
                    </form>
                @endif
            </div>
        @elseif($canManageContract)
            <form class="contract-form" method="POST" action="{{ route('clientes.contract.update', $cliente) }}" enctype="multipart/form-data">
                @csrf
                <label class="contract-label">Cargar contrato (PDF, máx 10 MB)</label>
                <input class="form-control" type="file" name="contrato" accept="application/pdf" required>
                <button type="submit" class="btn btn-primary">Subir contrato</button>
            </form>
        @else
            <p>No hay contrato disponible.</p>
        @endif
    </div>

    <!-- Archivos Subidos -->
    <div class="file-list">
        @if($files->count() > 0)
            @foreach($files as $file)
                <div class="file-item">
                    <i class="fas fa-file"></i>
                    <a href="{{ route('clientes.download_file', [$cliente->id, $file->id]) }}">{{ $file->file_name }}</a>
                </div>
            @endforeach
        @else
        @endif
    </div>

   <!--   <div class="related-users">
        <h2>Usuarios Relacionados</h2>
        @if($relatedUsers->count() > 0)
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Foto de Perfil</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($relatedUsers as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->role }}</td>
                            <td>
                                @if($user->profile_image)
                                    <img src="{{ asset($user->profile_image) }}" alt="{{ $user->name }}">
                                @else
                                    <i class="fas fa-user"></i>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p>No hay usuarios relacionados con este cliente.</p>
        @endif
    </div>
 -->
 

<!-- Botón "Volver arriba" -->
<button id="backToTop" title="Volver arriba"><i class="fas fa-chevron-up"></i></button>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const fileInput = document.getElementById('fileInput');
        const uploadSection = document.getElementById('uploadSection');
        const submitButton = document.getElementById('submitButton');
        const uploadDisplay = document.getElementById('uploadDisplay');
        const backToTop = document.getElementById('backToTop');

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

        backToTop.addEventListener('click', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });
</script>

@endsection
