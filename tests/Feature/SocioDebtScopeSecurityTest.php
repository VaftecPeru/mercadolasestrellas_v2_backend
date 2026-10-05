<?php

namespace Tests\Feature;

use App\Http\Controllers\DeudaController;
use App\Models\Socio;
use App\Models\Usuario;
use App\Support\ScopeSocio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use ReflectionMethod;
use Tests\TestCase;

class SocioDebtScopeSecurityTest extends TestCase
{
    public function test_general_deudas_route_is_restricted_to_pagos_module(): void
    {
        $route = collect(Route::getRoutes())->first(function ($route) {
            return $route->uri() === 'api/v1/deudas'
                && in_array('GET', $route->methods(), true);
        });

        $this->assertNotNull($route, 'No se encontró la ruta GET api/v1/deudas.');

        $middleware = $route->gatherMiddleware();

        $this->assertContains('auth.token', $middleware);
        $this->assertContains('permiso:7', $middleware);
        $this->assertNotContains('permiso:7,9', $middleware);
    }

    public function test_scope_socio_overrides_foreign_filters(): void
    {
        $usuario = new Usuario;
        $usuario->id_rol = 2;

        $socio = new Socio;
        $socio->id_socio = 77;

        $usuario->setRelation('Socio', $socio);

        $request = Request::create('/api/v1/deudas', 'GET', [
            'id_socio' => 999,
            'id_puesto' => 123,
            'nombre_socio' => 'Otro socio',
        ]);
        $request->attributes->set('usuario', $usuario);

        $harness = new ScopeSocioHarness;
        $harness->apply($request);

        $this->assertSame(77, (int) $request->input('id_socio'));
        $this->assertSame(77, (int) $request->query('id_socio'));
        $this->assertFalse($request->query->has('id_puesto'));
        $this->assertFalse($request->query->has('nombre_socio'));
    }

    public function test_deuda_index_keeps_defense_in_depth_scope(): void
    {
        $method = new ReflectionMethod(DeudaController::class, 'index');
        $lines = file($method->getFileName());
        $source = implode('', array_slice(
            $lines,
            $method->getStartLine() - 1,
            $method->getEndLine() - $method->getStartLine() + 1
        ));

        $this->assertStringContainsString(
            '$this->aplicarScopeSocio($request);',
            $source
        );
        $this->assertStringContainsString(
            "$query->where('id_socio', $idSocio);",
            $source
        );
    }
}

class ScopeSocioHarness
{
    use ScopeSocio;

    public function apply(Request $request): void
    {
        $this->aplicarScopeSocio($request);
    }
}
