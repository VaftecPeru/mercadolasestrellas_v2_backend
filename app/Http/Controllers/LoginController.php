<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

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

          
            if (!$usuario || !password_verify($request->input('password'), $usuario->contrasenia)){
                return response()->json(['message' => 'Nombre de usuario y/o contraseña incorrectos.'], 400);
            }

            $usuario->token = $this->apiToken();
            $usuario->save();

            return response()->json([
                "token" => $usuario->token,
                "message" => 'Se logueo correctamente.',
                "usuario" => [
                    "id_usuario" => $usuario->id_usuario,
                    "nombre_usuario" => $usuario->nombre_usuario,
                    "rol" => $usuario->rol,
                    "estado" => $usuario->estado,
                    "id_rol" => $usuario->id_rol
                ]
            ], 200);

        } catch (\Illuminate\Database\QueryException $e) {
           
            return response()->json([
                'message' => 'Error de conexión con la base de datos. Verifique su archivo .env',
                'debug' => $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
          
            return response()->json([
                'message' => 'Ocurrió un error inesperado en el servidor.',
                'debug' => $e->getMessage()
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

        if (!$usuario){
            return response()->json(['message' => 'Ocurrio un error al cerrar sesión.'], 400);
        }

       
        $usuario = Usuario::find($usuario->id_usuario);
        $usuario->token = null;
        $usuario->save();

        return response()->json(['message' => 'Salio del sistema correctamente.'], 200);
    }

    public function validaciones(Request $request)
    {
     
        $token = $request->bearerToken() ?? $request->input('token');

        if (!$token) {
            return response()->json(['error' => 'El token es requerido.'], 400);
        }

     
        $usuario = Usuario::select("id_usuario", "nombre_usuario", "rol", "estado", "id_rol")
            ->where('token', $token)->first();

   
        if (!$usuario){
            return response()->json(['message' => 'Token inválido o expirado. No se pudo validar el acceso.'], 401);
        }

        return response()->json($usuario, 200);
    }

    private function apiToken() {
        $str_random = Str::random(60);
        $apiToken = uniqid(base64_encode($str_random));
        return $apiToken;
    }
}
