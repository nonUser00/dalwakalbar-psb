<?php

use App\Enums\PendaftarStatus;
use App\Models\Auth\User;
use App\Models\Pendaftar\Pendaftar;
use Spatie\Permission\Models\Permission;

test('admin can set candidate to re-interview from pengumuman', function () {
    $permission = Permission::firstOrCreate(['name' => 'ujian.hasil.finalize', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->givePermissionTo($permission);

    $pendaftar = Pendaftar::factory()->create([
        'status' => PendaftarStatus::TidakLulus,
        'is_interview_ulang' => false,
        'interview_ulang_at' => null,
    ]);

    $response = $this->actingAs($user, 'web')
        ->post("/admin/pendaftar/pengunguman/{$pendaftar->id}/reinterview");

    $response->assertSessionHas('success');

    $pendaftar->refresh();
    expect($pendaftar->status)->toBe(PendaftarStatus::Tagihan)
        ->and($pendaftar->is_interview_ulang)->toBeTrue()
        ->and($pendaftar->interview_ulang_at)->not->toBeNull();
});
