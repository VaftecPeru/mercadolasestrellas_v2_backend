<?php

namespace App\Services;

use App\Models\Persona;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ConsultaDocumentoService
{
    private string $baseUrl;

    private ?string $token;

    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.decolecta.base_url', 'https://api.decolecta.com/v1'), '/');
        $this->token = config('services.decolecta.token');
        $this->timeout = (int) config('services.decolecta.timeout', 5);
    }

    /**
     * Valida si un DNI tiene formato peruano válido (8 dígitos numéricos).
     */
    public function esDniValido(?string $dni): bool
    {
        return $dni !== null && preg_match('/^\d{8}$/', trim($dni)) === 1;
    }

    /**
     * Valida si un RUC tiene formato peruano válido (11 dígitos numéricos).
     */
    public function esRucValido(?string $ruc): bool
    {
        return $ruc !== null && preg_match('/^\d{11}$/', trim($ruc)) === 1;
    }

    /**
     * Consulta información de una persona por su DNI.
     * Busca primero en la base de datos local para optimizar saldo de la API.
     *
     * @return array{success: bool, fuente?: string, data?: array, codigo?: string, message?: string}
     */
    public function consultarDni(string $dni, bool $forzarExterno = false): array
    {
        $dniLimpio = trim($dni);

        if (! $this->esDniValido($dniLimpio)) {
            return [
                'success' => false,
                'codigo' => 'DNI_INVALIDO',
                'message' => 'El número de DNI debe contener exactamente 8 dígitos numéricos.',
            ];
        }

        // 1. Optimización: Buscar primero en base de datos local
        if (! $forzarExterno) {
            $personaLocal = Persona::where('dni', $dniLimpio)
                ->whereNotNull('nombre')
                ->where('nombre', '!=', '')
                ->where('dni', '!=', '-')
                ->first();

            if ($personaLocal) {
                return [
                    'success' => true,
                    'fuente' => 'local',
                    'data' => [
                        'dni' => $personaLocal->dni,
                        'nombres' => $personaLocal->nombre,
                        'apellido_paterno' => $personaLocal->apellido_paterno ?? '',
                        'apellido_materno' => $personaLocal->apellido_materno ?? '',
                        'nombre_completo' => $personaLocal->nombre_completo ?: trim("{$personaLocal->nombre} {$personaLocal->apellido_paterno} {$personaLocal->apellido_materno}"),
                        'direccion' => $personaLocal->direccion ?? '',
                        'telefono' => $personaLocal->telefono ?? '',
                        'correo' => $personaLocal->correo ?? '',
                        'sexo' => $personaLocal->sexo ?? '',
                    ],
                ];
            }
        }

        // 2. Validación de credencial del servicio externo
        if (empty($this->token)) {
            Log::warning('ConsultaDocumentoService: DECOLECTA_TOKEN no está configurado en .env');

            return [
                'success' => false,
                'codigo' => 'TOKEN_NO_CONFIGURADO',
                'message' => 'El servicio de consulta automática no está configurado. Ingrese los datos manualmente.',
            ];
        }

        // 3. Consulta a la API de Decolecta (RENIEC)
        try {
            $response = Http::withToken($this->token)
                ->acceptJson()
                ->timeout($this->timeout)
                ->get("{$this->baseUrl}/reniec/dni", [
                    'numero' => $dniLimpio,
                ]);

            if ($response->successful()) {
                $json = $response->json();

                $nombres = trim((string) ($json['first_name'] ?? ''));
                $apellidoPaterno = trim((string) ($json['first_last_name'] ?? ''));
                $apellidoMaterno = trim((string) ($json['second_last_name'] ?? ''));
                $nombreCompleto = trim((string) ($json['full_name'] ?? "{$nombres} {$apellidoPaterno} {$apellidoMaterno}"));

                if (empty($nombres) && empty($nombreCompleto)) {
                    return [
                        'success' => false,
                        'codigo' => 'NO_ENCONTRADO',
                        'message' => 'No se encontraron datos asociados al DNI consultado.',
                    ];
                }

                return [
                    'success' => true,
                    'fuente' => 'reniec',
                    'data' => [
                        'dni' => (string) ($json['document_number'] ?? $dniLimpio),
                        'nombres' => $nombres,
                        'apellido_paterno' => $apellidoPaterno,
                        'apellido_materno' => $apellidoMaterno,
                        'nombre_completo' => $nombreCompleto,
                    ],
                ];
            }

            if ($response->status() === 404 || $response->status() === 422 || $response->status() === 400) {
                return [
                    'success' => false,
                    'codigo' => 'NO_ENCONTRADO',
                    'message' => 'El DNI ingresado no fue encontrado en RENIEC. Ingrese los datos manualmente.',
                ];
            }

            Log::error('ConsultaDocumentoService Error HTTP DNI: '.$response->status().' - '.$response->body());

            return [
                'success' => false,
                'codigo' => 'ERROR_SERVICIO',
                'message' => 'El servicio de consulta externa reportó un error. Ingrese los datos manualmente.',
            ];
        } catch (ConnectionException $e) {
            Log::warning('ConsultaDocumentoService Timeout/Conexión DNI: '.$e->getMessage());

            return [
                'success' => false,
                'codigo' => 'ERROR_CONEXION',
                'message' => 'No se pudo conectar con el servicio de consulta (tiempo agotado). Ingrese los datos manualmente.',
            ];
        } catch (\Throwable $e) {
            Log::error('ConsultaDocumentoService Excepción DNI: '.$e->getMessage());

            return [
                'success' => false,
                'codigo' => 'ERROR_INESPERADO',
                'message' => 'Ocurrió un inconveniente al consultar el DNI. Ingrese los datos manualmente.',
            ];
        }
    }

    /**
     * Consulta información de una empresa o persona con negocio por su RUC.
     *
     * @return array{success: bool, fuente?: string, data?: array, codigo?: string, message?: string}
     */
    public function consultarRuc(string $ruc): array
    {
        $rucLimpio = trim($ruc);

        if (! $this->esRucValido($rucLimpio)) {
            return [
                'success' => false,
                'codigo' => 'RUC_INVALIDO',
                'message' => 'El número de RUC debe contener exactamente 11 dígitos numéricos.',
            ];
        }

        if (empty($this->token)) {
            Log::warning('ConsultaDocumentoService: DECOLECTA_TOKEN no está configurado en .env');

            return [
                'success' => false,
                'codigo' => 'TOKEN_NO_CONFIGURADO',
                'message' => 'El servicio de consulta automática no está configurado. Ingrese los datos manualmente.',
            ];
        }

        try {
            $response = Http::withToken($this->token)
                ->acceptJson()
                ->timeout($this->timeout)
                ->get("{$this->baseUrl}/sunat/ruc", [
                    'numero' => $rucLimpio,
                ]);

            if ($response->successful()) {
                $json = $response->json();
                $razonSocial = trim((string) ($json['razon_social'] ?? ''));

                if (empty($razonSocial)) {
                    return [
                        'success' => false,
                        'codigo' => 'NO_ENCONTRADO',
                        'message' => 'No se encontraron datos para el RUC consultado.',
                    ];
                }

                return [
                    'success' => true,
                    'fuente' => 'sunat',
                    'data' => [
                        'ruc' => (string) ($json['numero_documento'] ?? $rucLimpio),
                        'razon_social' => $razonSocial,
                        'estado' => trim((string) ($json['estado'] ?? '')),
                        'condicion' => trim((string) ($json['condicion'] ?? '')),
                        'direccion' => trim((string) ($json['direccion'] ?? '')),
                        'departamento' => trim((string) ($json['departamento'] ?? '')),
                        'provincia' => trim((string) ($json['provincia'] ?? '')),
                        'distrito' => trim((string) ($json['distrito'] ?? '')),
                        'ubigeo' => trim((string) ($json['ubigeo'] ?? '')),
                    ],
                ];
            }

            if ($response->status() === 404 || $response->status() === 422 || $response->status() === 400) {
                return [
                    'success' => false,
                    'codigo' => 'NO_ENCONTRADO',
                    'message' => 'El RUC ingresado no fue encontrado en SUNAT. Ingrese los datos manualmente.',
                ];
            }

            Log::error('ConsultaDocumentoService Error HTTP RUC: '.$response->status().' - '.$response->body());

            return [
                'success' => false,
                'codigo' => 'ERROR_SERVICIO',
                'message' => 'El servicio de consulta externa reportó un error. Ingrese los datos manualmente.',
            ];
        } catch (ConnectionException $e) {
            Log::warning('ConsultaDocumentoService Timeout/Conexión RUC: '.$e->getMessage());

            return [
                'success' => false,
                'codigo' => 'ERROR_CONEXION',
                'message' => 'No se pudo conectar con el servicio de consulta SUNAT. Ingrese los datos manualmente.',
            ];
        } catch (\Throwable $e) {
            Log::error('ConsultaDocumentoService Excepción RUC: '.$e->getMessage());

            return [
                'success' => false,
                'codigo' => 'ERROR_INESPERADO',
                'message' => 'Ocurrió un inconveniente al consultar el RUC. Ingrese los datos manualmente.',
            ];
        }
    }
}
