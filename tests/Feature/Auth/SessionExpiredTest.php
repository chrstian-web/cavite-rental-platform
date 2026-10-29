<?php

namespace Tests\Feature\Auth;

use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SessionExpiredTest extends TestCase
{
    public function test_an_expired_csrf_token_redirects_to_login_with_a_friendly_message(): void
    {
        Route::middleware('web')->get('/_test-expired-token', function () {
            throw new TokenMismatchException('CSRF token mismatch.');
        });

        $response = $this->get('/_test-expired-token');

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('session_expired');
    }

    public function test_api_requests_keep_the_json_error_for_expired_tokens(): void
    {
        Route::middleware('web')->get('/_test-expired-token-json', function () {
            throw new TokenMismatchException('CSRF token mismatch.');
        });

        $this->getJson('/_test-expired-token-json')->assertStatus(419);
    }
}
