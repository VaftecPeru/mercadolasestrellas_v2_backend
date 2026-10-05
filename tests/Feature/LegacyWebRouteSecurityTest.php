<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class LegacyWebRouteSecurityTest extends TestCase
{
    /**
     * @dataProvider protectedLegacyRoutes
     */
    public function test_legacy_data_routes_are_authenticated_and_authorized(
        string $uri,
        string $permission
    ): void {
        $route = collect(Route::getRoutes())->first(function ($route) use ($uri) {
            return $route->uri() === $uri
                && in_array('GET', $route->methods(), true);
        });

        $this->assertNotNull($route, 'No se encontró la ruta heredada GET '.$uri);

        $middleware = $route->gatherMiddleware();

        $this->assertContains('auth.token', $middleware);
        $this->assertContains($permission, $middleware);
    }

    public static function protectedLegacyRoutes(): array
    {
        return [
            'socios' => ['socios', 'permiso:3'],
            'socios seleccionar' => ['socios/seleccionar', 'permiso:3'],
            'socios export' => ['socios/export', 'permiso:3'],
            'socios exportar' => ['socios/exportar', 'permiso:3'],
            'socios pdf' => ['socios/export-pdf', 'permiso:3'],
            'pagos' => ['pagos', 'permiso:7'],
            'pagos export' => ['pagos/export', 'permiso:7'],
            'pagos pdf' => ['pagos/export-pdf', 'permiso:7'],
        ];
    }
}
