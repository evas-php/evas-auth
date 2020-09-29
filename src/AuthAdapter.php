<?php
/**
 * @package evas-php/evas-auth
 */
namespace Evas\Auth;

use Evas\Auth\AuthException;
use Evas\Auth\Helpers\Config;
use Evas\Base\App;

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
    const ERROR_AUTH_GRANT_NOT_FOUND = 8;
    const ERROR_AUTH_GRANT_CONFIRM_NOT_FOUND = 9;

    // /**
    //  * @static array маппинг ошибок
    //  */
    // const ERRORS_MAP = [
    //     self::ERROR_SOURCE_CONTROLLER_NOT_SUPPORTED => 'Обработчик авторизации через %s не поддерживается',
    //     self::ERROR_SOURCE_CONTROLLER_NOT_FOUND => 'Обработчик авторизации через %s не найден',
    //     self::ERROR_SOURCE_CONTROLLER_ACTION_NOT_FOUND => 'Действие %s обработчика авторизации через %s не найдено',
    //     self::ERROR_MODEL_TABLE_NOT_FOUND => 'Имя таблицы модели данных %s не найдено',
    //     self::ERROR_USER_NOT_FOUND => 'Пользователь не найден',
    //     self::ERROR_USER_FAIL_PASSWORD => 'Неверный пароль',
    //     self::ERROR_VALIDATOR => 'Неверное имя пользователя/пароль',
    //     self::ERROR_USER_ALREADY_EXISTS => 'Пользователь уже существует',
    //     self::ERROR_AUTH_GRANT_CONFIRM_NOT_FOUND => 'Код подтверждения не найден, возможно, вы уже подтвердили вход',
    // ];

    /**
     * @var Config
     */
    protected static $config;

    /**
     * @var Database
     */
    protected static $db;

    /**
     * Путь к дефолтному конфигу адаптера.
     */
    const DEFAULT_CONFIG_PATH = __DIR__ . '/config.default.php';

    /**
     * Путь к кастомному конфигу адаптера.
     */
    public static $customConfigPath;

    /**
     * Получение конфига адаптера.
     * @return Config
     */
    public static function config(): Config
    {
        if (empty(static::$config)) {
            $defaultConfig = include static::DEFAULT_CONFIG_PATH;
            static::$config = new Config(static::$customConfigPath, $defaultConfig);
        }
        return static::$config;
    }

    /**
     * Получение элемента по индексу из списка в конфиге.
     * @param string имя списка
     * @param string|int индекс элемента списка
     * @return mixed
     */
    public static function getConfigListItem(string $listName, $itemIndex)
    {
        $list = static::config()->get($listName) ?? [];
        return $list[$itemIndex] ?? null;
    }


    // /**
    //  * @static array маппинг статусов грантов авторизации
    //  */
    // const AUTH_GRANT_STATUSES_MAP = [
    //     AuthGrant::STATUS_NOT_CONFIRMED => 'не подтвержден',
    //     AuthGrant::STATUS_CONFIRMED => 'подвержден',
    //     AuthGrant::STATUS_OUTDATED => 'необходимо обновить',
    // ];

    // /**
    //  * @static array маппинг таблиц моделей
    //  */
    // const MODELS_TABLES = [
    //     AuthGrant::class => 'auth_grants',
    //     AuthSession::class => 'auth_sessions',
    //     AuthGrantConfirm::class => 'auth_grant_confirms',
    // ];


    // /**
    //  * @static string имя токена авторизации в cookie
    //  */
    // const AUTH_TOKEN_COOKIE_NAME = 'token';

    // /**
    //  * @static int время жизни токена авторизации в секундах
    //  */
    // const AUTH_TOKEN_ALIVE = 2592000;

    // /**
    //  * @var array маппинг источников авторизации.
    //  */
    // protected static $sources = [
    //     'vk' => VkController::class,
    //     'fb' => FbController::class,
    //     'google' => GoogleController::class,
    //     'email' => EmailController::class,
    //     'phone' => PhoneController::class,
    // ];

    /**
     * Установка соединения с базой данных.
     * @param Database
     */
    public static function setDb(Database &$db)
    {
        static::$db = &$db;
    }

    /**
     * Получение соединения с базой данных.
     * @return Database
     */
    public static function getDb(): Database
    {
        return static::$db;
        // return App::getDb();
    }

    /**
     * Запуск обработчика.
     * @param string источник
     * @param string метод
     * @param array|null параметры
     * @throws AuthException
     */
    public static function run(string $source, string $action, array $params = null)
    {
        // $controllerClass = static::$sources[$source] ?? null;
        // $sources = static::config()->get('sources') ?? [];
        // $controllerClass = $sources[$source] ?? null;
        $controllerClass = static::getConfigListItem('sources', $source);
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
        // $error = static::ERRORS_MAP[$code];
        // $errorsMap = static::config()->get('errors_map') ?? [];
        // $message = $errorsMap[$code] ?? '';
        $error = static::getConfigListItem('errors_map', $code);
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
        // $tableName = static::MODELS_TABLES[$className] ?? null;
        // $modelsTables = static::config()->get('models_tables') ?? [];
        // $tableName = $modelsTables[$className] ?? null;
        $tableName = static::getConfigListItem('models_tables', $className);
        if (empty($tableName)) {
            static::throwError(static::ERROR_MODEL_TABLE_NOT_FOUND, [$className]);
        }
        return $tableName;
    }
}
