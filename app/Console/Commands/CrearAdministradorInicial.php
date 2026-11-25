<?php

namespace App\Console\Commands;

use App\Services\UsuarioService;
use Illuminate\Console\Command;

class CrearAdministradorInicial extends Command
{
    /**
     * Nombre y firma del comando de consola para aprovisionar el acceso inicial seguro.
     *
     * @var string
     */
    protected $signature = 'admin:inicial
                            {--usuario= : Nombre de usuario del administrador}
                            {--password= : Contraseña segura (mín. 8 caracteres; si se omite, se genera una temporal aleatoria)}
                            {--dni= : DNI de 8 dígitos del administrador}
                            {--nombre= : Nombres del administrador}
                            {--apellido-paterno= : Apellido paterno del administrador}
                            {--apellido-materno= : Apellido materno del administrador}
                            {--correo= : Correo electrónico del administrador}
                            {--telefono= : Teléfono de 9 dígitos del administrador}
                            {--direccion= : Dirección del administrador}
                            {--sexo=1 : Sexo (1 = Masculino, 2 = Femenino)}
                            {--sin-cambio-obligatorio : No forzar cambio de contraseña en el primer inicio de sesión cuando se provee --password}';

    /**
     * Alias alternativos para invocación desde despliegues.
     *
     * @var array<int, string>
     */
    protected $aliases = ['app:crear-admin'];

    /**
     * Descripción del comando.
     *
     * @var string
     */
    protected $description = 'Crea de forma segura el usuario Administrador inicial cuando el sistema no tiene administradores activos.';

    public function __construct(private UsuarioService $usuarioService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if ($this->usuarioService->existeAdministradorActivo()) {
            $this->warn('Ya existe un administrador activo en el sistema. No se realizaron cambios.');

            return self::FAILURE;
        }

        $interactivo = ! $this->option('no-interaction');

        $usuario = $this->resolverValor('usuario', 'Nombre de usuario del administrador', $interactivo);
        $dni = $this->resolverValor('dni', 'DNI (8 dígitos)', $interactivo);
        $nombre = $this->resolverValor('nombre', 'Nombres', $interactivo);
        $apellidoPaterno = $this->resolverValor('apellido-paterno', 'Apellido paterno', $interactivo);
        $apellidoMaterno = $this->resolverValor('apellido-materno', 'Apellido materno', $interactivo);
        $correo = $this->resolverValor('correo', 'Correo electrónico', $interactivo);
        $telefono = $this->resolverValor('telefono', 'Teléfono (9 dígitos)', $interactivo);
        $direccion = trim((string) ($this->option('direccion') ?: 'Mercado Las Estrellas'));
        $sexo = trim((string) ($this->option('sexo') ?: '1'));

        $password = $this->option('password');
        if (($password === null || trim((string) $password) === '') && $interactivo) {
            $password = $this->secret('Contraseña inicial (deje en blanco para autogenerar una contraseña temporal segura de 12 caracteres)');
        }

        $forzarCambio = ! (bool) $this->option('sin-cambio-obligatorio');

        try {
            $resultado = $this->usuarioService->crearAdministradorInicial([
                'nombre_usuario' => $usuario,
                'dni' => $dni,
                'nombre' => $nombre,
                'apellido_paterno' => $apellidoPaterno,
                'apellido_materno' => $apellidoMaterno,
                'correo' => $correo,
                'telefono' => $telefono,
                'direccion' => $direccion,
                'sexo' => $sexo,
            ], $password !== null ? (string) $password : null, $forzarCambio);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Administrador inicial creado correctamente.');
        $this->line('------------------------------------------------------------');
        $this->line('Usuario             : '.$resultado['usuario']->nombre_usuario);
        $this->line('Contraseña inicial  : '.$resultado['password_inicial']);
        $this->line('Cambio obligatorio  : '.($resultado['usuario']->debe_cambiar_password ? 'Sí (al primer inicio de sesión)' : 'No'));
        $this->line('------------------------------------------------------------');

        return self::SUCCESS;
    }

    private function resolverValor(string $opcion, string $pregunta, bool $interactivo): string
    {
        $valor = $this->option($opcion);

        if (($valor === null || trim((string) $valor) === '') && $interactivo) {
            $valor = $this->ask($pregunta);
        }

        return trim((string) $valor);
    }
}
