<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'usuario' => 'required',
            'password' => 'required',
        ], [
            'usuario.required' => 'El usuario es requerido.',
            'password.required' => 'La contraseña es requerida.',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        try {
            $usuario = Usuario::where('nombre_usuario', $request->input('usuario'))->first();

            if (! $usuario || ! password_verify($request->input('password'), $usuario->contrasenia)) {
                return response()->json(['message' => 'contraseña incorrecta, vuelve a intentarlo'], 400);
            }

            if ($usuario->bloqueado) {
                return response()->json(['message' => 'Acceso bloqueado'], 403);
            }

            if ($usuario->estado !== '1') {
                return response()->json(['message' => 'Acceso desactivado'], 403);
            }

            $usuario->token = $this->apiToken();
            $usuario->ultimo_acceso = now();
            $usuario->save();

            return response()->json([
                'token' => $usuario->token,
                'message' => 'Se logueo correctamente.',
                'usuario' => $this->usuarioPayload($usuario),
            ], 200);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'message' => 'Error de conexión con la base de datos. Verifique su archivo .env',
                'debug' => $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Ocurrió un error inesperado en el servidor.',
                'debug' => $e->getMessage(),
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'usuario' => 'required',
        ], [
            'usuario.required' => 'El usuario es requerido.',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        $usuario = Usuario::where('nombre_usuario', $request->input('usuario'))->first();

        if (! $usuario) {
            return response()->json(['message' => 'Ocurrio un error al cerrar sesión.'], 400);
        }

        $usuario->token = null;
        $usuario->save();

        return response()->json(['message' => 'Salio del sistema correctamente.'], 200);
    }

    public function validaciones(Request $request)
    {
        $token = $request->bearerToken() ?? $request->input('token');

        if (! $token) {
            return response()->json(['error' => 'El token es requerido.'], 400);
        }

        $usuario = Usuario::select('id_usuario', 'nombre_usuario', 'estado', 'id_rol', 'debe_cambiar_password')
            ->where('token', $token)->first();

        if (! $usuario) {
            return response()->json(['message' => 'Token inválido o expirado. No se pudo validar el acceso.'], 401);
        }

        return response()->json($this->usuarioPayload($usuario), 200);
    }

    public function cambiarPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password_actual' => 'required',
            'password_nueva' => 'required|min:6',
        ], [
            'password_actual.required' => 'La contraseña actual es requerida.',
            'password_nueva.required' => 'La nueva contraseña es requerida.',
            'password_nueva.min' => 'La nueva contraseña debe tener al menos 6 caracteres.',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        $token = $request->bearerToken() ?? $request->input('token');

        if (! $token) {
            return response()->json(['error' => 'El token es requerido.'], 400);
        }

        $usuario = Usuario::where('token', $token)->first();

        if (! $usuario) {
            return response()->json(['message' => 'Token inválido o expirado.'], 401);
        }

        if (! password_verify($request->input('password_actual'), $usuario->contrasenia)) {
            return response()->json(['message' => 'La contraseña actual es incorrecta.'], 400);
        }

        $usuario->contrasenia = Hash::make($request->input('password_nueva'));
        $usuario->debe_cambiar_password = false;
        $usuario->save();

        return response()->json([
            'message' => 'Contraseña actualizada correctamente.',
            'usuario' => $this->usuarioPayload($usuario),
        ], 200);
    }

    private function usuarioPayload(Usuario $usuario): array
    {
        return [
            'id_usuario' => $usuario->id_usuario,
            'nombre_usuario' => $usuario->nombre_usuario,
            'id_rol' => $usuario->id_rol,
            'estado' => $usuario->estado,
            'debe_cambiar_password' => $usuario->debe_cambiar_password,
        ];
    }

    private function apiToken()
    {
        $str_random = Str::random(60);
        $apiToken = uniqid(base64_encode($str_random));

        return $apiToken;
    }
}
