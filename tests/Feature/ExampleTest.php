<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * La raíz no tiene página propia: lleva al login del panel.
     */
    public function test_the_root_redirects_to_the_panel_login(): void
    {
        $this->get('/')->assertRedirect(route('filament.admin.auth.login'));
    }
}
