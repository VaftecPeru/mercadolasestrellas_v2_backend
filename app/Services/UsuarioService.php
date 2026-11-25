<?php

namespace App\Services;

use App\Models\Persona;
use App\Models\Socio;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UsuarioService
{
    public const ID_ROL_ADMINISTRADOR = 1;

    public const ID_ROL_SOCIO = 2;

    public const ID_ROL_CAJERO = 3;

    /**
     * Lista de contraseñas predeterminadas o débiles prohibidas para el acceso inicial.
     */
    public const CONTRASENIAS_PROHIBIDAS = [
        'password',
        '123456',
        '12345678',
        '123456789',
        'admin',
        'admin123',
        'administrador',
        'qwerty',
        'mercado',
        'mercado123',
    ];

    /**
     * Genera una contraseña temporal aleatoria (solo texto plano, nunca almacenada así).
     */
    public function generarContraseniaTemporal(int $longitud = 10): string
    {
        return Str::random(max(10, $longitud));
    }

    /**
     * Verifica si ya existe al menos un administrador activo y no bloqueado en el sistema.
     */
    public function existeAdministradorActivo(): bool
    {
        return Usuario::where('id_rol', self::ID_ROL_ADMINISTRADOR)
            ->where('estado', '1')
            ->where('bloqueado', 0)
            ->exists();
    }

    /**
     * Valida que una contraseña inicial no sea una credencial predeterminada o trivial.
     */
    public function validarContraseniaSegura(string $password, string $nombreUsuario = '', string $dni = ''): void
    {
        $limpia = trim($password);

        if (strlen($limpia) < 8) {
            throw new \InvalidArgumentException('La contraseña debe tener al menos 8 caracteres.');
        }

        $normalizada = strtolower($limpia);

        if (in_array($normalizada, self::CONTRASENIAS_PROHIBIDAS, true)) {
            throw new \InvalidArgumentException('No se permite el uso de contraseñas predeterminadas o débiles (como "password" o "admin").');
        }

        if ($nombreUsuario !== '' && $normalizada === strtolower(trim($nombreUsuario))) {
            throw new \InvalidArgumentException('La contraseña no puede ser igual al nombre de usuario.');
        }

        if ($dni !== '' && $limpia === trim($dni)) {
            throw new \InvalidArgumentException('La contraseña no puede ser igual al número de DNI.');
        }
    }

    /**
     * Aprovisiona de forma segura el administrador inicial del sistema cuando
     * la base de datos se despliega en limpio sin credenciales predeterminadas.
     *
     * @param  array  $datos  nombre_usuario, nombre, apellido_paterno, apellido_materno, dni, correo, telefono, direccion, sexo
     * @return array{usuario: Usuario, persona: Persona, password_inicial: string, password_autogenerado: bool}
     */
    public function crearAdministradorInicial(
        array $datos,
        ?string $passwordPersonalizada = null,
        bool $forzarCambioPassword = true
    ): array {
        if ($this->existeAdministradorActivo()) {
            throw new \RuntimeException(
                'Ya existe un administrador activo en el sistema. Gestione cuentas adicionales desde el módulo Usuarios y Roles.'
            );
        }

        $nombreUsuario = trim((string) ($datos['nombre_usuario'] ?? ''));
        $dni = trim((string) ($datos['dni'] ?? ''));
        $telefono = trim((string) ($datos['telefono'] ?? ''));

        if ($nombreUsuario === '' || strlen($nombreUsuario) < 3) {
            throw new \InvalidArgumentException('El nombre de usuario del administrador debe tener al menos 3 caracteres.');
        }

        if (! $this->esDniValido($dni)) {
            throw new \InvalidArgumentException('El DNI del administrador debe tener exactamente 8 dígitos numéricos.');
        }

        if (preg_match('/^\d{9}$/', $telefono) !== 1) {
            throw new \InvalidArgumentException('El teléfono del administrador debe tener exactamente 9 dígitos numéricos.');
        }

        foreach (['nombre', 'apellido_paterno', 'apellido_materno', 'correo'] as $campoRequerido) {
            if (trim((string) ($datos[$campoRequerido] ?? '')) === '') {
                throw new \InvalidArgumentException("El campo {$campoRequerido} es obligatorio para el administrador inicial.");
            }
        }

        if (Usuario::where('nombre_usuario', $nombreUsuario)->exists()) {
            throw new \InvalidArgumentException('El nombre de usuario ya está registrado.');
        }

        if (Persona::where('dni', $dni)->exists()) {
            throw new \InvalidArgumentException('El DNI ya está registrado.');
        }

        $passwordAutogenerado = $passwordPersonalizada === null || trim($passwordPersonalizada) === '';
        if ($passwordAutogenerado) {
            $passwordFinal = $this->generarContraseniaTemporal(12);
            $debeCambiar = true;
        } else {
            $passwordFinal = trim($passwordPersonalizada);
            $this->validarContraseniaSegura($passwordFinal, $nombreUsuario, $dni);
            $debeCambiar = $forzarCambioPassword;
        }

        return DB::transaction(function () use ($datos, $nombreUsuario, $dni, $telefono, $passwordFinal, $debeCambiar, $passwordAutogenerado) {
            $datosNormalizados = array_merge($datos, [
                'dni' => $dni,
                'telefono' => $telefono,
                'direccion' => trim((string) ($datos['direccion'] ?? '')) ?: 'Mercado Las Estrellas',
                'sexo' => in_array((string) ($datos['sexo'] ?? '1'), ['1', '2'], true) ? (string) $datos['sexo'] : '1',
                'estado' => '1',
                'fecha_registro' => $datos['fecha_registro'] ?? now(),
            ]);

            $persona = $this->crearPersona($datosNormalizados);

            $usuario = new Usuario;
            $usuario->nombre_usuario = $nombreUsuario;
            $usuario->id_persona = $persona->id_persona;
            $usuario->contrasenia = Hash::make($passwordFinal);
            $usuario->id_rol = self::ID_ROL_ADMINISTRADOR;
            $usuario->estado = '1';
            $usuario->debe_cambiar_password = $debeCambiar;
            $usuario->bloqueado = false;
            $usuario->fecha_registro = $datosNormalizados['fecha_registro'];
            $usuario->save();

            return [
                'usuario' => $usuario,
                'persona' => $persona,
                'password_inicial' => $passwordFinal,
                'password_autogenerado' => $passwordAutogenerado,
            ];
        });
    }

    /**
     * Indica si un DNI es válido para ser usado como nombre de usuario (8 dígitos).
     */
    private function esDniValido(?string $dni): bool
    {
        return $dni !== null && preg_match('/^\d{8}$/', $dni) === 1;
    }

    /**
     * Crea (si no existe) la cuenta de usuario asociada a un socio.
     *

     * @return array{usuario: Usuario, password_temporal: string|null, creado: bool}
     */
    public function generarCuentaSocio(Socio $socio): array
    {
        if ($socio->id_usuario) {
            $usuario = Usuario::find($socio->id_usuario);
            if ($usuario) {
                return ['usuario' => $usuario, 'password_temporal' => null, 'creado' => false];
            }
        }

        $dni = $socio->persona->dni ?? null;
        if (! $this->esDniValido($dni)) {
            throw new \InvalidArgumentException('El socio no tiene un DNI válido para generar su usuario.');
        }

        $cuenta = $this->crearCuentaSocio($socio, $dni);

        return ['usuario' => $cuenta['usuario'], 'password_temporal' => $cuenta['password_temporal'], 'creado' => true];
    }

    /**
     * Crea la cuenta de un socio (rol Socio, contraseña temporal) y la enlaza.
     *
     * @return array{usuario: Usuario, password_temporal: string}
     */
    private function crearCuentaSocio(Socio $socio, string $nombreUsuario): array
    {
        $passwordTemporal = $this->generarContraseniaTemporal();

        $usuario = new Usuario;
        $usuario->nombre_usuario = $nombreUsuario;
        $usuario->id_persona = $socio->id_socio;
        $usuario->contrasenia = Hash::make($passwordTemporal);
        $usuario->id_rol = self::ID_ROL_SOCIO;
        $usuario->estado = '1';
        $usuario->debe_cambiar_password = true;
        $usuario->bloqueado = false;
        $usuario->fecha_registro = now();
        $usuario->save();

        $socio->id_usuario = $usuario->id_usuario;
        $socio->save();

        return ['usuario' => $usuario, 'password_temporal' => $passwordTemporal];
    }

    /**
     * Regenera las credenciales de un socio existente (nueva contraseña temporal).
     *
     * @return array{usuario: Usuario, password_temporal: string}
     */
    public function regenerarCredenciales(Socio $socio): array
    {
        $usuario = $socio->id_usuario ? Usuario::find($socio->id_usuario) : null;
        if (! $usuario) {
            throw new \InvalidArgumentException('El socio no tiene una cuenta de usuario creada.');
        }

        $passwordTemporal = $this->generarPasswordTemporal($usuario);

        return ['usuario' => $usuario, 'password_temporal' => $passwordTemporal];
    }

    /**
     * Genera una nueva contraseña temporal para un usuario, la almacena con hash
     * y marca debe_cambiar_password = 1. Devuelve la contraseña en texto plano una sola vez.
     */
    public function generarPasswordTemporal(Usuario $usuario): string
    {
        $passwordTemporal = $this->generarContraseniaTemporal();

        $usuario->contrasenia = Hash::make($passwordTemporal);
        $usuario->debe_cambiar_password = true;
        $usuario->save();

        return $passwordTemporal;
    }

    /**
     * Crea un usuario (Administrador/Cajero) con sus datos personales en `personas`
     * y una contraseña temporal.
     *
     * @param  array  $datos  nombre_usuario, id_rol, nombre, apellido_paterno, apellido_materno, dni, correo, telefono, direccion, sexo, estado, fecha_registro
     * @return array{usuario: Usuario, password_temporal: string}
     */
    public function crearUsuario(array $datos): array
    {
        $idRol = (int) ($datos['id_rol'] ?? 0);
        $nombreUsuario = $datos['nombre_usuario'] ?? '';

        if ($idRol === self::ID_ROL_SOCIO) {
            throw new \InvalidArgumentException('Los usuarios con rol Socio se crean desde el Módulo Socios.');
        }

        if (Usuario::where('nombre_usuario', $nombreUsuario)->exists()) {
            throw new \InvalidArgumentException('El nombre de usuario ya está registrado.');
        }

        if (! empty($datos['dni']) && Persona::where('dni', $datos['dni'])->exists()) {
            throw new \InvalidArgumentException('El DNI ya está registrado.');
        }

        return DB::transaction(function () use ($datos, $nombreUsuario, $idRol) {
            $persona = $this->crearPersona($datos);

            $passwordTemporal = $this->generarContraseniaTemporal();

            $usuario = new Usuario;
            $usuario->nombre_usuario = $nombreUsuario;
            $usuario->id_persona = $persona->id_persona;
            $usuario->contrasenia = Hash::make($passwordTemporal);
            $usuario->id_rol = $idRol;
            $usuario->estado = $datos['estado'] ?? '1';
            $usuario->debe_cambiar_password = true;
            $usuario->bloqueado = false;
            $usuario->fecha_registro = $datos['fecha_registro'] ?? now();
            $usuario->save();

            return ['usuario' => $usuario, 'password_temporal' => $passwordTemporal];
        });
    }

    /**
     * Cantidad de socios activos que aún no tienen cuenta de usuario.
     */
    public function contarSociosSinCuenta(): int
    {
        return Socio::whereNull('id_usuario')->where('estado', '1')->count();
    }

    /**
     * Actualiza los datos personales (personas) y de cuenta (usuarios) de un usuario.
     */
    public function actualizarUsuario(Usuario $usuario, array $datos): Usuario
    {
        $nombreUsuario = $datos['nombre_usuario'] ?? '';

        if (Usuario::where('nombre_usuario', $nombreUsuario)->where('id_usuario', '!=', $usuario->id_usuario)->exists()) {
            throw new \InvalidArgumentException('El nombre de usuario ya está registrado.');
        }

        if (! empty($datos['dni']) && Persona::where('dni', $datos['dni'])->where('id_persona', '!=', $usuario->id_persona)->exists()) {
            throw new \InvalidArgumentException('El DNI ya está registrado.');
        }

        return DB::transaction(function () use ($usuario, $datos, $nombreUsuario) {
            $persona = $usuario->id_persona ? Persona::find($usuario->id_persona) : new Persona;
            $this->rellenarPersona($persona, $datos);
            $persona->save();

            if (! $usuario->id_persona) {
                $usuario->id_persona = $persona->id_persona;
            }

            $usuario->nombre_usuario = $nombreUsuario;
            $usuario->id_rol = (int) ($datos['id_rol'] ?? $usuario->id_rol);
            $usuario->estado = $datos['estado'] ?? $usuario->estado;
            $usuario->fecha_registro = $datos['fecha_registro'] ?? $usuario->fecha_registro;
            $usuario->save();

            return $usuario;
        });
    }

    public function actualizarTelefono(Usuario $usuario, string $telefono): Persona
    {
        $persona = $usuario->id_persona ? Persona::find($usuario->id_persona) : null;

        if (! $persona && $usuario->Socio) {
            $persona = $usuario->Socio->persona;
        }

        if (! $persona) {
            throw new \InvalidArgumentException('El usuario no tiene datos personales asociados.');
        }

        $persona->telefono = $telefono;
        $persona->save();

        return $persona;
    }

    /**
     * Crea un registro en `personas` a partir de los datos del formulario.
     */
    private function crearPersona(array $datos): Persona
    {
        $persona = $this->rellenarPersona(new Persona, $datos);
        $persona->save();

        return $persona;
    }

    /**
     * Rellena los campos personales de un registro `personas` (nuevo o existente).
     */
    private function rellenarPersona(Persona $persona, array $datos): Persona
    {
        $nombreCompleto = trim(
            ($datos['nombre'] ?? '').' '.($datos['apellido_paterno'] ?? '').' '.($datos['apellido_materno'] ?? '')
        );

        $persona->nombre = $datos['nombre'] ?? '';
        $persona->apellido_paterno = $datos['apellido_paterno'] ?? '';
        $persona->apellido_materno = $datos['apellido_materno'] ?? '';
        $persona->dni = $datos['dni'] ?? '';
        $persona->correo = $datos['correo'] ?? '';
        $persona->telefono = $datos['telefono'] ?? '';
        $persona->direccion = $datos['direccion'] ?? '';
        $persona->sexo = $datos['sexo'] ?? '';
        $persona->fecha_registro = $datos['fecha_registro'] ?? now();
        $persona->nombre_completo = $nombreCompleto;

        return $persona;
    }

    /**
     * Habilita/deshabilita el acceso de un socio cambiando el estado de su usuario.
     *
     * @return array{estado: string, habilitado: bool}
     */
    public function toggleAcceso(Socio $socio): array
    {
        $usuario = $socio->id_usuario ? Usuario::find($socio->id_usuario) : null;
        if (! $usuario) {
            throw new \InvalidArgumentException('El socio no tiene una cuenta de usuario creada.');
        }

        $usuario->estado = $usuario->estado === '1' ? '0' : '1';
        $usuario->save();

        return ['estado' => $usuario->estado, 'habilitado' => $usuario->estado === '1'];
    }

    /**
     * Lista los socios activos que aún no tienen cuenta de usuario.
     *
     * @return array<int, array{id_socio: int, dni: string, nombre_completo: string}>
     */
    public function listarSociosSinCuenta(): array
    {
        return Socio::whereNull('id_usuario')
            ->where('estado', '1')
            ->get()
            ->map(function (Socio $socio) {
                return [
                    'id_socio' => $socio->id_socio,
                    'dni' => $socio->persona->dni ?? '',
                    'nombre_completo' => $socio->persona->nombre_completo ?? '',
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Genera las cuentas de usuario para los socios seleccionados.
     *
     * Idempotente: si un socio ya obtuvo una cuenta, se omite sin duplicar.
     *
     * @param  array  $socios  [['id_socio' => int, 'nombre_usuario' => string], ...]
     * @return array{total_creadas: int, total_salteadas: int, creadas: array, salteadas: array}
     */
    public function generarCuentasParaSocios(array $socios): array
    {
        return DB::transaction(function () use ($socios) {
            $creadas = [];
            $salteadas = [];

            foreach ($socios as $item) {
                $idSocio = (int) ($item['id_socio'] ?? 0);
                $nombreUsuario = trim((string) ($item['nombre_usuario'] ?? ''));

                $socio = Socio::find($idSocio);

                if (! $socio) {
                    $salteadas[] = ['id_socio' => $idSocio, 'motivo' => 'socio no encontrado'];

                    continue;
                }

                if ($socio->id_usuario) {
                    $salteadas[] = ['id_socio' => $idSocio, 'motivo' => 'ya tiene usuario'];

                    continue;
                }

                if ($nombreUsuario === '') {
                    $salteadas[] = ['id_socio' => $idSocio, 'motivo' => 'nombre de usuario vacío'];

                    continue;
                }

                if (Usuario::where('nombre_usuario', $nombreUsuario)->exists()) {
                    $salteadas[] = ['id_socio' => $idSocio, 'motivo' => 'nombre de usuario ya registrado'];

                    continue;
                }

                $cuenta = $this->crearCuentaSocio($socio, $nombreUsuario);

                $creadas[] = [
                    'id_socio' => $idSocio,
                    'nombre_usuario' => $nombreUsuario,
                    'password_temporal' => $cuenta['password_temporal'],
                ];
            }

            return [
                'total_creadas' => count($creadas),
                'total_salteadas' => count($salteadas),
                'creadas' => $creadas,
                'salteadas' => $salteadas,
            ];
        });
    }
}
