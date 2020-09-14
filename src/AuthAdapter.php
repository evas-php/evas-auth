<?php
/**
 * @package evas-php/evas-auth
 */
namespace Evas\Auth;

use Evas\Auth\AuthException;
use Evas\Auth\Models\AuthGrant;
use Evas\Auth\Models\AuthGrantConfirm;
use Evas\Auth\Models\AuthSession;
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
     * @static int коды ошибок
     */
    const ERROR_SOURCE_CONTROLLER_NOT_SUPPORTED = 0;
    const ERROR_SOURCE_CONTROLLER_NOT_FOUND = 1;
    const ERROR_SOURCE_CONTROLLER_ACTION_NOT_FOUND = 2;
    const ERROR_MODEL_TABLE_NOT_FOUND = 3;
    const ERROR_USER_NOT_FOUND = 4;
    const ERROR_USER_FAIL_PASSWORD = 5;
    const ERROR_VALIDATOR = 6;
    const ERROR_USER_ALREADY_EXISTS = 7;
    const ERROR_AUTH_GRANT_CONFIRM_NOT_FOUND = 8;

    /**
     * @static array маппинг ошибок
     */
    const ERRORS_MAP = [
        self::ERROR_SOURCE_CONTROLLER_NOT_SUPPORTED => 'Обработчик авторизации через %s не поддерживается',
        self::ERROR_SOURCE_CONTROLLER_NOT_FOUND => 'Обработчик авторизации через %s не найден',
        self::ERROR_SOURCE_CONTROLLER_ACTION_NOT_FOUND => 'Действие %s обработчика авторизации через %s не найдено',
        self::ERROR_MODEL_TABLE_NOT_FOUND => 'Имя таблицы модели данных %s не найдено',
        self::ERROR_USER_NOT_FOUND => 'Пользователь не найден',
        self::ERROR_USER_FAIL_PASSWORD => 'Неверный пароль',
        self::ERROR_VALIDATOR => 'Неверное имя пользователя/пароль',
        self::ERROR_USER_ALREADY_EXISTS => 'Пользователь уже существует',
        self::ERROR_AUTH_GRANT_CONFIRM_NOT_FOUND => 'Код подтверждения не найден, возможно, вы уже подтвердили вход',
    ];

    /**
     * @static array маппинг статусов грантов авторизации
     */
    const AUTH_GRANT_STATUSES_MAP = [
        AuthGrant::STATUS_NOT_CONFIRMED => 'не подтвержден',
        AuthGrant::STATUS_CONFIRMED => 'подвержден',
        AuthGrant::STATUS_OUTDATED => 'необходимо обновить',
    ];

    /**
     * @static array маппинг таблиц моделей
     */
    const MODELS_TABLES = [
        AuthGrant::class => 'auth_grants',
        AuthSession::class => 'auth_sessions',
        AuthGrantConfirm::class => 'auth_grant_confirms',
    ];


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
     * @throws AuthException
     */
    public static function run(string $source, string $action, array $params = null)
    {
        $controllerClass = static::$sources[$source] ?? null;
        if (empty($controllerClass)) {
            static::throwError(static::ERROR_SOURCE_CONTROLLER_NOT_SUPPORTED, [$source]);
        }
        if (!class_exists($controllerClass, true)) {
            static::throwError(static::ERROR_SOURCE_CONTROLLER_NOT_FOUND, [$source]);
        }
        $controller = new $controllerClass;
        $action .= 'Action';
        if (!method_exists($controller, $action)) {
            static::throwError(static::ERROR_SOURCE_CONTROLLER_ACTION_NOT_FOUND, [$action, $source]);
        }
        call_user_func([$controller, $action], $params);
    }

    /**
     * Выбрасывание исключения с подстановкой текста ошибки.
     * @param int код ошибки
     * @param array|null параметры для подстановки
     * @throws AuthException
     */
    public static function throwError(int $code, array $props = null)
    {
        $error = static::ERRORS_MAP[$code];
        if (!empty($props)) {
            $error = vsprintf($error, $props);
        }
        throw new AuthException($error);
    }

    /**
     * Получение имени таблицы модели данных.
     * @param string имя класса модели
     * @throws AuthException
     * @return string
     */
    public static function getModelTableName(string $className): string
    {
        $tableName = static::MODELS_TABLES[$className] ?? null;
        if (empty($tableName)) {
            static::throwError(static::ERROR_MODEL_TABLE_NOT_FOUND, [$className]);
        }
        return $tableName;
    }
}
