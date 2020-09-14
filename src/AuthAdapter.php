<?php
/**
 * @package evas-php/evas-auth
 */
namespace Evas\Auth;

use Evas\Auth\AuthException;
use Evas\Auth\Sources\Vk\VkController;
use Evas\Auth\Sources\Fb\FbController;
use Evas\Auth\Sources\Google\GoogleController;

/**
 * Контроллер авторизации.
 * @author Egor Vasyakin <egor@evas-php.com>
 * @since 3 Sep 2020
 */
class AuthAdapter
{
    /**
     * @static string ошибка
     */
    const ERROR_USER_NOT_FOUND = 'Пользователь не найден';
    const ERROR_USER_FAIL_PASSWORD = 'Неверный пароль';
    const ERROR_VALIDATOR = 'Неверное имя пользователя/пароль';
    const ERROR_USER_ALREADY_EXISTS = 'Пользователь уже существует';

    /**
     * @static string имя токена авторизации в cookie
     */
    const AUTH_TOKEN_COOKIE_NAME = 'token';

    /**
     * @static int время жизни токена авторизации в секундах.
     */
    const AUTH_TOKEN_ALIVE = 2592000;

    /**
     * @var array маппинг источников авторизации.
     */
    protected static $sources = [
        'vk' => VkController::class,
        'fb' => FbController::class,
        'google' => GoogleController::class,
        'email' => EmailController::class,
        'phone' => PhoneController::class,
    ];

    /**
     * Запуск обработчика.
     * @param string источник
     * @param string метод
     * @param array|null параметры
     */
    public static function run(string $source, string $action, array $params = null)
    {
        $controllerClass = static::$sources[$source] ?? null;
        if (empty($controllerClass)) {
            throw new AuthException("Обработчик авторизации через $source не поддерживается");
        }
        if (!class_exists($controllerClass, true)) {
            throw new AuthException("Обработчик авторизации через $source не найден");
        }
        $controller = new $controllerClass;
        $action .= 'Action';
        if (!method_exists($controller, $action)) {
            throw new AuthException("Действие $action обработчика авторизации через $source не найдено");
        }
        call_user_func([$controller, $action], $params);
    }
}
