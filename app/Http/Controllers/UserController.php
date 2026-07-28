<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        return view('usuarios.index');
    }

    public function create()
    {
        return view('usuarios.form', ['user' => null]);
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        return view('usuarios.form', ['user' => $user]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'usuario' => 'required|string|max:50|unique:usuarios,usuario',
            'email' => 'required|email|max:100|unique:usuarios,email',
            'contrasenha' => 'required|string|min:6',
            'nombres' => 'nullable|string|max:100',
            'apellidos' => 'nullable|string|max:100',
            'documento_identidad' => 'nullable|string|max:20|unique:usuarios,documento_identidad',
            'telefono' => 'nullable|string|max:20',
            'rol' => 'required|string|in:admin,supervisor,operador,lectura',
            'observaciones' => 'nullable|string',
        ]);

        $validated['contrasenha'] = Hash::make($validated['contrasenha']);

        User::create($validated);

        return redirect()->route('usuarios.index')->with('success', 'Usuario creado exitosamente');
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'usuario' => 'required|string|max:50|unique:usuarios,usuario,' . $id . ',id_usuario',
            'email' => 'required|email|max:100|unique:usuarios,email,' . $id . ',id_usuario',
            'contrasenha' => 'nullable|string|min:6',
            'nombres' => 'nullable|string|max:100',
            'apellidos' => 'nullable|string|max:100',
            'documento_identidad' => 'nullable|string|max:20|unique:usuarios,documento_identidad,' . $id . ',id_usuario',
            'telefono' => 'nullable|string|max:20',
            'rol' => 'required|string|in:admin,supervisor,operador,lectura',
            'observaciones' => 'nullable|string',
        ]);

        if (empty($validated['contrasenha'])) {
            unset($validated['contrasenha']);
        } else {
            $validated['contrasenha'] = Hash::make($validated['contrasenha']);
        }

        $user->update($validated);

        return redirect()->route('usuarios.index')->with('success', 'Usuario actualizado exitosamente');
    }

    public function destroy($id)
    {
        if ((int) $id === (int) auth()->id()) {
            return redirect()->route('usuarios.index')->with('error', 'No puedes eliminar tu propio usuario');
        }

        $user = User::findOrFail($id);
        $user->update(['estado' => 0]);

        return redirect()->route('usuarios.index')->with('success', 'Usuario desactivado exitosamente');
    }

    public function apiList()
    {
        $users = User::where('estado', 1)->orderBy('usuario')->get();
        return response()->json(['success' => true, 'data' => $users]);
    }
}
