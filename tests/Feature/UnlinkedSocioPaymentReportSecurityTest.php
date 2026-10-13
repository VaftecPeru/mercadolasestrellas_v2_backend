<?php

namespace Tests\Feature;

use App\Http\Controllers\ReporteController;
use ReflectionMethod;
use Tests\TestCase;

class UnlinkedSocioPaymentReportSecurityTest extends TestCase
{
    /**
     * A Socio account without a linked Socio record must not obtain
     * unscoped payment data or exported documents.
     */
    public function test_payment_report_endpoints_fail_closed_for_unlinked_socio(): void
    {
        foreach (['pagos', 'exportReportePagos', 'exportReportePagosPDF'] as $method) {
            $source = $this->methodSource($method);

            $guard = '$this->esSocio($request) && $this->idSocioAutenticado($request) === null';
            $this->assertStringContainsString($guard, $source, $method);
            $this->assertStringContainsString('403', $source, $method);
            $this->assertLessThan(
                strpos($source, '$this->aplicarScopeSocio($request)'),
                strpos($source, $guard),
                $method.' must reject access before applying report filters'
            );
        }
    }

    private function methodSource(string $method): string
    {
        $reflection = new ReflectionMethod(ReporteController::class, $method);
        $lines = file($reflection->getFileName());

        return implode('', array_slice(
            $lines,
            $reflection->getStartLine() - 1,
            $reflection->getEndLine() - $reflection->getStartLine() + 1
        ));
    }
}
