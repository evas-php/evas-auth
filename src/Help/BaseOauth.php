<?php
/**
 * Базовый абстрактный класс внешней аутентификации.
 * @package evas-php\evas-auth
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Auth\Help;

use Evas\Auth\AuthException;
use Evas\Auth\Help\Config;
use Evas\Auth\Interfaces\OauthInterface;
use Evas\Http\CurlRequest;
use Evas\Http\CurlResponse;
use Evas\Http\Uri;

abstract class BaseOauth implements OauthInterface
{
    /**
     * Обязательно необходимые константы у наследников:
     * API_URL
     */

    /** @static array маппинг замены ключей */
    const USER_DATA_KEYS_REPLACES = [];

    /** @var Config */
    protected $config;
    /** @var array данные доступа */
    protected $accessData;
    /** @var array данные пользователя */
    protected $userData;
    /** @var array обработанные подготовленные к записи данные */
    protected $preparedData;

    public function __construct(array $config)
    {
        $default = static::configDefault() ?? [];
        $this->config = array_merge_recursive($default, $config);
    }

    /** 
     * Получение данных конфига по умолчанию.
     * @return array|null
     */
    public function configDefault(): ?array
    {}

    /**
     * Сборка ссылки с параметрами.
     * @param string uri
     * @param array параметры запроса
     * @return string
     */
    public static function buildLink(string $uri, array $params): string
    {
        return strval((new Uri($uri))->withQueryParams($params));
    }

    /**
     * Отправка запроса.
     * @param string uri запроса
     * @param array|null параметры запроса
     * @return array ответ сервера
     */
    public static function exec(string $uri, array $params = null): array
    {
        $uri = static::buildLink($uri, $params);
        try {
            $response = file_get_contents($uri);
        } catch (\Exception $e) {
            throw AuthException::build('oauth_fetch_url_error', $uri);
        }
        return json_decode($response, true);
    }

    /**
     * Отправка curl-запроса.
     * @param string метод
     * @param string uri
     * @param string|null тело запроса
     * @param array|null заголовки
     * @return CurlResponse
     */
    public static function execCurl(string $method, string $uri, string $body = null, array $headers = null): CurlResponse
    {
        $curl = (new CurlRequest)->withMethod($method)->withUri($uri);
        if (!empty($body)) $curl->withBody($body);
        if (!empty($headers)) $curl->withHeaders($headers);
        return $curl->send();
    }

    /**
     * Запрос api.
     * @param string|null метод запроса
     * @param array|null параметры запроса
     * @return array ответ сервера
     */
    public static function queryApi(string $method = null, array $params = null): array
    {
        return static::exec(static::API_URL . $method, $params);
    }


    /**
     * Получение доступа по пришедшим параметрам запроса.
     * @param array параметры запроса вместе с кодом доступа
     * @return array
     * @throws AuthException
     */
    public function fetchAccessByParams(array $params): array
    {
        extract($params);
        if (empty($code)) {
            throw AuthException::build('oauth_code_empty', static::SOURCE_NAME);
        }
        return $this->fetchAccess($code);
    }

    /**
     * Замена ключей данных.
     * @param array данные
     */
    protected static function renameDataKeys(array &$data)
    {
        foreach ($data as $key => $value) {
            if (in_array($key, array_keys(static::USER_DATA_KEYS_REPLACES))) {
                $data[static::USER_DATA_KEYS_REPLACES[$key]] = $value;
                unset($data[$key]);
            }
        }
    }

    /**
     * Выполнение входа.
     * @param array данные запроса
     * @return self
     */
    public function resolveLogin(array $payload)
    {
        $this->accessData = $this->fetchAccessByParams($payload);
        $this->userData = $this->fetchUserDataByAccess($this->accessData);
        return $this;
    }

    /**
     * Получение данных доступа.
     * @return array
     */
    public function getAccessData(): ?array
    {
        return $this->accessData;
    }

    /**
     * Получение запрошенных данных о пользователе.
     * @return array|null
     */
    public function getUserData(): ?array
    {
        return $this->userData;
    }

    /**
     * Получение обработанных данных пользователя.
     * @return array|null
     */
    public function getData(): ?array
    {
        if (!$this->preparedData) {
            $this->preparedData = $this->prepareData();
        }
        return $this->preparedData;
    }

    /**
     * Получение ключа пользователя в источнике.
     * @return string|null
     */
    public function getSourceKey(): ?string
    {
        return $this->userData['id'];
    }

    /**
     * Получение токена oauth.
     * @return string|null
     */
    public function getAccessToken(): ?string
    {
        return $this->accessData['access_token'];
    }

    // Необходимые к реализации у наследников методы:

    /**
     * Получение oauth ссылки на аутентификацию.
     * @return string ссылка для перехода на аутентификацию
     */
    abstract public function getAuthLink(): string;

    /**
     * Запрос oauth на доступ.
     * @param string код доступа
     * @return array данные доступа
     * @throws AuthException
     */
    abstract public function fetchAccess(string $code): array;

    /**
     * Запрос данных пользователя по данным доступа.
     * @param array данные доступа
     * @return array данные пользователя
     */
    abstract public function fetchUserDataByAccess(array $accessData): array;

    /**
     * Запрос данных пользователя по токену доступа.
     * @param string токен текущего пользователя
     * @param string получаемые поля
     * @param string|null id пользователей через запятую
     * @throws AuthException
     * @return array
     */
    // public function fetchUserData(string $access_token, string $fields = null, string $user_ids = null): array;

    /**
     * Подготовка пользовательских данных.
     * @return array
     */
    abstract protected function prepareData(): array;
}
