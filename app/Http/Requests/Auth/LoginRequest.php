<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * El login original no tenia limite de intentos, pese a que la tabla
 * `usuarios` ya tenia las columnas `intentos_fallidos` y `bloqueado_hasta`.
 * Esto lo convierte en el endpoint ideal para fuerza bruta.
 */
class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'username.required' => 'El usuario es obligatorio.',
            'password.required' => 'La contrasena es obligatoria.',
        ];
    }

    /**
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

// `Auth::attempt` recibe `['usuario' => ...]`, no `['username' => ...]`.
        //
        // `EloquentUserProvider::retrieveByCredentials()` arma el `where` con
        // las claves del array recibido, sin mirar `getAuthIdentifierName()`.
        // Mandarle `username` contra una columna que no existe en
        // `global.usuarios` producía "no existe la columna username" y el
        // login terminaba en 500.
        //
        // El nombre de la columna sale del modelo configurado en
        // `config/auth.php`, no de `$this->user()`: en este punto todavia no
        // hay nadie autenticado y ese metodo devuelve null.
        $modeloAuth = config('auth.providers.users.model');

        $columnaUsuario = (new $modeloAuth())->getAuthIdentifierName();

        $credenciales = [
            $columnaUsuario => $this->string('username')->value(),
            'password' => $this->string('password')->value(),
        ];

        if (!Auth::attempt($credenciales, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey(), $this->decayMinutes() * 60);

            throw ValidationException::withMessages([
                'username' => 'Credenciales invalidas.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), $this->maxAttempts())) {
            return;
        }

        event(new Lockout($this));

        $segundos = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'username' => "Demasiados intentos fallidos. Intente de nuevo en {$segundos} segundos.",
        ]);
    }

    public function maxAttempts(): int
    {
        return (int) env('AUTH_MAX_INTENTOS', 5);
    }

    public function decayMinutes(): int
    {
        return (int) env('AUTH_BLOQUEO_MINUTOS', 15);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(
            Str::lower((string) $this->string('username')) . '|' . $this->ip()
        );
    }
}