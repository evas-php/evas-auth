<?php
/**
 * Исключение аутентификации.
 * @package evas-php\evas-auth
 * @author Egor Vayakin <egor@evas-php.com>
 */
namespace Evas\Auth;

use Evas\Auth\Auth;

class AuthException extends \Exception
{
    /**
     * Сборка исключения.
     * @param string имя ошибки
     * @param mixed|null аргументы для подстановки
     * @return static
     */
    public static function build(string $errorName, ...$props)
    {
        $message = Auth::getError($errorName);
        return new static(sprintf($message, ...$props));
    }
}
