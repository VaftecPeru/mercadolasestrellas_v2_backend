<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LeastPrivilegeRouteTest extends TestCase
{
    #[DataProvider('restrictedOperationalRoutes')]
    public function test_operational_routes_require_their_module_permission(
        string $uri,
        string $permission
    ): void {
        $route = collect(Route::getRoutes())->first(function ($route) use ($uri) {
            return $route->uri() === $uri
                && in_array('GET', $route->methods(), true);
        });

        $this->assertNotNull($route, 'No se encontró la ruta GET '.$uri);

        $middleware = $route->gatherMiddleware();

        $this->assertContains('auth.token', $middleware);
        $this->assertContains($permission, $middleware);
    }

    public static function restrictedOperationalRoutes(): array
    {
        return [
            'inquilinos' => ['api/v1/inquilinos', 'permiso:4'],
            'puestos sin socio' => ['api/v1/puestos/sin-socio', 'permiso:4'],
            'puestos sin inquilino' => ['api/v1/puestos/sin-inquilino', 'permiso:4'],
            'bancos' => ['api/v1/setup/bancos', 'permiso:7'],
            'cuentas bancarias' => ['api/v1/setup/banco-cuentas', 'permiso:7'],
        ];
    }
}
