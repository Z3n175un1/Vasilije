@extends('layouts.master')

@section('title', 'Configuración - VASILIJE')

@section('content')
<div class="main-container w-full">
    <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 bg-white text-black p-4 rounded-3 shadow-heavy">
        <div class="header-decoration">
            <h1 class="fs-title mb-0 text-black">CONFIGURACIÓN</h1>
            <p class="font-bold small text-black uppercase">Ajustes del Sistema</p>
        </div>
    </header>
    <div class="bento-card text-center" style="border: 4px solid #000;">
        <i class="fas fa-cogs" style="font-size: 4rem; color: var(--primary); margin-bottom: 1.5rem;"></i>
        <h2 class="fs-title mb-3">CONFIGURACIÓN</h2>
        <p class="opacity-50 mb-4">Módulo de configuración en implementación</p>
    </div>
</div>
@endsection