<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * La raíz no tiene landing pública: un invitado es redirigido al login.
     */
    public function test_root_redirects_guests_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }

    /**
     * El registro público está deshabilitado.
     */
    public function test_register_route_does_not_exist(): void
    {
        $this->get('/register')->assertNotFound();
    }
}
