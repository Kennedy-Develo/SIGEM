<?php

namespace Tests\Feature\Manifestation;

use Tests\TestCase;

class TrashManifestationTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
