<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LocalSqliteMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_migrations_allow_a_local_certificate_with_a_generated_uuid(): void
    {
        $certificate = new Certificate();
        $certificate->forceFill([
            'tipo' => 'CASETA', 'modalidad' => 'NUEVO', 'solicitante' => 'PRUEBA LOCAL',
            'ci' => 'LOCAL-UUID', 'rubro' => 'PRUEBA', 'direccion' => 'DIRECCION DE PRUEBA',
            'dim_ancho' => 1, 'dim_largo' => 1, 'color' => 'BLANCO', 'material' => 'METAL',
            'tipo_via' => 'ACERA', 'dim_via' => 2, 'technical_proceed' => true,
            'legal_proceed' => true, 'ref_product' => 'VENTA DE DULCES',
            'ref_turn' => 'DIURNO', 'ref_start' => '08:00', 'ref_end' => '18:00',
            'lat' => -19.58, 'lng' => -65.75, 'conclusions' => 'PRUEBA',
        ])->save();

        $code = $certificate->fresh()->code;
        $this->assertTrue(Str::isUuid($code));
        $this->assertSame('4', $code[14]);
    }

    public function test_login_redirects_to_the_existing_admin_panel(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'username' => $user->username,
            'password' => 'password',
        ])->assertRedirect('/admin');

        $this->get('/admin')->assertOk();
    }
}
