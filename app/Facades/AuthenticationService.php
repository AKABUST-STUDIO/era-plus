<?php

namespace App\Facades;

use App\Models\User;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void login(string $email)
 * @method static void register(string $email)
 * @method static void authenticate(string $email, string $code, bool $shouldSendWelcomeMailable = false)
 *
 * @see \App\Services\AuthenticationService
 */
class AuthenticationService extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Services\AuthenticationService::class;
    }
}
