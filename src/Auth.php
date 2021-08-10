<?php
/**
 * Адаптер авторизации.
 * @package evas-php/evas-auth
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Auth;

use Evas\Auth\AuthException;
use Evas\Auth\Interfaces\LoginUserInterface;
use Evas\Auth\Interfaces\OauthInterface;
use Evas\Auth\Models\AuthGrant;
use Evas\Auth\Models\AuthSession;
use Evas\Base\App;
use Evas\Base\Help\Facade;
use Evas\Db\Interfaces\DatabaseInterface;

class Auth extends Facade
{
    /** @var array конфиг */
    protected $config = [];
    protected $db;
    protected $userModel;
    protected $user_id = false;

    /** @static string путь к конфигу по умолчанию */
    const DEFAULT_CONFIG_PATH = __DIR__.'/config.default.php';

    /**
     * Конструктор.
     * @param array|null конфиг или путь к конфигу
     */
    public function __construct($config = null)
    {
        $this->setConfig(static::DEFAULT_CONFIG_PATH);
        if ($config) $this->setConfig($config);
    }

    /**
     * Установка конфига модуля авторизации.
     * @param array|string конфиг или путь к конфигу
     * @return self
     * @throws \InvalidArgumentException
     */
    protected function setConfig($config)
    {
        if (is_string($config)) {
            $config = App::include($filename = $config);
            if (!is_array($config)) {
                throw new \InvalidArgumentException(sprintf(
                    'Auth config "%s" must return array, %s given',
                    $filename, gettype($config)
                ));
            }
        } else if (!is_array($config)) {
            throw new \InvalidArgumentException(sprintf(
                'Argument 1 passed by %s() must be string or array, %s given',
                __METHOD__, gettype($config)
            ));
        }
        $this->config = array_merge_recursive($this->config(), $config);
        return $this;
    }

    /**
     * Установка внешней авторизации.
     * @param string источник
     * @param array|string конфиг
     * @return self
     * @throws \InvalidArgumentException
     */
    protected function setForeign(string $source, $config)
    {
        if (is_string($config)) {
            $config = App::include($filename = $config);
            if (!is_array($config)) {
                throw new \InvalidArgumentException(sprintf(
                    'Auth %s config "%s" must return array, %s given',
                    $source, $filename, gettype($config)
                ));
            }
        } else if (!is_array($config)) {
            throw new \InvalidArgumentException(sprintf(
                'Argument 2 passed by %s() must be string or array, %s given',
                __METHOD__, gettype($config)
            ));
        }
        $this->config()['foreigns'][$source] = $config;
        return $this;
    }

    /**
     * Установка модели пользователя.
     * @param string имя класса пользователя
     * @return self
     * @throws AuthException
     */
    protected function setUserModel(string $className)
    {
        if (!is_subclass_of($className, LoginUserInterface::class)) {
            throw new AuthException(sprintf(
                'User model "%s" must implement the %s',
                $className, LoginUserInterface::class
            ));
        }
        $this->config['userModel'] = $className;
        return $this;
    }


    /**
     * Получение конфига модуля авторизации.
     * @return array конфиг
     */
    protected function config(): array
    {
        return $this->config;
    }

    /**
     * Получение текста или шаблона ошибки из конфига.
     * @param string имя ошибки
     * @return string текст или шаблон ошибки
     */
    protected function getError(string $name): string
    {
        $errors = $this->config()['errors'];
        return $errors[$name] ?? 'Unknown error';
    }

    /**
     * Получение имени таблицы модели.
     * @param string имя модели
     * @return string имя таблицы
     * @throws AuthException
     */
    protected function getModelTableName(string $name): string
    {
        if (!isset($this->config['models_to_tables'][$name])) {
            throw AuthException::build('model_table_not_exists', $name);
        }
        return $this->config['models_to_tables'][$name];
    }

    /**
     * Получение модели пользователя.
     * @return string имя класса пользователя
     * @throws AuthException
     */
    protected function userModel(): string
    {
        $userModel = $this->config['userModel'] ?? null;
        if (!$userModel) {
            throw new AuthException('User model not exists');
        }
        if (!is_subclass_of($userModel, LoginUserInterface::class)) {
            throw new AuthException(sprintf(
                'User model "%s" must implement the %s',
                $userModel, LoginUserInterface::class
            ));
        }
        return $userModel;
    }

    /**
     * Получение доступных способов авторизации.
     * @return array
     */
    protected function supportedSources(): array
    {
        $supported = array_keys($this->config()['foreigns']);
        if ($this->config()['password_enable']) $supported[] = 'password';
        return $supported;
    }

    /**
     * Проверка доступности способа авторизации.
     * @param string способ авторизации
     * @return bool
     */
    protected function isSupportedSource(string $source): bool
    {
        return in_array($source, $this->supportedSources());
    }

    /**
     * Выбрасывание исключения при недоступности способа авторизации.
     * @param string способ авторизации
     * @throws AuthException
     */
    protected function throwIfNotSupportedSource(string $source)
    {
        if (!$this->isSupportedSource($source)) {
            throw AuthException::build('auth_not_supported', $source);
        }
    }



    /**
     * Получение помощника авторизации.
     * @param string источник
     * @return OauthInterface
     */
    protected function getOauth(string $source): OauthInterface
    {
        $this->throwIfNotSupportedSource($source);
        $oauthClass = $this->config['foreignClasses'][$source] ?? null;
        $config = $this->config['foreigns'][$source] ?? null;
        if (!$oauthClass) {
            throw AuthException::build('oauth_handler_not_setted', $source);
        }
        if (!$config) {
            throw AuthException::build('oauth_config_not_setted', $source);
        }
        return new $oauthClass($config);
    }

    /**
     * Получение ссылки внешней авторизации.
     * @param string источник
     * @return string
     * @throws AuthException
     */
    protected function foreignAuthLink(string $source): string
    {
        $oauth = $this->getOauth($source);
        return $oauth->getAuthLink();
    }

    /**
     * Авторизация во внешнем источнике.
     * @param string источник
     * @param array параметры запроса\
     * @return int id пользователя
     * @throws AuthException
     */
    protected function foreignAuth(string $source, array $payload): int
    {
        $oauth = $this->getOauth($source);
        $oauth->resolveLogin($payload);
        $sourceKey = $oauth->getSourceKey();

        $grant = AuthGrant::findForeign($source, $sourceKey);
        if (!$grant) {
            $data = $oauth->getData();
            $user = $this->userModel()::insertByForeign($source, $data);

            $grant = AuthGrant::createForeign($user->id, $source, $sourceKey);
            $grant->save();
        }
        $request = App::request();
        $session = AuthSession::createOrUpdate($grant, $request, $oauth->getAccessToken());
        AuthSession::setCookieToken($session->token);
        return $grant->user_id;
    }

    /**
     * Авторизаци по паролю.
     * @param array параметры запроса
     * @return LoginUserInterface
     * @throws AuthException
     */
    protected function passwordAuth(array $payload = null): LoginUserInterface
    {
        $this->throwIfNotSupportedSource('password');
        
        $data = $this->userModel()::validateLogin($payload);
        $keys = array_fill_keys($this->userModel()::uniqueKeys(), $data['login']);
        $user = $this->userModel()::findByUniqueKeys($keys);

        if (!$user) {
            throw AuthException::build('user_not_found');
        }
        $grant = AuthGrant::findWithPasswordByUserId($user->id);
        if (!$grant->checkPassword($data['password'])) {
            throw AuthException::build('user_fail_password');
        }
        $request = App::request();
        $session = AuthSession::createOrUpdate($grant, $request);
        AuthSession::setCookieToken($session->token);
        return $user;
    }

    /**
     * Регистрация по паролю.
     * @param array параметры запроса
     * @return LoginUserInterface
     * @throws AuthException
     */
    protected function passwordRegistration(array $payload = null): LoginUserInterface
    {
        $this->throwIfNotSupportedSource('password');
        $data = $this->userModel()::validateRegistration($payload);
        $user = $this->userModel()::findByUniqueKeys($data);
        if ($user) foreach ($this->userModel()::uniqueKeys() as &$key) {
            if (isset($data[$key]) && $user->$key === $data[$key]) {
                $label = $this->userModel()::getUniqueKeyLabel($key);
                throw AuthException::build('user_already_exists', $label);
            }
        }
        $user = $this->userModel()::insertByPassword($data);
        AuthGrant::createWithPassword($user->id, $data['password']);
        return $user;
    }

    /**
     * Смена пароля пользователя.
     * @param int id пользователя
     * @param string старый пароль
     * @param string новый пароль
     * @throws AuthException
     */
    protected function changePassword(int $user_id, string $password_old, string $password)
    {
        $grant = AuthGrant::findWithPasswordByUserId($user_id);
        if (!$grant) throw AuthException::build('password_grant_not_found');
        $grant->changePassword($password_old, $password);
    }

    /**
     * Установка пароля пользователя.
     * @param int id пользователя
     * @param string пароль
     * @throws AuthException
     */
    protected function setPassword(int $user_id, string $password)
    {
        AuthGrant::createWithPassword($user_id, $password);
    }

    /**
     * Начало авторизации по отправленному на телефон/email коду.
     * @param array данные запроса
     * @return LoginUserInterface
     */
    protected function codeAuthInit(array $payload)
    {
        $this->throwIfNotSupportedSource('code');
        // $data = $this->userModel()::validateCode($payload);
        $keys = array_fill_keys($this->userModel()::uniqueKeys(), $data['login']);
        $user = $this->userModel()::findByUniqueKeys($keys);
        if (!$user) {
            $user = $this->userModel()::insertByCode($data);
        }
        // $confirm = AuthConfirm::createToEmail()
        return $confirm->code;
    }

    /**
     * Авторизаци по отправленному на телефон/email коду.
     * @param array данные запроса
     * @return LoginUserInterface|null
     */
    protected function codeAuth(array $payload): ?LoginUserInterface
    {
        $this->throwIfNotSupportedSource('code');
        $data = $this->userModel()::validateCode($payload);
        $keys = array_fill_keys($this->userModel()::uniqueKeys(), $data['login']);
        $user = $this->userModel()::findByUniqueKeys($keys);
        if (!$user) {
            throw AuthException::build('user_not_found');
        }
        if (AuthConfirm::completeConfirm($data['type'], $user->id, $data['code'])) {
            return $user;
        }
        return null;
    }

    /**
     * Создание подтвержения email.
     * @param int id пользователя
     * @param string источник получения подтверждения
     */
    protected function createConfirmToEmail(int $user_id, string $to)
    {
        return AuthConfirm::createToEmail($user_id, $to);
    }

    /**
     * Создание подтвержения телефона.
     * @param int id пользователя
     * @param string источник получения подтверждения
     */
    protected function createConfirmToPhone(int $user_id, string $to)
    {
        return AuthConfirm::createToPhone($user_id, $to);
    }

    /**
     * Получение id авторизованного пользователя.
     * @return int|null id пользователя
     */
    protected function loggedUserId(): ?int
    {
        if (false === $this->user_id) {
            $token = $_COOKIE[$this->config['token_cookie_name']] ?? null;
            $this->user_id = $token ? $this->loggedUserIdByToken($token) : null;
        }
        return $this->user_id;
    }

    /**
     * Получение id авторизованного пользователя по токену.
     * @param string токен пользователя
     * @return int|null id пользователя
     */
    protected function loggedUserIdByToken(string $token): ?int
    {
        return AuthSession::findUserIdByToken($token);
    }

    /**
     * Получение соединения с базой данных.
     * @param string|null имя соединения с базой данных
     * @return DatabaseInterface
     * @throws AuthException
     */
    protected function getDb(string $dbname = null): DatabaseInterface
    {
        if (!$this->db) {
            if (!is_callable($this->config['db'] ?? null)) {
                throw AuthException::build('invalid_db');
            }
            $this->db = $this->config['db'];
        }
        $db = $this->db;
        return $db($dbname);
    }




    // public static $foreign = [
    //     'password', 'vk', 'fb', 'goolge', 
    // ];

    // public static $registerSources = [];

    // public static function setConfig(array $config)
    // {
    //     static::$config = $config;
    // }

    // public static function supportedSources(): array
    // {
    //     return ['fake'];
    //     // return static::$foreign;
    // }

    // public static function isSupportedSource($source): bool
    // {
    //     return in_array($source, static::supportedSources());
    // }

    // public static function foreignCallback(callable $loggedCallback)
    // {
    //     static::$foreignCallback = $loggedCallback;
    // }

    // public static function registerForeign(string $name, array $config = null, callable $loggedCallback = null)
    // {
    //     static::$foreign[$name] = new ForeignAuth($config, $loggedCallback);
    // }

    // public static function catchLogged(string $source, array $args = null)
    // {
    //     // 
    // }
}