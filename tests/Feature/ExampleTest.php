<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_returns_a_successful_response(): void
    {
        User::factory()->create([
            'role' => 'TRADER',
            'company' => 'Apex Global',
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('VECTIS');
        $response->assertSee('TERMINAL');
    }
}
