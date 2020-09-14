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
     * @static array маппинг статусов грантов авторизации
     */
    const AUTH_GRANT_STATUSES_MAP = [
        0 => 'не подтвержден',
        1 => 'подвержден',
        2 => 'устарел, необходимо обновить',
    ];

    /**
     * @static array маппинг ошибок
     */
    const ERRORS_MAP = [
        0 => 'Пользователь не найден',
        1 => 'Неверный пароль',
        2 => 'Неверное имя пользователя/пароль',
        3 => 'Пользователь уже существует',
        4 => 'Код подтверждения не найден, возможно, вы уже подтвердили вход',
    ];

    /**
     * @static int коды ошибок
     */
    const ERROR_USER_NOT_FOUND = 0;
    const ERROR_USER_FAIL_PASSWORD = 1;
    const ERROR_VALIDATOR = 2;
    const ERROR_USER_ALREADY_EXISTS = 3;
    const ERROR_AUTH_GRANT_CONFIRM_NOT_FOUND = 4;

    /**
     * @static string имя токена авторизации в cookie
     */
    const AUTH_TOKEN_COOKIE_NAME = 'token';

    /**
     * @static int время жизни токена авторизации в секундах
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
