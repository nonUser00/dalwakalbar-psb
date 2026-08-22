<?php

namespace Tests\Feature;

use App\Models\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login()
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);
        $response = $this->post('/admin/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);
        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticatedAs($user, 'web');
    }

    public function test_admin_can_login_with_remember_me()
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);
        $response = $this->post('/admin/login', [
            'email' => $user->email,
            'password' => 'password',
            'remember' => true,
        ]);
        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticatedAs($user, 'web');
        $response->assertCookie(Auth::guard('web')->getRecallerName());
    }
}
