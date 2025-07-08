@extends('layouts.app')

@section('content')
<div class="container-fluid" id="app">
    <!-- panel-editor es el componente principal Vue -->
    <panel-editor :panel="{{ $panel }}"></panel-editor>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/app.js'])
@endpush
