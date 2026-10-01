<?php

namespace App\Http\Controllers;

use App\Exports\PDF\SociosPDFExport;
use App\Exports\SociosExport;
use App\Http\Resources\SocioCollection;
use App\Models\Persona;
use App\Models\Puesto;
use App\Models\Socio;
use App\Models\Usuario;
use App\Services\UsuarioService;
use App\Support\ScopeSocio;
use App\Support\Texto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class SocioController extends Controller
{
    use ScopeSocio;

    public function __construct(private UsuarioService $usuarioService) {}

    public function index(Request $request)
    {
        $per_page = 16;
        if (isset($request->per_page)) {
            $per_page = $request->per_page;
        }

        $listado = Socio::with(['Persona', 'Usuario', 'Puestos.Block', 'Puestos.Gironegocio', 'Puestos.Inquilino']);

        if ($request->filled('estado') && $request->estado !== 'todos') {
            $listado->where('socios.estado', $request->estado);
        }

        if ($this->esSocio($request)) {
            $idSocio = $this->idSocioAutenticado($request);
            if ($idSocio !== null) {
                $listado->where('socios.id_socio', $idSocio);
            }
        }

        if (isset($request->nombre_socio)) {
            $texto = strtr(utf8_decode($request->nombre_socio), utf8_decode('àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ'), 'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY');
            $texto = strtr(utf8_decode($texto), utf8_decode('àáâãäçèéêëìíîïññòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ'), 'aaaaaceeeeiiiin?ooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY');
            $texto = str_replace(' ', '%', $texto);
            $listado->whereHas('persona', function ($query) use ($texto) {
                $query->whereRaw('upper(nombre_completo) LIKE upper( ? )', ['%'.$texto.'%']);
            });
        }

        if (isset($request->numero_puesto)) {
            $listado->whereHas('puestos', function ($query) use ($request) {
                $query->whereRaw('upper(numero_puesto) LIKE upper( ? )', ['%'.$request->numero_puesto.'%']);
            });
        }

        $listado->orderByNombreCompleto();

        return new SocioCollection($listado->paginate($per_page));
    }

    public function seleccionarSocio(Request $request)
    {
        $query = Socio::join('personas as c', 'socios.id_socio', 'c.id_persona');

        if ($this->esSocio($request)) {
            $idSocio = $this->idSocioAutenticado($request);
            if ($idSocio !== null) {
                $query->where('socios.id_socio', $idSocio);
            }
        }

        $socios = $query
            ->select('socios.id_socio', 'c.nombre_completo', 'c.dni', 'c.telefono', 'c.correo')
            ->get()
            ->map(function ($socio) {
                $socio->nombre_completo = Texto::capitalizarNombre($socio->nombre_completo);

                return $socio;
            });

        return response()->json(['data' => $socios]);
    }

    public function listarPuestos(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_socio' => 'required',
        ], [
            'id_socio.required' => 'El campo id_socio es obligatorio',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        $puestos = Puesto::where('id_socio', $request->input('id_socio'))
            ->get(['id_puesto', 'numero_puesto']);

        return response()->json(['data' => $puestos]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required',
            'apellido_materno' => 'required',
            'apellido_paterno' => 'required',
            'correo' => 'required',
            'direccion' => 'required',
            'dni' => 'required|string|digits:8',
            'estado' => 'required',
            'fecha_registro' => 'required',
            'sexo' => 'required',
            'telefono' => 'required|string|digits:9',
        ], [
            'nombre.required' => 'El campo nombre es obligatorio',
            'apellido_materno.required' => 'El campo apellido materno es obligatorio',
            'apellido_paterno.required' => 'El campo apellido paterno es obligatorio',
            'correo.required' => 'El campo correo es obligatorio',
            'direccion.required' => 'El campo direccion es obligatorio',
            'dni.required' => 'El campo dni es obligatorio',
            'dni.digits' => 'El campo dni debe tener 8 digitos',
            'estado.required' => 'El campo estado es obligatorio',
            'fecha_registro.required' => 'El campo fecha de registro es obligatorio',
            'sexo.required' => 'El campo sexo es obligatorio',
            'telefono.required' => 'El campo telefono es obligatorio',
            'telefono.digits' => 'El campo telefono debe tener 9 digitos',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        $persona = Persona::where('dni', $request->input('dni'))->first();
        if ($persona) {
            return response()->json(['error' => 'El dni ya esta registrado. '.$persona->nombre_completo], 400);
        }

        $nombre_completo = $request->input('nombre').' '.$request->input('apellido_paterno').' '.$request->input('apellido_materno');

        // Registro transaccional de Persona + Socio + Usuario (rollback ante cualquier fallo)
        $resultado = DB::transaction(function () use ($request, $nombre_completo) {
            // Registro de Persona
            $persona = new Persona;
            $persona->nombre = $request->input('nombre');
            $persona->apellido_paterno = $request->input('apellido_paterno');
            $persona->apellido_materno = $request->input('apellido_materno');
            $persona->dni = $request->input('dni');
            $persona->correo = $request->input('correo');
            $persona->telefono = $request->input('telefono');
            $persona->direccion = $request->input('direccion');
            $persona->sexo = $request->input('sexo');
            $persona->estado = $request->input('estado');
            $persona->fecha_registro = $request->input('fecha_registro');
            $persona->nombre_completo = $nombre_completo;
            $persona->save();

            // Registro de socio (solo ID, fecha y estado - los datos personales vienen de Persona)
            $socio = new Socio;
            $socio->id_socio = $persona->id_persona;
            $socio->fecha_registro = $request->input('fecha_registro');
            $socio->estado = $request->input('estado');
            $socio->save();

            // Creación de la cuenta de acceso del socio
            $cuenta = $this->usuarioService->generarCuentaSocio($socio);

            return ['socio' => $socio, 'cuenta' => $cuenta];
        });

        $socio = $resultado['socio'];
        $cuenta = $resultado['cuenta'];

        // Se asigna el puesto al socio
        if ($request->input('id_puesto') != null) {
            $puesto = Puesto::where('id_puesto', $request->input('id_puesto'))->first();
            $puesto->id_socio = $socio->id_socio;
            $puesto->estado = 2;
            $puesto->update();
        }

        $data = ['data' => $socio, 'message' => 'Socio registrado correctamente'];

        if ($cuenta['creado']) {
            $data['acceso'] = [
                'nombre_usuario' => $cuenta['usuario']->nombre_usuario,
                'password_temporal' => $cuenta['password_temporal'],
            ];
        }

        return response()->json($data);
    }

    public function update(Request $request, $id_socio)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required',
            'apellido_materno' => 'required',
            'apellido_paterno' => 'required',
            'correo' => 'required',
            'direccion' => 'required',
            'dni' => 'required|string|digits:8',
            'estado' => 'required',
            'fecha_registro' => 'required',
            'sexo' => 'required',
            'telefono' => 'required|string|digits:9',
        ], [
            'nombre.required' => 'El campo nombre es obligatorio',
            'apellido_materno.required' => 'El campo apellido materno es obligatorio',
            'apellido_paterno.required' => 'El campo apellido paterno es obligatorio',
            'correo.required' => 'El campo correo es obligatorio',
            'direccion.required' => 'El campo direccion es obligatorio',
            'dni.required' => 'El campo dni es obligatorio',
            'dni.digits' => 'El campo dni debe tener 8 digitos',
            'estado.required' => 'El campo estado es obligatorio',
            'fecha_registro.required' => 'El campo fecha de registro es obligatorio',
            'sexo.required' => 'El campo sexo es obligatorio',
            'telefono.required' => 'El campo telefono es obligatorio',
            'telefono.digits' => 'El campo telefono debe tener 9 digitos',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        $persona = Persona::where('dni', $request->input('dni'))
            ->where('id_persona', '!=', $id_socio)->first();
        if ($persona) {
            return response()->json(['error' => 'El dni ya esta registrado. '.$persona->nombre_completo], 400);
        }

        $nombre_completo = $request->input('nombre').' '.$request->input('apellido_paterno').' '.$request->input('apellido_materno');
        // Actualizar de Persona
        $persona = Persona::find($id_socio);
        $persona->nombre = $request->input('nombre');
        $persona->apellido_paterno = $request->input('apellido_paterno');
        $persona->apellido_materno = $request->input('apellido_materno');
        $persona->dni = $request->input('dni');
        $persona->correo = $request->input('correo');
        $persona->telefono = $request->input('telefono');
        $persona->direccion = $request->input('direccion');
        $persona->sexo = $request->input('sexo');
        $persona->estado = $request->input('estado');
        // $persona->fecha_registro = Carbon::now();
        $persona->fecha_registro = $request->input('fecha_registro');
        $persona->nombre_completo = $nombre_completo;
        $persona->update();

        // Actualizar estado del socio (los datos personales ya están en persona)
        $socio = Socio::where('id_socio', $id_socio)->first();
        $socio->estado = $request->input('estado');
        $socio->fecha_registro = $request->input('fecha_registro');
        $socio->update();

        // Actualizar datos de usuario (si existe)
        $usuario = Usuario::where('id_usuario', $socio->id_usuario)->first();
        if ($usuario) {
            $usuario->nombre_usuario = $request->input('dni');
            $usuario->estado = $request->input('estado');
            $usuario->update();
        }

        return response()->json(['data' => $socio, 'message' => 'Los datos del socio fueron actualizados correctamente']);
    }

    public function destroy($id_socio)
    {
        // Buscamos al socio
        $socio = Socio::find($id_socio);

        // Verificamos si el socio existe
        if (! $socio) {
            return response()->json(['error' => 'El socio no existe.'], 400);
        }

        // Verificamos si el socio tiene un puesto asignado y lo liberamos
        if ($socio->puesto) {
            $puesto = Puesto::where('id_puesto', $socio->puesto->id_puesto)->first();
            $puesto->id_socio = null; // Sin socio
            $puesto->estado = 1; // Disponible
            $puesto->update();
        }

        // Desactivamos al usuario (si existe)
        $usuario = Usuario::where('id_usuario', $socio->id_usuario)->first();
        if ($usuario) {
            $usuario->estado = 0;
            $usuario->update();
        }

        return response()->json(['message' => 'El socio fue eliminado correctamente']);
    }

    public function toggleAcceso(Request $request, $id_socio)
    {
        $socio = Socio::find($id_socio);

        if (! $socio) {
            return response()->json(['error' => 'El socio no existe.'], 400);
        }

        try {
            $resultado = $this->usuarioService->toggleAcceso($socio);

            $mensaje = $resultado['habilitado']
                ? 'El acceso del socio fue habilitado.'
                : 'El acceso del socio fue deshabilitado.';

            return response()->json(['message' => $mensaje, 'data' => $resultado], 200);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => 'Error al procesar la solicitud.'], 400);
        }
    }

    public function regenerarCredenciales(Request $request, $id_socio)
    {
        $socio = Socio::find($id_socio);

        if (! $socio) {
            return response()->json(['error' => 'El socio no existe.'], 400);
        }

        try {
            $resultado = $this->usuarioService->regenerarCredenciales($socio);

            return response()->json([
                'message' => 'Credenciales regeneradas correctamente.',
                'nombre_usuario' => $resultado['usuario']->nombre_usuario,
                'password_temporal' => $resultado['password_temporal'],
            ], 200);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => 'Error al regenerar credenciales.'], 400);
        }
    }

    public function activar(Request $request, $id_socio)
    {
        $socio = Socio::find($id_socio);

        if (! $socio) {
            return response()->json(['error' => 'El socio no existe.'], 400);
        }

        $socio->estado = '1';
        $socio->save();

        if ($socio->persona) {
            $socio->persona->estado = '1';
            $socio->persona->save();
        }

        if ($socio->usuario) {
            $socio->usuario->estado = '1';
            $socio->usuario->save();
        }

        return response()->json([
            'message' => 'El socio fue activado correctamente.',
            'data' => $socio,
        ], 200);
    }

    public function desactivar(Request $request, $id_socio)
    {
        $socio = Socio::find($id_socio);

        if (! $socio) {
            return response()->json(['error' => 'El socio no existe.'], 400);
        }

        $socio->estado = '0';
        $socio->save();

        if ($socio->persona) {
            $socio->persona->estado = '0';
            $socio->persona->save();
        }

        if ($socio->usuario) {
            $socio->usuario->estado = '0';
            $socio->usuario->save();
        }

        return response()->json([
            'message' => 'El socio fue desactivado correctamente.',
            'data' => $socio,
        ], 200);
    }

    public function export()
    {
        return Excel::download(new SociosExport, 'socios.xlsx');
    }

    public function exportPDF()
    {
        $export = new SociosPDFExport;

        return $export->generatePDF();
    }
}
