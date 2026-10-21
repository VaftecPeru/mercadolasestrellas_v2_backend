<?php

namespace Tests\Feature;

use App\Http\Middleware\AutenticarToken;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ConsultaDocumentoFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.decolecta.base_url', 'https://api.decolecta.com/v1');
        Config::set('services.decolecta.token', 'test_token_123');
        Config::set('services.decolecta.timeout', 2);
    }

    public function test_rutas_consultas_requieren_autenticacion(): void
    {
        $responseDni = $this->getJson('/api/v1/consultas/dni/12345678');
        $responseDni->assertStatus(401);

        $responseRuc = $this->getJson('/api/v1/consultas/ruc/20601030013');
        $responseRuc->assertStatus(401);
    }

    public function test_endpoint_dni_formato_invalido(): void
    {
        $this->withoutMiddleware(AutenticarToken::class);

        $response = $this->getJson('/api/v1/consultas/dni/12345');
        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'codigo' => 'DNI_INVALIDO',
            ]);
    }

    public function test_endpoint_dni_exitoso(): void
    {
        $this->withoutMiddleware(AutenticarToken::class);

        Http::fake([
            'https://api.decolecta.com/v1/reniec/dni*' => Http::response([
                'first_name' => 'ANA MARIA',
                'first_last_name' => 'MENDOZA',
                'second_last_name' => 'LOPEZ',
                'full_name' => 'ANA MARIA MENDOZA LOPEZ',
                'document_number' => '44556677',
            ], 200),
        ]);

        $response = $this->getJson('/api/v1/consultas/dni/44556677?forzar=true');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'fuente' => 'reniec',
                'data' => [
                    'dni' => '44556677',
                    'nombres' => 'ANA MARIA',
                    'apellido_paterno' => 'MENDOZA',
                    'apellido_materno' => 'LOPEZ',
                    'nombre_completo' => 'ANA MARIA MENDOZA LOPEZ',
                ],
            ]);
    }

    public function test_endpoint_dni_no_encontrado(): void
    {
        $this->withoutMiddleware(AutenticarToken::class);

        Http::fake([
            'https://api.decolecta.com/v1/reniec/dni*' => Http::response([
                'error' => 'Not found',
            ], 404),
        ]);

        $response = $this->getJson('/api/v1/consultas/dni/00000000?forzar=true');
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'codigo' => 'NO_ENCONTRADO',
            ]);
    }

    public function test_endpoint_dni_error_conexion(): void
    {
        $this->withoutMiddleware(AutenticarToken::class);

        Http::fake([
            'https://api.decolecta.com/v1/reniec/dni*' => function () {
                throw new ConnectionException('Timeout');
            },
        ]);

        $response = $this->getJson('/api/v1/consultas/dni/44556677?forzar=true');
        $response->assertStatus(503)
            ->assertJson([
                'success' => false,
                'codigo' => 'ERROR_CONEXION',
            ]);
    }

    public function test_endpoint_ruc_formato_invalido(): void
    {
        $this->withoutMiddleware(AutenticarToken::class);

        $response = $this->getJson('/api/v1/consultas/ruc/123');
        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'codigo' => 'RUC_INVALIDO',
            ]);
    }

    public function test_endpoint_ruc_exitoso(): void
    {
        $this->withoutMiddleware(AutenticarToken::class);

        Http::fake([
            'https://api.decolecta.com/v1/sunat/ruc*' => Http::response([
                'numero_documento' => '20601030013',
                'razon_social' => 'COMERCIAL EL SOL S.A.C.',
                'estado' => 'ACTIVO',
                'condicion' => 'HABIDO',
                'direccion' => 'AV. LOS ALISOS 456',
                'departamento' => 'LIMA',
                'provincia' => 'LIMA',
                'distrito' => 'SAN MARTIN DE PORRES',
                'ubigeo' => '150135',
            ], 200),
        ]);

        $response = $this->getJson('/api/v1/consultas/ruc/20601030013');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'fuente' => 'sunat',
                'data' => [
                    'ruc' => '20601030013',
                    'razon_social' => 'COMERCIAL EL SOL S.A.C.',
                    'estado' => 'ACTIVO',
                    'condicion' => 'HABIDO',
                ],
            ]);
    }
}
