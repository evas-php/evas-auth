<?php
/**
 * Адаптер аутентификации.
 * @package evas-php/evas-auth
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Auth;

use Evas\Auth\AuthException;
// use Evas\Auth\Actions\CodeAuth;
// use Evas\Auth\Actions\ConfirmAndRecovery;
// use Evas\Auth\Actions\ForeignAuth;
// use Evas\Auth\Actions\PasswordAuth;
use Evas\Auth\Interfaces\LoginUserInterface;
use Evas\Auth\Interfaces\OauthInterface;
use Evas\Auth\Models\AuthGrant;
use Evas\Auth\Models\AuthSession;
use Evas\Auth\Traits\AuthCodeTrait;
use Evas\Auth\Traits\AuthConfirmTrait;
use Evas\Auth\Traits\AuthForeignTrait;
use Evas\Auth\Traits\AuthPasswordTrait;
use Evas\Base\App;
use Evas\Base\Help\Facade;
use Evas\Db\Interfaces\DatabaseInterface;
use Evas\Http\Interfaces\RequestInterface;

class Auth extends Facade
{
    use AuthCodeTrait, AuthConfirmTrait, AuthForeignTrait, AuthPasswordTrait;

    /** @var array конфиг */
    protected $config = [];
    protected $db;
    protected $user_id = false;
    protected $userModel;
    protected $supportedForeign;
    protected $supported;

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
     * Установка конфига модуля аутентификации.
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
        $this->supportedForeign = null;
        $this->supported = null;
        $this->userModel = null;
        return $this;
    }

    /**
     * Установка внешней аутентификации.
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
        $this->config['foreigns'][$source] = $config;
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
        $this->userModel = null;
        return $this;
    }


    /**
     * Получение конфига модуля аутентификации.
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
        if (!$this->userModel) {
            $this->userModel = $this->config['userModel'] ?? null;
            if (!$this->userModel) {
                throw new AuthException('User model not exists');
            }
            if (!is_subclass_of($this->userModel, LoginUserInterface::class)) {
                throw new AuthException(sprintf(
                    'User model "%s" must implement the %s',
                    $this->userModel, LoginUserInterface::class
                ));
            }
        }
        return $this->userModel;
    }

    /**
     * Получение доступных внешних способов аутентификации.
     * @return array
     */
    protected function supportedForeignSources(): array
    {
        if (!$this->supportedForeign) {
            $this->supportedForeign = array_keys($this->config['foreigns']);
        }
        return $this->supportedForeign;
    }

    /**
     * Получение доступных способов аутентификации.
     * @return array
     */
    protected function supportedSources(): array
    {
        if (!$this->supported) {
            $this->supported = $this->supportedForeignSources();
            if ($this->config['password_enable']) $this->supported[] = 'password';
            if ($this->config['code_enable']) $this->supported[] = 'code';
        }
        return $this->supported;
    }

    /**
     * Проверка доступности способа аутентификации.
     * @param string способ аутентификации
     * @return bool
     */
    protected function isSupportedSource(string $source): bool
    {
        return in_array($source, $this->supportedSources());
    }

    /**
     * Выбрасывание исключения при недоступности способа аутентификации.
     * @param string способ аутентификации
     * @throws AuthException
     */
    protected function throwIfNotSupportedSource(string $source)
    {
        if (!$this->isSupportedSource($source)) {
            throw AuthException::build('auth_not_supported', $source);
        }
    }



    /**
     * Получение помощника внешней аутентификации.
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
     * Получение объекта запроса.
     * @return RequestInterface
     */
    protected function getRequest(): RequestInterface
    {
        return App::request();
    }

    /**
     * Установка cookie с токеном.
     * @param string токен
     * @param int|null дельта времени жизни cookie
     */
    protected function setCookieToken(string $token, int $alive = null)
    {
        if (!$alive) $alive = $this->config['token_alive'];
        $name = $this->config['token_cookie_name'];
        $path = '/';
        $host = App::uri()->getHost();
        setcookie($name, $token, time() + $alive, $path, $host, false, true);
    }

    /**
     * Создание или обновление сессии аутентификации.
     * @param AuthGrant грант аутентификации
     * @param string|null токен гранта аутентификации
     * @return AuthSession
     */
    protected function makeSession(AuthGrant &$grant, string $grant_token = null): AuthSession
    {
        $session = AuthSession::make($grant, $grant_token);
        $this->setCookieToken($session->token);
        return $session;
    }

    /**
     * Получение id авторизованного пользователя.
     * @return int|null id пользователя
     */
    protected function loggedUserId(): ?int
    {
        if (false === $this->user_id) {
            $token = $_COOKIE[$this->config['token_cookie_name']] ?? null;
            $this->user_id = $token ? AuthSession::findUserIdByToken($token) : null;
        }
        return $this->user_id;
    }

    /**
     * Выход авторизованного пользователя.
     */
    protected function logout()
    {
        $token = $_COOKIE[$this->config['token_cookie_name']] ?? null;
        if ($token) {
            $session = AuthSession::findActualByToken($token);
            if ($session) {
                $session->destroy();
                $this->setCookieToken($session->token, -100);
            }
        }
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
}
