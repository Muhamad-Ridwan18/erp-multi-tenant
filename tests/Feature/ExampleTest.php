<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_root_redirects_guests_to_login(): void
    {
        $this->get('/')
            ->assertRedirect(route('login'));
    }

    public function test_login_page_is_ok(): void
    {
        $this->get(route('login'))
            ->assertOk();
    }
}
