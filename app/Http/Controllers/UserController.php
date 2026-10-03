<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Rol;
use App\Models\User;
use App\Services\AuditoriaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * GESTION DE USUARIOS (exclusiva del administrador).
 */
class UserController extends Controller
{
    public function __construct(private readonly AuditoriaService $auditoria) {}

    public function index(): View
    {
        return view('usuarios.index', ['roles' => Rol::cases()]);
    }

    public function create(): View
    {
        return view('usuarios.form', ['user' => null, 'roles' => Rol::cases()]);
    }

    public function edit(string $id): View
    {
        return view('usuarios.form', [
            'user' => User::findOrFail($id),
            'roles' => Rol::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validado = $request->validate($this->reglas());

        $validado['contrasenha'] = Hash::make($validado['contrasenha']);
        $validado['creado_por'] = $request->user()?->id;
        $validado['fecha_creacion'] = now();
        $validado['fecha_actualizacion'] = now();

        $id = DB::table('global.usuarios')->insertGetId($validado, 'id_usuario');

        $this->auditoria->registrar('USUARIO_CREADO', 'usuarios', "Usuario {$validado['usuario']} creado", [
            'id' => $id,
            'rol' => $validado['rol'],
        ]);

        return redirect()->route('usuarios.index')->with('success', 'Usuario creado exitosamente');
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        // `getKey()` y no `getAuthIdentifier()` en todo este controlador.
        // Como la columna de identificacion es `usuario`, `getAuthIdentifier()`
        // devuelve el nombre de login ("admin") en vez del id (1), y las
        // consultas por `id_usuario` comparaban texto contra un integer.
        $validado = $request->validate($this->reglas($user->getKey()));

        if (empty($validado['contrasenha'])) {
            unset($validado['contrasenha']);
        } else {
            $validado['contrasenha'] = Hash::make($validado['contrasenha']);
        }

        $validado['fecha_actualizacion'] = now();
        unset($validado['creado_por']);

        DB::table('global.usuarios')->where('id_usuario', $user->getKey())->update($validado);

        $this->auditoria->registrar('USUARIO_ACTUALIZADO', 'usuarios', "Usuario {$user->usuario} actualizado", [
            'id' => $user->getKey(),
        ]);

        return redirect()->route('usuarios.index')->with('success', 'Usuario actualizado exitosamente');
    }

    public function destroy(Request $request, string $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        // Nadie se queda sin acceso a si mismo.
        if ((int) $id === (int) $request->user()?->id) {
            return redirect()->route('usuarios.index')
                ->with('error', 'No puedes desactivar tu propio usuario.');
        }

        // Desactivar, no borrar: el username esta referenciado por la
        // auditoria y los gastos quedaron emitidos por esa persona.
        DB::table('global.usuarios')
            ->where('id_usuario', $user->getKey())
            ->update(['estado' => 0, 'fecha_actualizacion' => now()]);

        $this->auditoria->registrar('USUARIO_DESACTIVADO', 'usuarios', "Usuario {$user->usuario} desactivado", [
            'id' => $user->getKey(),
        ]);

        return redirect()->route('usuarios.index')->with('success', 'Usuario desactivado exitosamente');
    }

    public function apiList(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => User::where('estado', 1)->orderBy('usuario')->get(),
        ]);
    }

    /** @return array<string, mixed> */
    private function reglas(?int $ignorarId = null): array
    {
        return [
            'usuario' => [
                'required', 'string', 'max:50', 'alpha_dash',
                Rule::unique('usuarios', 'usuario')->ignore($ignorarId, 'id_usuario'),
            ],
            'email' => [
                'required', 'email', 'max:100',
                Rule::unique('usuarios', 'email')->ignore($ignorarId, 'id_usuario'),
            ],
            'contrasenha' => [$ignorarId === null ? 'required' : 'nullable', 'string', 'min:8'],
            'nombres' => ['nullable', 'string', 'max:100'],
            'apellidos' => ['nullable', 'string', 'max:100'],
            'documento_identidad' => [
                'nullable', 'string', 'max:20',
                Rule::unique('usuarios', 'documento_identidad')->ignore($ignorarId, 'id_usuario'),
            ],
            'telefono' => ['nullable', 'string', 'max:20'],
            'rol' => ['required', 'string', Rule::in(Rol::values())],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ];
    }
}