<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\EstadoFactura;
use App\Enums\Rol;
use App\Models\User;
use App\Policies\ModuloPolicy;
use App\Services\AuditoriaService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AuditoriaService::class);
    }

    public function boot(): void
    {
        if (config('app.env') === 'production' || request()->header('X-Forwarded-Proto') === 'https') {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        $this->registrarGates();
        $this->registrarPolicies();
    }

    /**
     * Gates de capacidad por modulo: Gate::allows('crear', 'gastos').
     *
     * Se registra la misma Policy como callback de cada capacidad para
     * que el codigo en controladores y en el menu use una unica fuente.
     */
    private function registrarGates(): void
    {
        $policy = ModuloPolicy::class;

        foreach (['ver', 'crear', 'editar', 'eliminar', 'anular', 'administrar'] as $capacidad) {
            Gate::define($capacidad, function (User $user, string $modulo) use ($policy, $capacidad): bool {
                return (new $policy)->{$capacidad}($user, $modulo);
            });
        }
    }

    private function registrarPolicies(): void
    {
        Gate::policy(\App\Models\User::class, ModuloPolicy::class);
    }
}
