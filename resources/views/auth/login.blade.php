@extends('layouts.master-no-nav')

@section('title', 'VASILIJE - Login')

@section('content')
<div class="login-page">
    <div class="login-container">
        <div class="login-header-area text-center mb-5 animate-slide-up">
            <h1 class="vaso-title animate-pulse">VASILIJE</h1>
            <div class="brutal-badge hover-scale">LOGIN</div>
        </div>

        <div class="bento-card login-card-bento animate-slide-up" style="animation-delay:0.2s;">
            <header class="mb-4 pb-3">
                <p class="font-bold small opacity-50 uppercase mb-1">Acceso Corporativo</p>
                <h2 class="fs-mid font-bold">PORTAL DE GESTIÓN</h2>
            </header>

            @if($errors->any())
                <div class="alert alert-danger font-bold text-center mb-4" style="border:3px solid #000;border-radius:0;font-size:0.9rem;">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    {{ $errors->first('username') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="form-bento" id="loginForm">
                @csrf
                <div class="form-group">
                    <label class="small font-bold">USUARIO</label>
                    <div class="input-with-icon">
                        <input type="text" name="username" id="username" value="{{ old('username') }}" placeholder="INGRESE USUARIO" required autofocus>
                        <i class="fas fa-user-shield"></i>
                    </div>
                </div>

                <div class="form-group mb-5">
                    <label class="small font-bold">CONTRASEÑA</label>
                    <div class="input-with-icon">
                        <input type="password" name="password" id="password" placeholder="••••••••" required>
                        <i class="fas fa-key"></i>
                    </div>
                </div>

                <button type="submit" class="btn-bento btn-bento-primary w-100 py-4 font-bold fs-mid" id="loginSubmit" style="background:var(--warning);border-color:#000;">
                    <i class="fas fa-sign-in-alt me-2"></i>
                    <span>ENTRAR A VASILIJE</span>
                </button>
            </form>

            <footer class="mt-5 pt-3 opacity-25 text-center">
                <p class="small font-bold mb-0">© {{ date('Y') }} VASILIJE</p>
            </footer>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }

html, body {
    height: 100%;
    width: 100%;
    margin: 0;
    padding: 0;
    overflow: hidden;
}

.login-page {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
}

.login-container {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    width: 100%;
    max-width: 500px;
    padding: 2rem;
}

.vaso-title {
    font-size: clamp(3rem, 8vw, 5rem);
    letter-spacing: -2px;
    line-height: 1;
    text-shadow: 4px 4px 0 var(--primary);
    color: #000;
    font-weight: 800;
    text-transform: uppercase;
}

.login-card-bento {
    max-width: 450px;
    width: 100%;
    border: 6px solid #000 !important;
    background: #fff;
    padding: 2.5rem;
    color: #000 !important;
    box-shadow: 15px 15px 0px var(--primary);
}

.login-card-bento h2,
.login-card-bento label,
.login-card-bento p {
    color: #000 !important;
}

@media (max-width: 768px) {
    .login-card-bento {
        padding: 2rem !important;
        border-width: 4px !important;
    }
}

.input-with-icon {
    position: relative;
}

.input-with-icon i {
    position: absolute;
    right: 1.5rem;
    top: 50%;
    transform: translateY(-50%);
    color: rgba(0,0,0,0.4);
    font-size: 1.2rem;
    transition: color 0.3s ease;
}

.input-with-icon input:focus + i {
    color: var(--primary);
}

.form-bento input {
    padding-right: 4rem !important;
    border-radius: 0 !important;
    border: 3px solid #000 !important;
    background: #fff !important;
    color: #000 !important;
    transition: all 0.3s ease;
}

.form-bento input::placeholder {
    color: #999 !important;
    opacity: 1;
}

.form-bento input:focus {
    border-color: var(--primary) !important;
    box-shadow: inset 4px 4px 0 rgba(0,0,0,0.1) !important;
}
</style>
@endpush

@push('scripts')
<script>
document.getElementById('loginForm')?.addEventListener('submit', function(e) {
    const btn = document.getElementById('loginSubmit');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> VERIFICANDO...';
});
</script>
@endpush