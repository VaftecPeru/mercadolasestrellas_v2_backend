<?php

namespace Tests\Feature;

use App\Services\UsuarioService;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DatabaseBaseAndInitialAdminSecurityTest extends TestCase
{
    private function baseSqlContent(): string
    {
        $path = base_path('database/sql/mercado_bd_base.sql');
        $this->assertFileExists($path);

        return (string) file_get_contents($path);
    }

    public function test_base_sql_removes_default_admin_credentials_and_inherited_personal_data(): void
    {
        $sql = $this->baseSqlContent();

        $this->assertStringNotContainsString('INSERT INTO `usuarios`', $sql);
        $this->assertStringNotContainsString('INSERT INTO `personas`', $sql);
        $this->assertStringNotContainsString('$2y$12$HNJvX5UnK8a7mUvZxKs3zeo0leHVp2fg3.iTNMWSUbPmsvFdxb.T.', $sql);
        $this->assertStringNotContainsString('hectorvasquez@vaftecperu.com', $sql);
        $this->assertStringNotContainsString('0000-00-00 00:00:00', $sql);
    }

    public function test_base_sql_resets_counters_and_removes_legacy_records(): void
    {
        $sql = $this->baseSqlContent();

        // El correlativo de documentos debe iniciar en 0 (no en 47)
        $this->assertStringContainsString(
            "INSERT INTO `documentos` (`id_documento`, `numero_documento`, `serie`, `estado`, `fecha_registro`) VALUES\n(1,0,'0000','1'",
            str_replace("\r\n", "\n", $sql)
        );

        // No deben existir contadores AUTO_INCREMENT heredados en tablas operativas vacías
        foreach ([973, 974, 339, 677, 667, 285, 288, 307, 1328] as $contadorHeredado) {
            $this->assertStringNotContainsString(
                "AUTO_INCREMENT={$contadorHeredado}",
                $sql,
                "Se encontró el contador heredado AUTO_INCREMENT={$contadorHeredado} en mercado_bd_base.sql"
            );
        }

        // No deben existir saltos de línea corruptos (\r\n literales) en cuentas bancarias ni servicios
        $this->assertStringNotContainsString('\r\n', $sql);

        // La tabla usuarios debe tener la estructura vigente (AUTO_INCREMENT, id_persona, debe_cambiar_password, bloqueado)
        $this->assertStringContainsString('`id_usuario` bigint(20) unsigned NOT NULL AUTO_INCREMENT', $sql);
        $this->assertStringContainsString('`id_persona` bigint(20) unsigned DEFAULT NULL', $sql);
        $this->assertStringContainsString('`debe_cambiar_password` tinyint(1) NOT NULL DEFAULT 1', $sql);
        $this->assertStringContainsString('`bloqueado` tinyint(1) NOT NULL DEFAULT 0', $sql);

        // Debe incluir los 3 roles del sistema y el módulo 13 (Usuarios y Roles)
        $this->assertStringContainsString("(1,'Administrador','ADM','1')", $sql);
        $this->assertStringContainsString("(2,'Socio','SOC','0')", $sql);
        $this->assertStringContainsString("(3,'Cajero','CAJ','0')", $sql);
        $this->assertStringContainsString("(13,'Usuarios y Roles','/home/usuarios'", $sql);
    }

    public function test_usuario_service_rejects_default_or_weak_passwords(): void
    {
        $service = app(UsuarioService::class);

        foreach (['password', 'PASSWORD', 'admin', 'admin123', '12345678', 'corto', 'mi_admin', '12345678'] as $insegura) {
            try {
                $service->validarContraseniaSegura($insegura, 'mi_admin', '12345678');
                $this->fail("Se esperaba que la contraseña insegura '{$insegura}' fuera rechazada.");
            } catch (\InvalidArgumentException $e) {
                $this->assertNotEmpty($e->getMessage());
            }
        }

        // Una contraseña robusta y distinta del usuario/DNI debe ser aceptada
        $service->validarContraseniaSegura('M3rcad0$Segur0!2026', 'mi_admin', '12345678');
        $this->assertTrue(true);
    }

    public function test_initial_admin_console_command_is_registered(): void
    {
        $commands = Artisan::all();

        $this->assertArrayHasKey('admin:inicial', $commands);
        $this->assertArrayHasKey('app:crear-admin', $commands);

        $commandSource = (string) file_get_contents(app_path('Console/Commands/CrearAdministradorInicial.php'));
        $this->assertStringNotContainsString('ADMIN_INITIAL_', $commandSource);
    }
}
