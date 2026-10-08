<?php

namespace Tests;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Browser tests do not submit a real session token; keep CSRF out of
        // the way while leaving auth, roles, policies, and validation active.
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }
}
