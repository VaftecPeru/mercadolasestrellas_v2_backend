<?php

namespace App\Http\Controllers;

use App\Http\Resources\UsuarioAdminResource;
use App\Models\Modulo;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\UsuarioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UsuarioController extends Controller
{
    public function __construct(private UsuarioService $usuarioService) {}

    /**
     * Listado de usuarios con búsqueda y filtros (Administrador).
     */
    public function index(Request $request)
    {
        $perPage = $request->per_page ?? 10;

        $query = Usuario::with(['Rol', 'Persona', 'Socio.persona']);

        if ($buscar = $request->input('buscar')) {
            $query->where(function ($q) use ($buscar) {
                $q->where('usuarios.nombre_usuario', 'like', "%{$buscar}%")
                    ->orWhereHas('Persona', function ($q2) use ($buscar) {
                        $q2->where('dni', 'like', "%{$buscar}%");
                    })
                    ->orWhereHas('Socio.persona', function ($q2) use ($buscar) {
                        $q2->where('dni', 'like', "%{$buscar}%");
                    });
            });
        }

        if ($request->filled('id_rol')) {
            $query->where('usuarios.id_rol', $request->input('id_rol'));
        }

        if ($estado = $request->input('estado')) {
            switch ($estado) {
                case 'activo':
                    $query->where('usuarios.estado', '1')
                        ->where('usuarios.bloqueado', 0)
                        ->where('usuarios.debe_cambiar_password', 0);

                    break;
                case 'inactivo':
                    $query->where('usuarios.estado', '0');

                    break;
                case 'bloqueado':
                    $query->where('usuarios.bloqueado', 1);

                    break;
                case 'pendiente':
                    $query->where('usuarios.estado', '1')
                        ->where('usuarios.debe_cambiar_password', 1);

                    break;
            }
        }

        return UsuarioAdminResource::collection(
            $query->orderBy('usuarios.id_usuario', 'asc')->paginate($perPage)
        );
    }

    /**
     * Crea un usuario (solo Administrador/Cajero; Socio se crea desde Módulo Socios)
     * registrando sus datos personales en `personas`.
     */
    public function store(Request $request)
    {
        $validator = $this->validarUsuario($request);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        try {
            $resultado = $this->usuarioService->crearUsuario($request->all());
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        return response()->json([
            'message' => 'Usuario creado correctamente.',
            'usuario' => new UsuarioAdminResource($resultado['usuario']->load(['Rol', 'Persona', 'Socio.persona'])),
            'password_temporal' => $resultado['password_temporal'],
        ], 200);
    }

    /**
     * Edita los datos personales (personas) y de cuenta (usuarios) de un usuario.
     */
    public function update(Request $request, $id_usuario)
    {
        $usuario = Usuario::find($id_usuario);

        if (! $usuario) {
            return response()->json(['error' => 'El usuario no existe.'], 400);
        }

        $validator = $this->validarUsuario($request);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        $idRolNuevo = (int) $request->input('id_rol');
        $idRolActual = (int) $usuario->id_rol;

        if ($idRolNuevo === UsuarioService::ID_ROL_SOCIO && $idRolActual !== UsuarioService::ID_ROL_SOCIO) {
            return response()->json(['error' => 'Los usuarios con rol Socio se crean desde el Módulo Socios.'], 400);
        }

        if ($idRolActual === UsuarioService::ID_ROL_ADMINISTRADOR
            && $idRolNuevo !== UsuarioService::ID_ROL_ADMINISTRADOR
            && $this->esUltimoAdministradorActivo($usuario)) {
            return response()->json(['error' => 'No se puede cambiar el rol del último administrador activo.'], 400);
        }

        if ($request->input('estado') === '0' && $usuario->estado !== '0') {
            if ($this->esUsuarioActual($request, $usuario)) {
                return response()->json(['error' => 'No puede desactivar su propia cuenta.'], 400);
            }

            if ($this->esUltimoAdministradorActivo($usuario)) {
                return response()->json(['error' => 'No puede desactivar al último administrador activo.'], 400);
            }
        }

        if ($idRolActual === UsuarioService::ID_ROL_SOCIO && $idRolNuevo !== UsuarioService::ID_ROL_SOCIO) {
            $socio = $usuario->Socio;
            if ($socio) {
                $socio->id_usuario = null;
                $socio->save();
            }
        }

        try {
            $usuario = $this->usuarioService->actualizarUsuario($usuario, $request->all());
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        return response()->json([
            'message' => 'Usuario actualizado correctamente.',
            'usuario' => new UsuarioAdminResource($usuario->load(['Rol', 'Persona', 'Socio.persona'])),
        ], 200);
    }

    public function activar(Request $request, $id_usuario)
    {
        $usuario = $this->buscarUsuario($id_usuario);
        if (! $usuario) {
            return response()->json(['error' => 'El usuario no existe.'], 400);
        }

        $usuario->estado = '1';
        $usuario->save();

        return $this->respuestaEstado($usuario, 'Usuario activado.');
    }

    public function desactivar(Request $request, $id_usuario)
    {
        $usuario = $this->buscarUsuario($id_usuario);
        if (! $usuario) {
            return response()->json(['error' => 'El usuario no existe.'], 400);
        }

        if ($this->esUsuarioActual($request, $usuario)) {
            return response()->json(['error' => 'No puede desactivar su propia cuenta.'], 400);
        }

        if ($this->esUltimoAdministradorActivo($usuario)) {
            return response()->json(['error' => 'No puede desactivar al último administrador activo.'], 400);
        }

        $usuario->estado = '0';
        $usuario->save();

        return $this->respuestaEstado($usuario, 'Usuario desactivado.');
    }

    public function bloquear(Request $request, $id_usuario)
    {
        $usuario = $this->buscarUsuario($id_usuario);
        if (! $usuario) {
            return response()->json(['error' => 'El usuario no existe.'], 400);
        }

        if ($this->esUsuarioActual($request, $usuario)) {
            return response()->json(['error' => 'No puede bloquear su propia cuenta.'], 400);
        }

        if ($this->esUltimoAdministradorActivo($usuario)) {
            return response()->json(['error' => 'No puede bloquear al último administrador activo.'], 400);
        }

        $usuario->bloqueado = true;
        $usuario->save();

        return $this->respuestaEstado($usuario, 'Usuario bloqueado.');
    }

    public function desbloquear(Request $request, $id_usuario)
    {
        $usuario = $this->buscarUsuario($id_usuario);
        if (! $usuario) {
            return response()->json(['error' => 'El usuario no existe.'], 400);
        }

        $usuario->bloqueado = false;
        $usuario->save();

        return $this->respuestaEstado($usuario, 'Usuario desbloqueado.');
    }

    public function generarPasswordTemporal(Request $request, $id_usuario)
    {
        $usuario = $this->buscarUsuario($id_usuario);
        if (! $usuario) {
            return response()->json(['error' => 'El usuario no existe.'], 400);
        }

        $passwordTemporal = $this->usuarioService->generarPasswordTemporal($usuario);

        return response()->json([
            'message' => 'Contraseña temporal generada correctamente.',
            'nombre_usuario' => $usuario->nombre_usuario,
            'password_temporal' => $passwordTemporal,
        ], 200);
    }


    public function actualizarTelefono(Request $request, $id_usuario)
    {
        $usuario = $this->buscarUsuario($id_usuario);
        if (! $usuario) {
            return response()->json(['error' => 'El usuario no existe.'], 400);
        }

        $validator = Validator::make($request->all(), [
            'telefono' => 'required|string|digits:9',
        ], [
            'telefono.required' => 'El campo teléfono es obligatorio.',
            'telefono.digits' => 'El campo teléfono debe tener 9 dígitos.',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        try {
            $persona = $this->usuarioService->actualizarTelefono($usuario, $request->input('telefono'));
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        return response()->json([
            'message' => 'Teléfono actualizado correctamente.',
            'telefono' => $persona->telefono,
        ], 200);
    }

    /**
     * Estadísticas para el módulo Usuarios y Roles.
     */
    public function estadisticas()
    {
        return response()->json([
            'socios_sin_cuenta' => $this->usuarioService->contarSociosSinCuenta(),
        ], 200);
    }

    /**
     * Lista los socios activos que aún no tienen cuenta de usuario.
     */
    public function listarSociosSinCuenta()
    {
        return response()->json($this->usuarioService->listarSociosSinCuenta(), 200);
    }

    /**
     * Genera las cuentas de acceso para los socios seleccionados.
     */
    public function generarCuentasSocios(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'socios' => 'required|array|min:1',
        ], [
            'socios.required' => 'Debe seleccionar al menos un socio.',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        return response()->json(
            $this->usuarioService->generarCuentasParaSocios($request->input('socios')),
            200
        );
    }

    /**
     * Lista de roles con sus módulos asignados.
     */
    public function indexRol()
    {
        $roles = Rol::orderBy('id_rol')->get()->map(function ($rol) {
            return [
                'id_rol' => $rol->id_rol,
                'nombre' => $rol->nombre,
                'codigo' => $rol->codigo,
                'es_administrador' => $rol->es_administrador,
                'modulos' => DB::table('rol_modulo')->where('id_rol', $rol->id_rol)->pluck('id_modulo')->all(),
            ];
        });

        return response()->json($roles);
    }

    /**
     * Lista de todos los módulos (para la configuración de permisos).
     */
    public function indexModulo()
    {
        return response()->json(Modulo::orderBy('orden')->orderBy('id_modulo')->get());
    }

    /**
     * Módulos asignados a un rol.
     */
    public function modulosRol($id_rol)
    {
        $rol = Rol::find($id_rol);

        if (! $rol) {
            return response()->json(['error' => 'El rol no existe.'], 400);
        }

        $modulos = DB::table('rol_modulo')->where('id_rol', $id_rol)->pluck('id_modulo')->all();

        return response()->json(['id_rol' => (int) $id_rol, 'modulos' => $modulos]);
    }

    /**
     * Actualiza los módulos asignados a un rol.
     */
    public function actualizarModulosRol(Request $request, $id_rol)
    {
        $rol = Rol::find($id_rol);

        if (! $rol) {
            return response()->json(['error' => 'El rol no existe.'], 400);
        }

        if ((int) $id_rol === UsuarioService::ID_ROL_ADMINISTRADOR) {
            return response()->json(['error' => 'No se pueden modificar los permisos del rol Administrador.'], 400);
        }

        $validator = Validator::make($request->all(), [
            'id_modulos' => 'required|array',
        ], [
            'id_modulos.required' => 'Debe enviar la lista de módulos.',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        $idModulos = array_values(array_unique(array_map('intval', $request->input('id_modulos'))));

        DB::transaction(function () use ($id_rol, $idModulos) {
            DB::table('rol_modulo')->where('id_rol', $id_rol)->delete();

            foreach ($idModulos as $idModulo) {
                DB::table('rol_modulo')->insert(['id_rol' => $id_rol, 'id_modulo' => $idModulo]);
            }
        });

        return response()->json(['message' => 'Permisos actualizados correctamente.'], 200);
    }

    private function buscarUsuario($id_usuario): ?Usuario
    {
        return Usuario::find($id_usuario);
    }

    private function respuestaEstado(Usuario $usuario, string $mensaje)
    {
        return response()->json([
            'message' => $mensaje,
            'usuario' => new UsuarioAdminResource($usuario->load(['Rol', 'Persona', 'Socio.persona'])),
        ], 200);
    }

    private function esUsuarioActual(Request $request, Usuario $usuario): bool
    {
        $token = $request->bearerToken() ?? $request->input('token');

        if (! $token) {
            return false;
        }

        $actual = Usuario::where('token', $token)->first();

        return $actual && (int) $actual->id_usuario === (int) $usuario->id_usuario;
    }

    private function esUltimoAdministradorActivo(Usuario $usuario): bool
    {
        if ((int) $usuario->id_rol !== UsuarioService::ID_ROL_ADMINISTRADOR) {
            return false;
        }

        $otrosActivos = Usuario::where('id_rol', UsuarioService::ID_ROL_ADMINISTRADOR)
            ->where('estado', '1')
            ->where('bloqueado', 0)
            ->where('id_usuario', '!=', $usuario->id_usuario)
            ->count();

        return $otrosActivos === 0;
    }

    /**
     * Validador común para crear/editar usuarios (evita duplicar reglas).
     */
    private function validarUsuario(Request $request)
    {
        return Validator::make($request->all(), [
            'nombre_usuario' => 'required|string',
            'id_rol' => 'required|integer|in:1,2,3',
            'nombre' => 'required',
            'apellido_paterno' => 'required',
            'apellido_materno' => 'required',
            'dni' => 'required|string|digits:8',
            'correo' => 'required',
            'telefono' => 'required|string|digits:9',
            'direccion' => 'required',
            'sexo' => 'required',
            'estado' => 'required',
            'fecha_registro' => 'required',
        ], [
            'nombre_usuario.required' => 'El nombre de usuario es obligatorio.',
            'id_rol.required' => 'El rol es obligatorio.',
            'id_rol.in' => 'El rol seleccionado no es válido.',
            'nombre.required' => 'El campo nombre es obligatorio.',
            'apellido_paterno.required' => 'El campo apellido paterno es obligatorio.',
            'apellido_materno.required' => 'El campo apellido materno es obligatorio.',
            'dni.required' => 'El campo DNI es obligatorio.',
            'dni.digits' => 'El campo DNI debe tener 8 dígitos.',
            'correo.required' => 'El campo correo es obligatorio.',
            'telefono.required' => 'El campo teléfono es obligatorio.',
            'telefono.digits' => 'El campo teléfono debe tener 9 dígitos.',
            'direccion.required' => 'El campo dirección es obligatorio.',
            'sexo.required' => 'El campo sexo es obligatorio.',
            'estado.required' => 'El campo estado es obligatorio.',
            'fecha_registro.required' => 'El campo fecha de registro es obligatorio.',
        ]);
    }
}
