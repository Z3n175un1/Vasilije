@extends('layouts.master')

@section('title', 'Inicio - VASILIJE')

@section('content')
<div class="main-container w-full">
    <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 bg-white text-black p-4 rounded-3 shadow-heavy">
        <div class="header-decoration">
            <h1 class="fs-title mb-0 text-black">DOCUMENTOS</h1>
            <p class="font-bold small text-black uppercase">Centro de Documentación Digital</p>
        </div>
    </header>
    <div class="bento-card text-center" style="border: 4px solid #000;">
        <i class="fas fa-folder-open" style="font-size: 4rem; color: var(--primary); margin-bottom: 1.5rem;"></i>
        <h2 class="fs-title mb-3">BIENVENIDO AL SISTEMA</h2>
        <p class="opacity-50 mb-4">Seleccione un módulo en el menú lateral para comenzar</p>
    </div>

    <div class="row g-4 mt-2">
        <div class="col-md-4">
            <a href="{{ route('dashboard.index') }}" class="text-decoration-none">
                <div class="bento-card text-center cursor-pointer" style="border-width:4px;transition:all 0.2s;" onmouseover="this.style.background='#f0f0f0'" onmouseout="this.style.background=''">
                    <i class="fas fa-truck text-primary" style="font-size: 3rem;"></i>
                    <h3 class="fs-mid mt-3">UNIDADES</h3>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('personal.index') }}" class="text-decoration-none">
                <div class="bento-card text-center cursor-pointer" style="border-width:4px;transition:all 0.2s;" onmouseover="this.style.background='#f0f0f0'" onmouseout="this.style.background=''">
                    <i class="fas fa-users text-primary" style="font-size: 3rem;"></i>
                    <h3 class="fs-mid mt-3">PERSONAL</h3>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('gastos.index') }}" class="text-decoration-none">
                <div class="bento-card text-center cursor-pointer" style="border-width:4px;transition:all 0.2s;" onmouseover="this.style.background='#f0f0f0'" onmouseout="this.style.background=''">
                    <i class="fas fa-minus-circle text-danger" style="font-size: 3rem;"></i>
                    <h3 class="fs-mid mt-3">GASTOS</h3>
                </div>
            </a>
        </div>
    </div>
</div>
@endsection