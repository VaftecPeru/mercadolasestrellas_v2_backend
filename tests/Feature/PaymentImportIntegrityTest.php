<?php

namespace Tests\Feature;

use App\Http\Controllers\PagoController;
use App\Imports\PagosImport;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

class PaymentImportIntegrityTest extends TestCase
{
    public function test_manual_bank_payment_validates_bank_account_relationship(): void
    {
        $source = $this->methodSource(
            PagoController::class,
            'registrarPagoTransaccional'
        );

        $this->assertStringContainsString('BancoCuenta::where(', $source);
        $this->assertStringContainsString("'id_bancocuenta'", $source);
        $this->assertStringContainsString("->where('id_banco'", $source);
        $this->assertStringContainsString("->where('estado', '1')", $source);
    }

    public function test_both_import_formats_use_the_guarded_payment_writer(): void
    {
        $source = $this->importFileSource();

        $this->assertSame(
            2,
            substr_count($source, '$this->registrarPagoImportado(')
        );
    }

    public function test_imported_payment_locks_debt_checks_owner_and_balance(): void
    {
        $source = $this->importMethodSource('registrarPagoImportado');

        $this->assertStringContainsString('->lockForUpdate()', $source);
        $this->assertStringContainsString(
            '(int) $deuda->id_socio !== $idSocio',
            $source
        );
        $this->assertStringContainsString(
            '$montoPago > $saldoPendiente',
            $source
        );
        $this->assertStringContainsString(
            "Documento::where('id_documento', 1)",
            $source
        );
    }

    public function test_import_bank_defaults_are_validated_before_persisting_operation(): void
    {
        $source = $this->importMethodSource('registrarPagoImportado');

        $this->assertStringContainsString('BancoCuenta::where(', $source);
        $this->assertStringContainsString('self::ID_CUENTA_IMPORTACION', $source);
        $this->assertStringContainsString('self::ID_BANCO_IMPORTACION', $source);
        $this->assertStringContainsString("->where('estado', '1')", $source);
    }

    public function test_import_serializes_rows_by_puesto(): void
    {
        $source = $this->importFileSource();

        $this->assertGreaterThanOrEqual(
            2,
            substr_count($source, '->lockForUpdate()')
        );
        $this->assertStringContainsString(
            "Puesto::where('numero_puesto', $nro_puesto)",
            $source
        );
        $this->assertStringContainsString(
            "Puesto::where('id_puesto', $puestoObj->id_puesto)",
            $source
        );
    }

    private function methodSource(string $class, string $method): string
    {
        $reflection = new ReflectionMethod($class, $method);
        $lines = file($reflection->getFileName());

        return implode('', array_slice(
            $lines,
            $reflection->getStartLine() - 1,
            $reflection->getEndLine() - $reflection->getStartLine() + 1
        ));
    }

    private function importFileSource(): string
    {
        $reflection = new ReflectionClass(PagosImport::class);

        return file_get_contents($reflection->getFileName());
    }

    private function importMethodSource(string $method): string
    {
        // Loading PagosImport.php also defines the internal PagoSheetImport class.
        class_exists(PagosImport::class);

        $reflection = new ReflectionMethod(
            'App\\Imports\\PagoSheetImport',
            $method
        );
        $lines = file($reflection->getFileName());

        return implode('', array_slice(
            $lines,
            $reflection->getStartLine() - 1,
            $reflection->getEndLine() - $reflection->getStartLine() + 1
        ));
    }
}
