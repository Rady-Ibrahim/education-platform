<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            BranchSeeder::class,
            RolePermissionSeeder::class,
        ]);
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response
            ->assertOk()
            ->assertSeeVolt('pages.auth.register');
    }

    public function test_new_users_can_register_as_pending_student(): void
    {
        $component = Volt::test('pages.auth.register')
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('role', 'student')
            ->set('password', 'password')
            ->set('password_confirmation', 'password');

        $component->call('register');

        $component
            ->assertHasNoErrors()
            ->assertRedirect(route('account.pending', absolute: false));

        $this->assertAuthenticated();

        $user = User::query()->where('email', 'test@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame(UserStatus::PendingAdmin, $user->status);
        $this->assertTrue($user->hasRole(UserRole::Student));
    }
}
