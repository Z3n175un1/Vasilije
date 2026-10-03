<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuditoriaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Autenticacion por sesion.
 *
 * CAMBIOS RESPECTO A LA VERSION ANTERIOR
 * --------------------------------------
 *  1. Limite de intentos con RateLimiter. El controlador anterior no lo
 *     tenia pese a que la tabla usuarios ya tenia `intentos_fallidos` y
 *     `bloqueado_hasta`.
 *  2. Se registran `ultimo_login`, `ultimo_ip` e `intentos_fallidos`, que
 *     nunca se escribian.
 *  3. Mensajes genericos: no se distingue entre usuario inexistente y
 *     contrasena incorrecta.
 *  4. Auditoria de acceso.
 */
class LoginController extends Controller
{
    public function __construct(private readonly AuditoriaService $auditoria) {}

    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('documentos.index');
        }

        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $usuario = $request->user();

        // Por la clave primaria, no por `getAuthIdentifier()`: como la columna de
        // identificacion es `usuario`, ese metodo devuelve "probe_admin" y el
        // UPDATE comparaba texto contra el integer `id_usuario`.
        DB::table('global.usuarios')->where('id_usuario', $usuario->getKey())->update([
            'ultimo_login' => now(),
            'ultimo_ip' => $request->ip(),
            'intentos_fallidos' => 0,
            'bloqueado_hasta' => null,
            'fecha_actualizacion' => now(),
        ]);

        $this->auditoria->registrar('LOGIN_EXITOSO', 'auth', "Acceso de {$usuario->usuario}", [
            'rol' => $usuario->rol,
        ]);

        return redirect()->intended(route('documentos.index'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $usuario = $request->user();

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($usuario) {
            $this->auditoria->registrar('LOGOUT', 'auth', "Sesion cerrada de {$usuario->usuario}", []);
        }

        return redirect()->route('login')->with('success', 'Sesion cerrada exitosamente');
    }
}