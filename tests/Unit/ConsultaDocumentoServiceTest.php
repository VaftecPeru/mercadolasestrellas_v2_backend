<?php

namespace Tests\Unit;

use App\Services\ConsultaDocumentoService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ConsultaDocumentoServiceTest extends TestCase
{
    private ConsultaDocumentoService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.decolecta.base_url', 'https://api.decolecta.com/v1');
        Config::set('services.decolecta.token', 'test_token_123');
        Config::set('services.decolecta.timeout', 2);
        $this->service = new ConsultaDocumentoService;
    }

    public function test_valida_formato_dni(): void
    {
        $this->assertTrue($this->service->esDniValido('12345678'));
        $this->assertFalse($this->service->esDniValido('1234567'));
        $this->assertFalse($this->service->esDniValido('123456789'));
        $this->assertFalse($this->service->esDniValido('1234567A'));
        $this->assertFalse($this->service->esDniValido(null));
        $this->assertFalse($this->service->esDniValido('-'));
    }

    public function test_valida_formato_ruc(): void
    {
        $this->assertTrue($this->service->esRucValido('20601030013'));
        $this->assertTrue($this->service->esRucValido('10460278971'));
        $this->assertFalse($this->service->esRucValido('2060103001'));
        $this->assertFalse($this->service->esRucValido('206010300133'));
        $this->assertFalse($this->service->esRucValido('2060103001A'));
        $this->assertFalse($this->service->esRucValido(null));
    }

    public function test_rechaza_dni_con_formato_invalido(): void
    {
        $resultado = $this->service->consultarDni('12345');
        $this->assertFalse($resultado['success']);
        $this->assertEquals('DNI_INVALIDO', $resultado['codigo']);
    }

    public function test_rechaza_ruc_con_formato_invalido(): void
    {
        $resultado = $this->service->consultarRuc('12345');
        $this->assertFalse($resultado['success']);
        $this->assertEquals('RUC_INVALIDO', $resultado['codigo']);
    }

    public function test_consulta_dni_exitoso(): void
    {
        Http::fake([
            'https://api.decolecta.com/v1/reniec/dni*' => Http::response([
                'first_name' => 'JUAN CARLOS',
                'first_last_name' => 'PEREZ',
                'second_last_name' => 'QUISPE',
                'full_name' => 'JUAN CARLOS PEREZ QUISPE',
                'document_number' => '87654321',
            ], 200),
        ]);

        $resultado = $this->service->consultarDni('87654321', true);

        $this->assertTrue($resultado['success']);
        $this->assertEquals('reniec', $resultado['fuente']);
        $this->assertEquals('JUAN CARLOS', $resultado['data']['nombres']);
        $this->assertEquals('PEREZ', $resultado['data']['apellido_paterno']);
        $this->assertEquals('QUISPE', $resultado['data']['apellido_materno']);
        $this->assertEquals('87654321', $resultado['data']['dni']);
    }

    public function test_consulta_dni_no_encontrado(): void
    {
        Http::fake([
            'https://api.decolecta.com/v1/reniec/dni*' => Http::response([
                'message' => 'DNI no encontrado',
            ], 404),
        ]);

        $resultado = $this->service->consultarDni('00000000', true);

        $this->assertFalse($resultado['success']);
        $this->assertEquals('NO_ENCONTRADO', $resultado['codigo']);
    }

    public function test_consulta_dni_error_conexion(): void
    {
        Http::fake([
            'https://api.decolecta.com/v1/reniec/dni*' => function () {
                throw new ConnectionException('Connection timed out');
            },
        ]);

        $resultado = $this->service->consultarDni('87654321', true);

        $this->assertFalse($resultado['success']);
        $this->assertEquals('ERROR_CONEXION', $resultado['codigo']);
    }

    public function test_consulta_ruc_exitoso(): void
    {
        Http::fake([
            'https://api.decolecta.com/v1/sunat/ruc*' => Http::response([
                'numero_documento' => '20601030013',
                'razon_social' => 'MERCADO LAS ESTRELLAS S.A.C.',
                'estado' => 'ACTIVO',
                'condicion' => 'HABIDO',
                'direccion' => 'AV. PRINCIPAL 123',
                'departamento' => 'LIMA',
                'provincia' => 'LIMA',
                'distrito' => 'LOS OLIVOS',
                'ubigeo' => '150117',
            ], 200),
        ]);

        $resultado = $this->service->consultarRuc('20601030013');

        $this->assertTrue($resultado['success']);
        $this->assertEquals('sunat', $resultado['fuente']);
        $this->assertEquals('MERCADO LAS ESTRELLAS S.A.C.', $resultado['data']['razon_social']);
        $this->assertEquals('ACTIVO', $resultado['data']['estado']);
        $this->assertEquals('HABIDO', $resultado['data']['condicion']);
    }
}
