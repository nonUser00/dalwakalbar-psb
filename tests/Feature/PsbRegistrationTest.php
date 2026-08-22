<?php

namespace Tests\Feature;

use App\Models\Pendaftar\Pendaftar;
use App\Models\Setting\NumberingSequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PsbRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        NumberingSequence::create([
            'id' => Str::uuid(),
            'name' => 'nomor_pendaftaran',
            'prefix' => 'PSB',
            'pattern' => '{PREFIX}-{YYYY}-{AUTONUMBER}',
            'padding' => 4,
            'next_number' => 1,
        ]);
    }

    public function test_can_view_registration_page(): void
    {
        $response = $this->get('/psb/register');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Psb/Auth/Register')
        );
    }

    public function test_registrant_can_register_and_is_redirected_to_success_page(): void
    {
        $response = $this->post('/psb/register', [
            'nik' => '6171012345678901',
            'nama' => 'Muhammad Fatih',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertRedirect('/psb/register/success');

        $this->assertDatabaseHas('pendaftars', [
            'nik' => '6171012345678901',
            'nama' => 'Muhammad Fatih',
            'status' => 'DRAFT',
            'tipe_pendaftaran' => 'Reguler',
        ]);

        $pendaftar = Pendaftar::where('nik', '6171012345678901')->first();
        $this->assertNotNull($pendaftar);
        $this->assertStringStartsWith('PSB-', $pendaftar->nomor_pendaftaran);
        $this->assertAuthenticatedAs($pendaftar, 'pendaftar');
    }

    public function test_registration_success_page_displays_data_and_registration_number(): void
    {
        $pendaftar = Pendaftar::create([
            'id' => Str::uuid(),
            'nomor_pendaftaran' => 'PSB-2026-0001',
            'nik' => '6171098765432109',
            'nama' => 'Abdullah Salim',
            'password' => bcrypt('password'),
            'status' => 'DRAFT',
            'tipe_pendaftaran' => 'Reguler',
        ]);

        $response = $this->actingAs($pendaftar, 'pendaftar')->get('/psb/register/success');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Psb/Auth/RegisterSuccess')
            ->has('pendaftar', fn (Assert $data) => $data
                ->where('nomor_pendaftaran', 'PSB-2026-0001')
                ->where('nik', '6171098765432109')
                ->where('nama', 'Abdullah Salim')
                ->etc()
            )
        );
    }

    public function test_unauthenticated_user_cannot_view_registration_success_page(): void
    {
        $response = $this->get('/psb/register/success');

        $response->assertRedirect('/psb/login');
    }
}
