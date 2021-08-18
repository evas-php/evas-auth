<?php
namespace Evas\Auth\tests\help;

use Evas\Auth\Interfaces\LoginUserInterface;
use Evas\Auth\Models\LoginUserTrait;
use Evas\Auth\tests\help\User;

class LoginUser extends User implements LoginUserInterface
{
    use LoginUserTrait;
    
    public static function validateLogin(array $payload = null): array
    {
        return $payload;
    }

    public static function validateRegistration(array $payload = null): array
    {
        return $payload;
    }
}
