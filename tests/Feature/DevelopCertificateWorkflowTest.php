<?php
namespace Tests\Feature;

use App\Models\Caseta;
use App\Models\Certificate;
use App\Services\CertificateHistory;
use App\User;
use Illuminate\Contracts\Console\Kernel;
use Tests\TestCase;

class DevelopCertificateWorkflowTest extends TestCase
{
    use \Tests\Concerns\IsolatedControlDatabase;

    public function createApplication()
    {
        $app = require __DIR__.'/../../bootstrap/app.php';
        $app->useEnvironmentPath(sys_get_temp_dir());
        $app->loadEnvironmentFrom('certtrafico-develop-workflow-no-env');
        $app->make(Kernel::class)->bootstrap();
        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->configureControlDatabase();
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
        $user = new User(); $user->id = 1; $user->name = 'Jefatura ensayo'; $user->roles = 'JEFE_UNIDAD';
        $this->actingAs($user);
    }

    protected function tearDown(): void
    {
        $this->releaseControlDatabase();
        parent::tearDown();
    }

    private function legalPayload(array $replace = []): array
    {
        return array_replace(['type' => 'CASETA', 'mode' => 'NUEVO', 'solicitante' => 'Titular ensayo',
            'ci' => 'DOC-ENSAYO', 'rubro' => 'VENTA', 'legal_proceed' => '1', 'conclusions' => 'Observación legal'], $replace);
    }

    private function createLegal(array $replace = []): Certificate
    {
        $this->post(route('certificate.store'), $this->legalPayload($replace))
            ->assertSessionHasNoErrors()->assertRedirect(route('certificate.index'));
        return Certificate::latest('id')->firstOrFail();
    }

    private function technicalPayload(Certificate $certificate, array $replace = []): array
    {
        return array_replace(['certificate_version' => CertificateHistory::version($certificate),
            'direccion' => 'Dirección ensayo', 'dim_ancho' => 1.2, 'dim_largo' => 2, 'tipo_via' => 'ACERA', 'dim_via' => 4,
            'ref_product' => 'VENTA DE DULCES', 'ref_turn' => 'DIURNO', 'ref_start' => '08:00', 'ref_end' => '16:00',
            'color' => 'AZUL', 'material' => 'METAL', 'technical_proceed' => '1', 'inspection_date' => '30/10/2026',
            'lat' => -19.589, 'lng' => -65.754, 'location_source' => 'MANUAL', 'location_reason' => 'Ubicación comprobada en campo',
            'location_confirmed' => 1, 'conclusions' => 'Observaciones técnicas'], $replace);
    }

    public function test_legal_registration_keeps_technical_stage_pending_and_supports_repeated_owner_documents(): void
    {
        $first = $this->createLegal();
        $second = $this->createLegal();
        $this->assertNull($first->inspection_date);
        $this->assertSame('Observación legal', $first->conclusions);
        $this->assertNotEquals($first->code, $second->code);
        $this->get(route('certificate.legal.edit', $first))->assertOk()->assertSee('certificate_version');
        $this->get(route('certificate.technical', $first))->assertOk()->assertSee('Etapa Técnica')->assertSee('Confirmar este punto')->assertDontSee('maps.googleapis.com');
        $this->get(route('certificate.show', $first))->assertOk()->assertSee(route('certificate.pdf.legal', $first), false);
        $this->assertDatabaseCount('certificate_revisions', 0);
    }

    public function test_technical_completion_synchronizes_caseta_and_preserves_legacy_documents(): void
    {
        $caseta = Caseta::create(['number' => 'DEV-01', 'owner' => 'Titular ensayo', 'document' => 'DOC-ENSAYO',
            'address' => 'Dirección inicial', 'lat' => -19.58, 'lng' => -65.75, 'location_source' => 'MANUAL']);
        $certificate = $this->createLegal(['caseta_id' => $caseta->id]);
        $this->assertEquals(-19.58, $certificate->lat);
        $this->post(route('certificate.technical.store', $certificate), $this->technicalPayload($certificate))
            ->assertSessionHasNoErrors()->assertRedirect(route('certificate.show', $certificate));
        $certificate->refresh(); $caseta->refresh();
        $this->assertSame('2026-10-30', $certificate->inspection_date);
        $this->assertTrue($certificate->technical_proceed);
        $this->assertEquals(4, $certificate->dim_via);
        $this->assertEquals(1.2, $certificate->dim_ancho);
        $this->assertEquals(2, $certificate->dim_largo);
        $this->assertEquals(30, $certificate->dim_perc_ocupado);
        foreach (['direccion' => 'DIRECCIÓN ENSAYO', 'tipo_via' => 'ACERA', 'color' => 'AZUL', 'material' => 'METAL',
            'ref_product' => 'VENTA DE DULCES', 'ref_turn' => 'DIURNO', 'location_source' => 'MANUAL',
            'location_reason' => 'Ubicación comprobada en campo'] as $field => $expected) {
            $this->assertSame($expected, $certificate->$field, $field);
        }
        $this->assertSame('08:00', \Carbon\Carbon::parse($certificate->ref_start)->format('H:i'));
        $this->assertSame('16:00', \Carbon\Carbon::parse($certificate->ref_end)->format('H:i'));
        $this->assertEquals(-19.589, $certificate->lat);
        $this->assertEquals(-65.754, $certificate->lng);
        $this->assertNull($certificate->accuracy);
        $this->assertNotNull($certificate->located_at);
        $this->assertSame('DOC-ENSAYO', $certificate->ci);
        $this->assertTrue($certificate->legal_proceed);
        $this->assertEquals($certificate->lat, $caseta->lat);
        $this->assertEquals($certificate->lng, $caseta->lng);
        $this->get(route('certificate.showInspectionCert', $certificate))->assertOk()->assertSee('PARA - Nombre');
        $this->get(route('certificate.show', $certificate))->assertOk()->assertSee('Actualizar croquis');
    }

    public function test_string_zero_keeps_negative_technical_conclusion_and_rejects_unapproved_legal_stage(): void
    {
        $certificate = $this->createLegal();
        $this->post(route('certificate.technical.store', $certificate), $this->technicalPayload($certificate))->assertSessionHasNoErrors();
        $certificate->refresh();
        $this->post(route('certificate.technical.store', $certificate), $this->technicalPayload($certificate, ['technical_proceed' => '0', 'conclusions' => $certificate->conclusions]))
            ->assertSessionHasNoErrors();
        $certificate->refresh();
        $this->assertFalse($certificate->technical_proceed);
        $this->assertStringContainsString('NO ES VIABLE', $certificate->conclusions);
        $this->assertSame(1, substr_count($certificate->conclusions, 'REALIZADA LA INSPECCIÓN'));
        $this->assertStringContainsString('Observaciones técnicas', $certificate->conclusions);
        $unapproved = $this->createLegal(['legal_proceed' => '0']);
        $this->postJson(route('certificate.technical.store', $unapproved), $this->technicalPayload($unapproved))->assertUnprocessable();
        $this->assertNull($unapproved->fresh()->inspection_date);
    }

    public function test_technical_edits_require_confirmed_location_and_current_version_and_legal_edit_cannot_bypass_stage_lock(): void
    {
        $certificate = $this->createLegal();
        $payload = $this->technicalPayload($certificate);
        $this->post(route('certificate.technical.store', $certificate), array_replace($payload, ['location_confirmed' => 0]))
            ->assertSessionHasErrors('location_confirmed');
        $this->assertNull($certificate->fresh()->inspection_date);
        $this->post(route('certificate.technical.store', $certificate), array_replace($payload, ['location_source' => 'EXISTING', 'lat' => 0, 'lng' => 0]))
            ->assertSessionHasErrors('location_source');
        $this->post(route('certificate.technical.store', $certificate), $payload)->assertSessionHasNoErrors();
        $this->post(route('certificate.technical.store', $certificate), $payload)->assertSessionHasErrors('certificate_version');
        $this->postJson(route('certificate.legal.update', $certificate), $this->legalPayload(['certificate_version' => CertificateHistory::version($certificate->fresh())]))
            ->assertUnprocessable();
    }

    public function test_read_only_profile_cannot_modify_either_stage(): void
    {
        $certificate = $this->createLegal();
        auth()->user()->roles = 'ASESOR_LEGAL';
        $this->get(route('certificate.index'))->assertOk()->assertDontSee(route('certificate.create'), false);
        $this->post(route('certificate.store'), $this->legalPayload())->assertForbidden();
        $this->get(route('certificate.technical', $certificate))->assertForbidden();
        $this->post(route('certificate.technical.store', $certificate), [])->assertForbidden();
        $this->post(route('certificate.legal.update', $certificate), [])->assertForbidden();
    }

    public function test_legal_technical_inspection_and_qr_endpoints_generate_real_pdf_responses(): void
    {
        $certificate = $this->createLegal();
        $this->assertPdf($this->get(route('certificate.pdf.legal', $certificate)));
        $this->post(route('certificate.technical.store', $certificate), $this->technicalPayload($certificate))->assertSessionHasNoErrors();
        foreach (['certificate.pdf', 'certificate.pdf.inspectionCert', 'certificate.qr'] as $route) {
            $this->assertPdf($this->get(route($route, $certificate)));
        }
    }

    private function assertPdf($response): void
    {
        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertStringStartsWith('inline; filename="', $response->headers->get('Content-Disposition'));
        $this->assertStringNotContainsString('\\', $response->headers->get('Content-Disposition'));
    }
}
