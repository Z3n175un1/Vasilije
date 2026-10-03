@extends('layouts.master-no-nav')

@section('title', 'Inicio de Sesión')

@section('content')
<div class="login-page">
    {{-- Background decorativo --}}
    <div class="login-bg-top"></div>
    <div class="login-bg-bottom"></div>

    {{-- Shapes flotantes --}}
    <div class="floating-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
        <div class="shape shape-4"></div>
        <div class="shape shape-5"></div>
        <div class="shape shape-6"></div>
    </div>

    {{-- Grid principal --}}
    <div class="login-grid">
        {{-- PANEL IZQUIERDO: MARCA --}}
        <div class="login-brand">
            <div class="brand-content">
                <h1 class="brand-title">
                    <span class="brand-ds">DS</span>
                    <span class="brand-transporte">TRANSPORTE</span>
                </h1>

                <p class="brand-tagline">
                    Sistema de gestión integral
                </p>

                <div class="brand-features">
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-truck"></i>
                        </div>
                        <div>
                            <div class="feature-title">Flota</div>
                            <div class="feature-desc">Monitoreo de unidades</div>
                        </div>
                    </div>

                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                        <div>
                            <div class="feature-title">Fletes</div>
                            <div class="feature-desc">Gestión de tarifas</div>
                        </div>
                    </div>

                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <div>
                            <div class="feature-title">Reportes</div>
                            <div class="feature-desc">Análisis financiero</div>
                        </div>
                    </div>

                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-cogs"></i>
                        </div>
                        <div>
                            <div class="feature-title">Control</div>
                            <div class="feature-desc">Gastos y almacén</div>
                        </div>
                    </div>
                </div>

                <div class="brand-footer">
                    <p class="copyright">© {{ date('Y') }} DS TRANSPORTE</p>
                    <p class="version">Beta • v1.0</p>
                </div>
            </div>
        </div>

        {{-- PANEL DERECHO: FORMULARIO --}}
        <div class="login-form-wrapper">
            <div class="login-card">
                {{-- HEADER CARD --}}
                <div class="login-card-header">
                    <div class="login-card-icon">
                        <i class="fas fa-lock"></i>
                    </div>
                    <h2 class="login-card-title">ACCESO CORPORATIVO</h2>
                    <p class="login-card-subtitle">Ingrese sus credenciales para continuar</p>
                </div>

                {{-- ERROR MESSAGE --}}
                @if($errors->any())
                    <div class="login-error" role="alert">
                        <i class="fas fa-exclamation-circle"></i>
                        <div>
                            <span class="error-title">Credenciales inválidas</span>
                            <span class="error-message">{{ $errors->first('username') }}</span>
                        </div>
                    </div>
                @endif

                {{-- FORM --}}
                <form method="POST" action="{{ route('login') }}" id="loginForm" class="login-form">
                    @csrf

                    {{-- FIELD: USUARIO --}}
                    <div class="login-field">
                        <label for="username" class="field-label">
                            <i class="fas fa-user"></i>
                            USUARIO
                        </label>
                        <div class="field-input-wrap">
                            <input type="text"
                                   name="username"
                                   id="username"
                                   class="field-input"
                                   value="{{ old('username') }}"
                                   placeholder="usuario@empresa.com"
                                   required
                                   autofocus
                                   autocomplete="username">
                            <div class="input-underline"></div>
                        </div>
                    </div>

                    {{-- FIELD: CONTRASEÑA --}}
                    <div class="login-field">
                        <label for="password" class="field-label">
                            <i class="fas fa-lock"></i>
                            CONTRASEÑA
                        </label>
                        <div class="field-input-wrap">
                            <input type="password"
                                   name="password"
                                   id="password"
                                   class="field-input"
                                   placeholder="••••••••"
                                   required
                                   autocomplete="current-password">
                            <button type="button"
                                    class="password-toggle"
                                    id="togglePass"
                                    tabindex="-1"
                                    aria-label="Mostrar contraseña">
                                <i class="fas fa-eye"></i>
                            </button>
                            <div class="input-underline"></div>
                        </div>
                    </div>

                    {{-- SUBMIT BUTTON --}}
                    <button type="submit" class="login-submit" id="loginSubmit">
                        <span class="btn-content">
                            <span class="btn-text">ENTRAR AL SISTEMA</span>
                            <span class="btn-icon">
                                <i class="fas fa-arrow-right"></i>
                            </span>
                        </span>
                        <div class="btn-loading">
                            <div class="loader-dot"></div>
                            <div class="loader-dot"></div>
                            <div class="loader-dot"></div>
                        </div>
                    </button>
                </form>

                {{-- FOOTER --}}
                <div class="login-footer">
                    <span>© DS TRANSPORTE S.R.L</span>
                    <span>•</span>
                    <span>v1.0</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* ============================================================
   VARIABLES CSS - SISTEMA BENTO
   ============================================================ */
:root {
    --primary: #2f2c79;
    --primary-dark: #1d1a50;
    --primary-light: #403c91;
    --accent: #9ce0db;
    --danger: #dc3545;
    --success: #28a745;
    --gray-dark: #333333;
    --gray-mid: #666666;
    --gray-light: #cccccc;
    --white: #ffffff;
    --black: #000000;
}

/* ============================================================
   RESET
   ============================================================ */
*, *::before, *::after {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

html, body {
    height: 100%;
    width: 100%;
    margin: 0;
    padding: 0;
    overflow: hidden;
}

.login-page {
    position: fixed;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f5f5f5;
    font-family: 'Uncut Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

/* ============================================================
   BACKGROUNDS DECORATIVOS
   ============================================================ */
.login-bg-top {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: 50vh;
    background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 50%, var(--primary-light) 100%);
    z-index: 0;
}

.login-bg-bottom {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    height: 50vh;
    background: #f5f5f5;
    z-index: 0;
}

/* ============================================================
   FLOATING SHAPES - DECORACIÓN
   ============================================================ */
.floating-shapes {
    position: fixed;
    inset: 0;
    z-index: 1;
    pointer-events: none;
    overflow: hidden;
}

.shape {
    position: absolute;
    border: 2px solid rgba(156, 224, 219, 0.15);
}

.shape-1 {
    top: 10%;
    left: 5%;
    width: 140px;
    height: 140px;
    animation: floatShape 8s ease-in-out infinite;
}

.shape-2 {
    top: 60%;
    right: 8%;
    width: 100px;
    height: 100px;
    background: rgba(156, 224, 219, 0.05);
    animation: floatShape 10s ease-in-out infinite reverse;
}

.shape-3 {
    top: 30%;
    right: 15%;
    width: 70px;
    height: 70px;
    border-color: rgba(47, 44, 121, 0.1);
    animation: floatShape 7s ease-in-out infinite 1s;
}

.shape-4 {
    bottom: 20%;
    left: 10%;
    width: 120px;
    height: 120px;
    background: rgba(47, 44, 121, 0.03);
    animation: floatShape 9s ease-in-out infinite 2s;
}

.shape-5 {
    top: 5%;
    left: 40%;
    width: 50px;
    height: 50px;
    border-color: rgba(156, 224, 219, 0.2);
    animation: floatShape 6s ease-in-out infinite 0.5s;
}

.shape-6 {
    bottom: 35%;
    right: 25%;
    width: 60px;
    height: 60px;
    background: rgba(156, 224, 219, 0.04);
    animation: floatShape 11s ease-in-out infinite 1.5s;
}

@keyframes floatShape {
    0%, 100% { transform: translate(0, 0) rotate(0deg); }
    25% { transform: translate(15px, -20px) rotate(5deg); }
    50% { transform: translate(-10px, 10px) rotate(-3deg); }
    75% { transform: translate(20px, 15px) rotate(4deg); }
}

/* ============================================================
   LOGIN GRID - LAYOUT PRINCIPAL
   ============================================================ */
.login-grid {
    position: relative;
    z-index: 2;
    display: grid;
    grid-template-columns: 1fr 1fr;
    max-width: 1200px;
    width: 95%;
    height: min(85vh, 700px);
    background: var(--white);
    border: 6px solid var(--black);
    box-shadow:
        20px 20px 0 var(--black),
        0 0 0 1px var(--black);
    animation: cardEntrance 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    opacity: 0;
}

@keyframes cardEntrance {
    from {
        opacity: 0;
        transform: translateY(40px) scale(0.96);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

/* ============================================================
   PANEL IZQUIERDO: MARCA/BRAND
   ============================================================ */
.login-brand {
    background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
    color: var(--white);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 3rem;
    position: relative;
    overflow: hidden;
    border-right: 6px solid var(--black);
}

.login-brand::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
        repeating-linear-gradient(
            0deg,
            transparent,
            transparent 50px,
            rgba(156, 224, 219, 0.02) 50px,
            rgba(156, 224, 219, 0.02) 51px
        ),
        repeating-linear-gradient(
            90deg,
            transparent,
            transparent 50px,
            rgba(156, 224, 219, 0.02) 50px,
            rgba(156, 224, 219, 0.02) 51px
        );
    pointer-events: none;
    z-index: 0;
}

.brand-content {
    position: relative;
    z-index: 1;
    max-width: 360px;
    animation: slideInLeft 0.5s ease 0.2s both;
}

.brand-title {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 0;
    margin-bottom: 1rem;
}

.brand-ds {
    font-size: clamp(4rem, 10vw, 6.5rem);
    font-weight: 900;
    letter-spacing: -4px;
    line-height: 0.9;
    color: var(--white);
    text-shadow: 4px 4px 0 var(--accent);
    margin-bottom: 0.25rem;
}

.brand-transporte {
    font-size: clamp(1rem, 2.5vw, 1.3rem);
    font-weight: 800;
    letter-spacing: 3px;
    color: var(--accent);
    text-transform: uppercase;
}

.brand-tagline {
    font-size: 0.85rem;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.8);
    margin-bottom: 2.5rem;
    line-height: 1.6;
    max-width: 300px;
    animation: slideInLeft 0.5s ease 0.3s both;
}

/* ============================================================
   FEATURES LIST
   ============================================================ */
.brand-features {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
    margin-bottom: 2rem;
    animation: slideInLeft 0.5s ease 0.4s both;
}

.feature-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    transition: all 0.3s ease;
}

.feature-item:hover {
    transform: translateX(4px);
}

.feature-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(156, 224, 219, 0.2);
    border: 2px solid var(--accent);
    color: var(--accent);
    font-size: 1.1rem;
    flex-shrink: 0;
    transition: all 0.3s ease;
}

.feature-item:hover .feature-icon {
    background: var(--accent);
    color: var(--primary-dark);
    box-shadow: 0 0 0 4px rgba(156, 224, 219, 0.2);
}

.feature-title {
    font-size: 0.85rem;
    font-weight: 800;
    letter-spacing: 0.5px;
    color: var(--white);
    text-transform: uppercase;
    margin-bottom: 0.25rem;
}

.feature-desc {
    font-size: 0.7rem;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.7);
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.brand-footer {
    border-top: 2px solid rgba(156, 224, 219, 0.2);
    padding-top: 1.5rem;
    animation: slideInLeft 0.5s ease 0.5s both;
}

.copyright {
    font-size: 0.7rem;
    font-weight: 700;
    color: rgba(255, 255, 255, 0.8);
    letter-spacing: 0.5px;
    margin-bottom: 0.25rem;
}

.version {
    font-size: 0.65rem;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.6);
    letter-spacing: 0.3px;
}

@keyframes slideInLeft {
    from {
        opacity: 0;
        transform: translateX(-30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

/* ============================================================
   PANEL DERECHO: FORMULARIO
   ============================================================ */
.login-form-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 3rem 2.5rem;
    background: var(--white);
}

.login-card {
    width: 100%;
    max-width: 380px;
}

.login-card-header {
    text-align: center;
    margin-bottom: 2rem;
    animation: fadeInUp 0.5s ease 0.3s both;
}

.login-card-icon {
    width: 70px;
    height: 70px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    color: var(--white);
    font-size: 1.6rem;
    margin: 0 auto 1.25rem;
    border: 4px solid var(--black);
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.login-card:hover .login-card-icon {
    background: linear-gradient(135deg, var(--primary-light), var(--primary));
    box-shadow: 0 0 0 4px var(--accent);
    transform: scale(1.05);
}

.login-card-title {
    font-size: 1.1rem;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    color: var(--black);
    margin-bottom: 0.5rem;
}

.login-card-subtitle {
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--gray-mid);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* ============================================================
   ERROR MESSAGE
   ============================================================ */
.login-error {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    background: #fff5f5;
    border: 3px solid var(--danger);
    border-left: 6px solid var(--danger);
    padding: 1rem 1.15rem;
    margin-bottom: 1.75rem;
    font-size: 0.8rem;
    color: var(--danger);
    animation: slideDown 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

.login-error i {
    font-size: 1.1rem;
    flex-shrink: 0;
    margin-top: 0.15rem;
}

.error-title {
    display: block;
    font-weight: 800;
    margin-bottom: 0.25rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.error-message {
    display: block;
    font-weight: 600;
    opacity: 0.9;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* ============================================================
   FORMULARIO
   ============================================================ */
.login-form {
    animation: fadeInUp 0.5s ease 0.4s both;
}

.login-field {
    margin-bottom: 1.75rem;
}

.field-label {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    font-size: 0.75rem;
    font-weight: 900;
    letter-spacing: 1px;
    color: var(--black);
    margin-bottom: 0.75rem;
    text-transform: uppercase;
}

.field-label i {
    font-size: 0.8rem;
    color: var(--primary);
    width: 16px;
    text-align: center;
}

.field-input-wrap {
    position: relative;
}

.field-input {
    width: 100%;
    padding: 1.1rem 3rem 1.1rem 1.25rem;
    font-size: 1rem;
    font-weight: 700;
    font-family: inherit;
    color: var(--black);
    background: var(--white);
    border: 3px solid var(--black);
    border-radius: 4px;
    outline: none;
    transition: all 0.25s ease;
}

.field-input::placeholder {
    color: #bbb;
    font-weight: 600;
}

.field-input:focus {
    border-color: var(--primary);
    box-shadow:
        0 0 0 4px rgba(47, 44, 121, 0.15),
        inset 0 0 0 1px var(--primary);
}

.input-underline {
    position: absolute;
    bottom: -3px;
    left: 0;
    width: 0%;
    height: 3px;
    background: linear-gradient(90deg, var(--primary), var(--accent));
    transition: width 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    border-radius: 2px;
}

.field-input:focus ~ .input-underline {
    width: 100%;
}

.password-toggle {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--gray-light);
    font-size: 1rem;
    cursor: pointer;
    padding: 0.5rem;
    transition: color 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.password-toggle:hover {
    color: var(--primary);
}

/* ============================================================
   SUBMIT BUTTON
   ============================================================ */
.login-submit {
    position: relative;
    width: 100%;
    padding: 1.2rem;
    margin-top: 1.5rem;
    background: var(--black);
    color: var(--white);
    border: 4px solid var(--black);
    font-family: inherit;
    font-size: 0.9rem;
    font-weight: 900;
    letter-spacing: 1.2px;
    text-transform: uppercase;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    transition: all 0.25s ease;
    overflow: hidden;
    border-radius: 4px;
}

.btn-content {
    position: relative;
    z-index: 2;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
}

.btn-text {
    transition: transform 0.3s ease;
}

.btn-icon {
    transition: transform 0.3s ease;
    font-size: 0.9rem;
}

.login-submit::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(90deg, var(--primary), var(--primary-light));
    transform: scaleX(0);
    transform-origin: right;
    transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    z-index: 1;
    border-radius: 4px;
}

.login-submit:hover:not(:disabled)::before {
    transform: scaleX(1);
    transform-origin: left;
}

.login-submit:hover:not(:disabled) {
    color: var(--white);
    border-color: var(--primary);
    box-shadow:
        8px 8px 0 rgba(47, 44, 121, 0.2),
        0 0 0 1px var(--primary);
}

.login-submit:hover:not(:disabled) .btn-icon {
    transform: translateX(4px);
}

.login-submit:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}

.login-submit:disabled .btn-content {
    display: none;
}

.login-submit:disabled .btn-loading {
    display: flex;
}

.btn-loading {
    display: none;
    align-items: center;
    gap: 6px;
    position: relative;
    z-index: 2;
}

.loader-dot {
    width: 8px;
    height: 8px;
    background: var(--primary);
    border-radius: 50%;
    animation: dotBounce 0.6s ease-in-out infinite alternate;
}

.loader-dot:nth-child(2) {
    animation-delay: 0.2s;
}

.loader-dot:nth-child(3) {
    animation-delay: 0.4s;
}

@keyframes dotBounce {
    from {
        transform: translateY(0);
    }
    to {
        transform: translateY(-8px);
    }
}

/* ============================================================
   FOOTER
   ============================================================ */
.login-footer {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.6rem;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 2px solid #eee;
    font-size: 0.65rem;
    font-weight: 700;
    color: #ccc;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    animation: fadeInUp 0.5s ease 0.6s both;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(15px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* ============================================================
   RESPONSIVE - TABLET
   ============================================================ */
@media (max-width: 900px) {
    .login-grid {
        grid-template-columns: 1fr;
        max-height: 95vh;
        box-shadow:
            12px 12px 0 var(--black),
            0 0 0 1px var(--black);
        border-width: 4px;
    }

    .login-brand {
        display: none;
    }

    .login-form-wrapper {
        padding: 2.5rem 2rem;
    }

    .login-card-icon {
        width: 64px;
        height: 64px;
        font-size: 1.4rem;
        border-width: 3px;
    }

    .floating-shapes {
        display: none;
    }
}

/* ============================================================
   RESPONSIVE - MOBILE
   ============================================================ */
@media (max-width: 600px) {
    .login-grid {
        height: auto;
        max-height: 98vh;
        box-shadow:
            8px 8px 0 var(--black),
            0 0 0 1px var(--black);
        border-width: 3px;
    }

    .login-form-wrapper {
        padding: 2rem 1.5rem;
    }

    .login-card-header {
        margin-bottom: 1.5rem;
    }

    .login-card-icon {
        width: 56px;
        height: 56px;
        font-size: 1.2rem;
    }

    .login-card-title {
        font-size: 1rem;
        letter-spacing: 0.8px;
    }

    .login-card-subtitle {
        font-size: 0.7rem;
    }

    .login-field {
        margin-bottom: 1.5rem;
    }

    .field-input {
        padding: 1rem 2.75rem 1rem 1rem;
        font-size: 0.95rem;
    }

    .login-submit {
        padding: 1rem;
        font-size: 0.85rem;
        letter-spacing: 1px;
    }

    .login-footer {
        font-size: 0.6rem;
        margin-top: 1.5rem;
    }
}

/* ============================================================
   SMALL HEIGHT DEVICES
   ============================================================ */
@media (max-height: 650px) {
    .login-grid {
        height: auto;
    }

    .login-form-wrapper {
        padding: 1.5rem;
    }

    .login-card-header {
        margin-bottom: 1rem;
    }

    .login-card-icon {
        width: 52px;
        height: 52px;
        margin: 0 auto 0.75rem;
        font-size: 1.1rem;
    }

    .login-field {
        margin-bottom: 1.1rem;
    }

    .field-input {
        padding: 0.85rem 2.5rem 0.85rem 1rem;
        font-size: 0.9rem;
    }

    .login-submit {
        padding: 0.95rem;
        font-size: 0.8rem;
    }
}
</style>
@endpush

@push('scripts')
<script>
(function() {
    'use strict';

    const form = document.getElementById('loginForm');
    const submitBtn = document.getElementById('loginSubmit');
    const togglePass = document.getElementById('togglePass');
    const passInput = document.getElementById('password');
    const usernameInput = document.getElementById('username');

    // Toggle password visibility
    if (togglePass && passInput) {
        togglePass.addEventListener('click', function(e) {
            e.preventDefault();
            const isPassword = passInput.type === 'password';
            passInput.type = isPassword ? 'text' : 'password';
            togglePass.innerHTML = isPassword
                ? '<i class="fas fa-eye-slash"></i>'
                : '<i class="fas fa-eye"></i>';
        });
    }

    // Handle form submission
    if (form) {
        form.addEventListener('submit', function(e) {
            submitBtn.disabled = true;

            // Trigger loading state
            setTimeout(() => {
                // Form submits naturally
            }, 100);
        });
    }

    // Auto-focus username field
    if (usernameInput && !usernameInput.value) {
        usernameInput.focus();
    }
})();
</script>
@endpush
