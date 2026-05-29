<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_home_page_redirects_guests_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }

    public function test_the_login_page_is_available(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
    }

    public function test_removed_legacy_admin_routes_are_not_available(): void
    {
        $this->get('/admin/contracts')->assertNotFound();
        $this->get('/admin/agents')->assertNotFound();
    }

    public function test_register_requires_an_office(): void
    {
        $this->post('/register', [
            'name' => 'Funcionario Nuevo',
            'email_prefix' => 'funcionario.nuevo',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('office_id');
    }

    public function test_register_requires_institutional_email_format(): void
    {
        $this->post('/register', [
            'name' => 'Funcionario Nuevo',
            'email_prefix' => 'funcionario',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('email_prefix');
    }
}
