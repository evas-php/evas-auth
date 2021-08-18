<?php
/**
 * Тест аутентификаций.
 * @package evas-php\evas-auth
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Auth\tests;

use Codeception\Util\Autoload;
// use Evas\Base\App;
use Evas\Auth\Auth;
use Evas\Auth\Models\AuthConfirm;
use Evas\Auth\Models\AuthGrant;
use Evas\Auth\tests\help\AuthTestUnit;
use Evas\Auth\tests\help\LoginUser;

Autoload::addNamespace('Evas\\Auth', 'vendor/evas-php/evas-auth/src');
Autoload::addNamespace('Evas\\Auth\\tests', 'vendor/evas-php/evas-auth/tests');

class AuthTest extends AuthTestUnit
{
    public function testSupported()
    {
        codecept_debug(Auth::supportedSources());
        $this->assertEquals(['password', 'code'], Auth::supportedSources());
    }

    /**
     * Тест аутентификации по паролю.
     */
    public function testAuthByPassword()
    {
        // registration
        $payload = [
            'email' => 'egor@evas-php.com',
            'first_name' => 'Egor',
            'last_name' => 'Vasyakin',
            'password' => '123456',
            'password_repeat' => '123456',
        ];
        $regUser = Auth::registrationByPassword($payload);
        $this->assertTrue($regUser instanceof LoginUser);
        // login
        $payload = [
            'login' => 'egor@evas-php.com',
            'password' => '123456',
        ];
        $loginUser = Auth::loginByPassword($payload);
        $this->assertTrue($loginUser instanceof LoginUser);
        $this->assertEquals($regUser, $loginUser);
    }

    /**
     * Тест подтверждения email/телефона.
     */
    public function testConfirm()
    {
        // get code
        $payload = [
            'to' => 'egor@evas-php.com',
        ];
        $code = Auth::getConfirmCode($payload);
        // get code for resend
        $resendCode = Auth::getCodeForResend($payload);
        $this->assertEquals($code, $resendCode);
        // confirm check
        $payload['code'] = $code;
        $confirm = Auth::confirmCheck($payload);
        $this->assertTrue($confirm instanceof AuthConfirm);
        $this->assertTrue($confirm->isCompleted());
        $this->assertEquals($payload['to'], $confirm->to);
        $this->assertEquals(AuthConfirm::TYPE_EMAIL, $confirm->type);
    }

    /**
     * Тест аутентификации по коду.
     */
    public function testAuthByCode()
    {
        // get code
        $payload = [
            'to' => 'egor@evas-php.com',
        ];
        $code = Auth::getCodeForLogin($payload);
        // get code for resend
        $resendCode = Auth::getCodeForResend($payload);
        $this->assertEquals($code, $resendCode);
        // login
        $payload['code'] = $code;
        $loginUser = Auth::loginByCode($payload);
        $this->assertTrue($loginUser instanceof LoginUser);
        $this->assertEquals($payload['to'], $loginUser->email);
    }

    /**
     * Тест восстановления пароля.
     */
    public function testRecovery()
    {
        // get recovery code
        $login = 'egor@evas-php.com';
        $password = 'newpass';
        $payload = [
            'to' => $login,
        ];
        $code = Auth::getRecoveryCode($payload);
        // get recovery code for resend
        $resendCode = Auth::getRecoveryCodeForResend($payload);
        $this->assertEquals($code, $resendCode);
        // check recovery
        $payload['code'] = $code;
        $payload['password'] = $payload['password_repeat'] = $password;
        $grant = Auth::recoveryCheck($payload);
        $this->assertTrue($grant instanceof AuthGrant);

        // check new password
        $payload = compact('login', 'password');
        $loginUser = Auth::loginByPassword($payload);
        $this->assertTrue($loginUser instanceof LoginUser);
        $this->assertEquals($grant->user_id, $loginUser->id);
    }
}
