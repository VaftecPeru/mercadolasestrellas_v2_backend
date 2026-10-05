<?php

namespace Tests\Feature;

use App\Http\Controllers\CuotaController;
use App\Http\Controllers\PagoController;
use ReflectionMethod;
use Tests\TestCase;

class FinancialIntegrityGuardTest extends TestCase
{
    public function test_pago_validation_rejects_duplicate_debt_rows(): void
    {
        $store = $this->methodSource(PagoController::class, 'store');
        $bankStore = $this->methodSource(PagoController::class, 'storePagoPorBanco');

        $this->assertStringContainsString(
            "'deudas.*.id_deuda_cuota' => 'required|integer|distinct'",
            $store
        );
        $this->assertStringContainsString(
            "'deudas.*.id_deuda_cuota' => 'required|integer|distinct'",
            $bankStore
        );
    }

    public function test_pago_registration_locks_rows_and_validates_debt_owner(): void
    {
        $source = $this->methodSource(PagoController::class, 'registrarPagoTransaccional');

        $this->assertStringContainsString("->lockForUpdate()", $source);
        $this->assertStringContainsString(
            '(int) $deuda->id_socio !== $idSocio',
            $source
        );
        $this->assertStringContainsString(
            'DetallePagos::where(\'id_deuda_cuota\', $idDeudaCuota)',
            $source
        );
        $this->assertStringContainsString(
            'DB::transaction(function ()',
            $source
        );
    }

    public function test_individual_metrado_uses_puesto_area(): void
    {
        $source = $this->methodSource(CuotaController::class, 'storePorPuesto');

        $this->assertStringContainsString(
            '$servicio->costo_unitario * $puesto->area',
            $source
        );
        $this->assertStringNotContainsString(
            '$servicio->costo_unitario * $socio->area',
            $source
        );
    }

    public function test_cuota_with_payments_is_rejected_before_transaction_starts(): void
    {
        $source = $this->methodSource(CuotaController::class, 'destroy');

        $validationPosition = strpos($source, '$tienePagos');
        $transactionPosition = strpos($source, 'DB::beginTransaction()');

        $this->assertNotFalse($validationPosition);
        $this->assertNotFalse($transactionPosition);
        $this->assertLessThan($transactionPosition, $validationPosition);
    }

    public function test_multiple_puesto_billing_excludes_unassigned_or_inactive_socios(): void
    {
        $source = $this->methodSource(CuotaController::class, 'storePorMultiplesPuestos');

        $this->assertStringContainsString("->whereNotNull('id_socio')", $source);
        $this->assertStringContainsString("->whereHas('socio'", $source);
        $this->assertStringContainsString("->where('estado', '1')", $source);
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
}
