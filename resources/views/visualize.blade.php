@extends('layouts.complete')

@section('title', 'Energy Dashboard')

@push('head')
  <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@vite(['resources/css/pages/panels.css','resources/js/pages/panels.js'])

@section('content')
    @include('dashboard.partials.panel')
@endsection
