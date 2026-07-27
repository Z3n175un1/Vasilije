@extends('layouts.master-no-nav')

@section('title', 'VASILIJE | formulario de login')

@section('content')
<div class="login-page">
    <div class="login-bg-top"></div>
    <div class="login-bg-bottom"></div>

    <div class="floating-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
        <div class="shape shape-4"></div>
        <div class="shape shape-5"></div>
        <div class="shape shape-6"></div>
    </div>

    <div class="login-grid">
        <div class="login-brand">
            <div class="brand-content">
                <div class="brand-badge">SISTEMA DE GESTIÓN</div>
                <h1 class="brand-title">VASILIJE</h1>
                <p class="brand-desc">Control de Flota, Gastos y Facturación para empresas de transporte bolivianas.</p>
                <div class="brand-features">
                    <div class="feature-item">
                        <i class="fas fa-truck"></i>
                        <span>Monitoreo de Unidades</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-file-invoice-dollar"></i>
                        <span>Gestión de Fletes</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-chart-bar"></i>
                        <span>Reportes Financieros</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-warehouse"></i>
                        <span>Control de Almacén</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="login-form-wrapper">
            <div class="login-card">
                <div class="login-card-header">
                    <div class="login-card-icon">
                        <i class="fas fa-lock"></i>
                    </div>
                    <h2 class="login-card-title">Acceso Corporativo</h2>
                    <p class="login-card-subtitle">Ingrese sus credenciales para continuar</p>
                </div>

                @if($errors->any())
                    <div class="login-error">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>{{ $errors->first('username') }}</span>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" id="loginForm" class="login-form">
                    @csrf
                    <div class="login-field">
                        <label for="username">
                            <i class="fas fa-user"></i>
                            USUARIO
                        </label>
                        <div class="login-input-wrap">
                            <input type="text" name="username" id="username"
                                   value="{{ old('username') }}"
                                   placeholder="Ingrese su usuario"
                                   required autofocus autocomplete="username">
                            <div class="input-border"></div>
                        </div>
                    </div>

                    <div class="login-field">
                        <label for="password">
                            <i class="fas fa-key"></i>
                            CONTRASEÑA
                        </label>
                        <div class="login-input-wrap">
                            <input type="password" name="password" id="password"
                                   placeholder="••••••••"
                                   required autocomplete="current-password">
                            <button type="button" class="password-toggle" id="togglePass" tabindex="-1" aria-label="Mostrar contraseña">
                                <i class="fas fa-eye"></i>
                            </button>
                            <div class="input-border"></div>
                        </div>
                    </div>

                    <button type="submit" class="login-submit" id="loginSubmit">
                        <span class="btn-text">ENTRAR AL SISTEMA</span>
                        <span class="btn-icon"><i class="fas fa-arrow-right"></i></span>
                        <div class="btn-loading">
                            <div class="loader-dot"></div>
                            <div class="loader-dot"></div>
                            <div class="loader-dot"></div>
                        </div>
                    </button>
                </form>

                <div class="login-footer">
                    <span>© {{ date('Y') }} VASILIJE</span>
                    <span class="footer-dot">•</span>
                    <span>v1.0</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
*, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }

html, body {
    height: 100%; width: 100%;
    margin: 0; padding: 0;
    overflow: hidden;
}

.login-page {
    position: fixed; inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f5f5f5;
    font-family: 'Uncut Sans', sans-serif;
}

.login-bg-top {
    position: fixed; top: 0; left: 0; right: 0;
    height: 50vh;
    background: linear-gradient(135deg, #000 0%, #1a1a1a 100%);
    z-index: 0;
}

.login-bg-bottom {
    position: fixed; bottom: 0; left: 0; right: 0;
    height: 50vh;
    background: #f5f5f5;
    z-index: 0;
}

/* Floating shapes */
.floating-shapes {
    position: fixed; inset: 0;
    z-index: 1;
    pointer-events: none;
    overflow: hidden;
}

.shape {
    position: absolute;
    border: 3px solid rgba(255, 204, 0, 0.12);
    border-radius: 0;
}

.shape-1 {
    top: 10%; left: 5%;
    width: 120px; height: 120px;
    animation: floatShape 8s ease-in-out infinite;
}

.shape-2 {
    top: 60%; right: 8%;
    width: 80px; height: 80px;
    background: rgba(255, 204, 0, 0.06);
    animation: floatShape 10s ease-in-out infinite reverse;
}

.shape-3 {
    top: 30%; right: 15%;
    width: 60px; height: 60px;
    border-color: rgba(0,0,0,0.08);
    animation: floatShape 7s ease-in-out infinite 1s;
}

.shape-4 {
    bottom: 20%; left: 10%;
    width: 100px; height: 100px;
    background: rgba(0,0,0,0.03);
    animation: floatShape 9s ease-in-out infinite 2s;
}

.shape-5 {
    top: 5%; left: 40%;
    width: 40px; height: 40px;
    border-color: rgba(255, 204, 0, 0.2);
    animation: floatShape 6s ease-in-out infinite 0.5s;
}

.shape-6 {
    bottom: 35%; right: 25%;
    width: 50px; height: 50px;
    background: rgba(255, 204, 0, 0.04);
    animation: floatShape 11s ease-in-out infinite 1.5s;
}

@keyframes floatShape {
    0%, 100% { transform: translate(0, 0) rotate(0deg); }
    25% { transform: translate(15px, -20px) rotate(5deg); }
    50% { transform: translate(-10px, 10px) rotate(-3deg); }
    75% { transform: translate(20px, 15px) rotate(4deg); }
}

/* Main grid */
.login-grid {
    position: relative;
    z-index: 2;
    display: grid;
    grid-template-columns: 1fr 1fr;
    max-width: 1100px;
    width: 95%;
    height: min(85vh, 680px);
    background: #fff;
    border: 6px solid #000;
    box-shadow: 20px 20px 0 #000;
    animation: cardEntrance 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    opacity: 0;
}

@keyframes cardEntrance {
    from { opacity: 0; transform: translateY(40px) scale(0.97); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

/* Brand panel */
.login-brand {
    background: #000;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 3rem;
    position: relative;
    overflow: hidden;
}

.login-brand::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
        repeating-linear-gradient(0deg, transparent, transparent 40px, rgba(255,204,0,0.03) 40px, rgba(255,204,0,0.03) 41px),
        repeating-linear-gradient(90deg, transparent, transparent 40px, rgba(255,204,0,0.03) 40px, rgba(255,204,0,0.03) 41px);
    pointer-events: none;
}

.brand-content {
    position: relative;
    z-index: 1;
    max-width: 380px;
}

.brand-badge {
    display: inline-block;
    background: #ffcc00;
    color: #000;
    font-size: 0.65rem;
    font-weight: 800;
    letter-spacing: 2px;
    padding: 0.5rem 1rem;
    margin-bottom: 1.5rem;
    animation: slideInLeft 0.5s ease 0.2s both;
}

.brand-title {
    font-size: clamp(3rem, 5vw, 4.5rem);
    font-weight: 900;
    letter-spacing: -3px;
    line-height: 1;
    color: #fff;
    text-transform: uppercase;
    margin-bottom: 1rem;
    animation: slideInLeft 0.5s ease 0.3s both;
    text-shadow: 4px 4px 0 #ffcc00;
}

.brand-desc {
    color: rgba(255,255,255,0.6);
    font-size: 0.85rem;
    font-weight: 600;
    line-height: 1.6;
    margin-bottom: 2.5rem;
    animation: slideInLeft 0.5s ease 0.4s both;
}

.brand-features {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    animation: slideInLeft 0.5s ease 0.5s both;
}

.feature-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    color: rgba(255,255,255,0.7);
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    transition: all 0.3s ease;
}

.feature-item i {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 204, 0, 0.15);
    color: #ffcc00;
    font-size: 0.9rem;
    flex-shrink: 0;
    transition: all 0.3s ease;
}

.feature-item:hover {
    color: #fff;
}

.feature-item:hover i {
    background: #ffcc00;
    color: #000;
}

@keyframes slideInLeft {
    from { opacity: 0; transform: translateX(-30px); }
    to { opacity: 1; transform: translateX(0); }
}

/* Form panel */
.login-form-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2.5rem;
    background: #fff;
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
    width: 64px; height: 64px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #000;
    color: #ffcc00;
    font-size: 1.4rem;
    margin: 0 auto 1.2rem;
    border: 3px solid #000;
    transition: all 0.3s ease;
}

.login-card:hover .login-card-icon {
    background: #ffcc00;
    color: #000;
}

.login-card-title {
    font-size: 1.1rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #000;
    margin-bottom: 0.4rem;
}

.login-card-subtitle {
    font-size: 0.75rem;
    font-weight: 600;
    color: #999;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Error */
.login-error {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    background: #fff5f5;
    border: 3px solid #dc3545;
    padding: 0.85rem 1rem;
    margin-bottom: 1.5rem;
    font-size: 0.8rem;
    font-weight: 700;
    color: #dc3545;
    animation: shake 0.4s ease;
}

.login-error i { font-size: 1rem; flex-shrink: 0; }

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    20% { transform: translateX(-8px); }
    40% { transform: translateX(8px); }
    60% { transform: translateX(-5px); }
    80% { transform: translateX(5px); }
}

/* Form fields */
.login-form {
    animation: fadeInUp 0.5s ease 0.4s both;
}

.login-field {
    margin-bottom: 1.5rem;
}

.login-field label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.7rem;
    font-weight: 800;
    letter-spacing: 1px;
    color: #000;
    margin-bottom: 0.6rem;
    text-transform: uppercase;
}

.login-field label i {
    font-size: 0.75rem;
    color: #ffcc00;
    width: 16px;
    text-align: center;
}

.login-input-wrap {
    position: relative;
}

.login-input-wrap input {
    width: 100%;
    padding: 1rem 3rem 1rem 1.25rem;
    font-size: 0.95rem;
    font-weight: 700;
    font-family: inherit;
    color: #000;
    background: #fff;
    border: 3px solid #000;
    border-radius: 0;
    outline: none;
    transition: all 0.25s ease;
}

.login-input-wrap input::placeholder {
    color: #bbb;
    font-weight: 600;
}

.login-input-wrap input:focus {
    border-color: #ffcc00;
    box-shadow: inset 0 0 0 1px #ffcc00;
}

.input-border {
    position: absolute;
    bottom: 0; left: 50%;
    width: 0;
    height: 3px;
    background: #ffcc00;
    transition: all 0.3s ease;
    transform: translateX(-50%);
}

.login-input-wrap input:focus ~ .input-border {
    width: 100%;
}

.password-toggle {
    position: absolute;
    right: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #bbb;
    font-size: 1rem;
    cursor: pointer;
    padding: 0.25rem;
    transition: color 0.2s ease;
}

.password-toggle:hover { color: #000; }

/* Submit button */
.login-submit {
    position: relative;
    width: 100%;
    padding: 1.25rem;
    margin-top: 0.5rem;
    background: #000;
    color: #ffcc00;
    border: 3px solid #000;
    font-family: inherit;
    font-size: 0.85rem;
    font-weight: 800;
    letter-spacing: 1px;
    text-transform: uppercase;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    transition: all 0.25s ease;
    overflow: hidden;
}

.login-submit .btn-text { position: relative; z-index: 2; transition: transform 0.3s ease; }
.login-submit .btn-icon { position: relative; z-index: 2; transition: transform 0.3s ease; }

.login-submit::before {
    content: '';
    position: absolute;
    inset: 0;
    background: #ffcc00;
    transform: scaleX(0);
    transform-origin: right;
    transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    z-index: 1;
}

.login-submit:hover:not(:disabled)::before {
    transform: scaleX(1);
    transform-origin: left;
}

.login-submit:hover:not(:disabled) {
    color: #000;
    border-color: #000;
}

.login-submit:hover:not(:disabled) .btn-icon {
    transform: translateX(4px);
}

.login-submit:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.login-submit:disabled .btn-text,
.login-submit:disabled .btn-icon {
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
    background: #ffcc00;
    border-radius: 50%;
    animation: dotBounce 0.6s ease-in-out infinite alternate;
}

.loader-dot:nth-child(2) { animation-delay: 0.2s; }
.loader-dot:nth-child(3) { animation-delay: 0.4s; }

@keyframes dotBounce {
    from { transform: translateY(0); }
    to { transform: translateY(-8px); }
}

/* Footer */
.login-footer {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    margin-top: 2rem;
    font-size: 0.65rem;
    font-weight: 700;
    color: #ccc;
    animation: fadeInUp 0.5s ease 0.6s both;
}

.footer-dot { color: #ddd; }

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(15px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Responsive */
@media (max-width: 800px) {
    .login-grid {
        grid-template-columns: 1fr;
        height: auto;
        max-height: 95vh;
        box-shadow: 10px 10px 0 #000;
        border-width: 4px;
    }

    .login-brand {
        display: none;
    }

    .login-form-wrapper {
        padding: 2rem 1.5rem;
    }

    .login-card-icon {
        width: 56px; height: 56px;
        font-size: 1.2rem;
    }

    .floating-shapes { display: none; }
}

@media (max-height: 650px) {
    .login-grid {
        height: auto;
        max-height: 98vh;
    }

    .login-form-wrapper { padding: 1.5rem; }
    .login-card-header { margin-bottom: 1rem; }
    .login-field { margin-bottom: 1rem; }
    .login-field input { padding: 0.75rem 2.5rem 0.75rem 1rem; font-size: 0.85rem; }
    .login-submit { padding: 1rem; }
    .brand-features { gap: 0.5rem; }
    .feature-item { font-size: 0.7rem; }
    .feature-item i { width: 28px; height: 28px; font-size: 0.75rem; }
}
</style>
@endpush

@push('scripts')
<script>
(function() {
    const form = document.getElementById('loginForm');
    const submitBtn = document.getElementById('loginSubmit');
    const togglePass = document.getElementById('togglePass');
    const passInput = document.getElementById('password');

    if (togglePass && passInput) {
        togglePass.addEventListener('click', function() {
            const isPassword = passInput.type === 'password';
            passInput.type = isPassword ? 'text' : 'password';
            this.innerHTML = isPassword ? '<i class="fas fa-eye-slash"></i>' : '<i class="fas fa-eye"></i>';
        });
    }

    if (form) {
        form.addEventListener('submit', function(e) {
            submitBtn.disabled = true;
            submitBtn.querySelector('.btn-text').textContent = 'VERIFICANDO...';
            submitBtn.querySelector('.btn-icon').innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        });
    }
})();
</script>
@endpush
