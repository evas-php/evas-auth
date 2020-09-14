<?php
/**
 * @package evas-php\evas-auth
 */
namespace Evas\Auth\Helpers;

use Evas\Auth\AuthException;
use Evas\Auth\Helpers\ApiConfig;
use Evas\Curl\Curl;

/**
 * Базовый абстрактный класс api.
 * @author Egor Vasyakin <egor@evas-php.com>
 * @since 7 Sep 2020
 */
abstract class BaseApi
{
    /**
     * Обязательно необходимые константы у наследников:
     * CONFIG_PATH
     * API_URL
     */

    /**
     * @static array данные конфига по умолчанию
     */
    const CONFIG_DEFAULT = [];

    /**
     * @static string ошибка код доступа не получен
     */
    const ERROR_OAUTH_CODE_EMPTY = 'Код доступа не получен';

    /**
     * @var ApiConfig
     */
    protected static $config;

    /**
     * Получение доступа к конфигу.
     * @return ApiConfig
     */
    public static function config(): ApiConfig
    {
        if (empty(static::$config)) {
            static::$config = new ApiConfig(static::CONFIG_PATH, static::CONFIG_DEFAULT);
        }
        return static::$config;
    }

    /**
     * Сборка ссылки с параметрами.
     * @param string uri
     * @param array параметры запроса
     * @return string
     */
    public static function buildLink(string $uri, array $params): string
    {
        $i = 0;
        $query = '';
        foreach ($params as $name => $value) {
            $query .= $i > 0 ? '&' : '?';
            $query .= urlencode($name) . '=' . urlencode($value);
            $i++;
        }
        return $uri . $query;
    }

    /**
     * Отправка запроса.
     * @param string uri запроса
     * @param array|null параметры запроса
     * @return object ответ сервера
     */
    public static function exec(string $uri, array $params = null): object
    {
        $uri = static::buildLink($uri, $params);
        try {
            $response = file_get_contents($uri);
        } catch (\Exception $e) {
            throw new AuthException("file_get_contents to \"$uri\" failed to open stream");
        }
        return json_decode($response, false);
    }

    /**
     * Запрос api.
     * @param string|null метод запроса
     * @param array|null параметры запроса
     * @return object
     */
    public static function query(string $method = null, array $params = null): object
    {
        return static::exec(static::API_URL . $method, $params);
    }

    /**
     * Начало сборки curl-запроса.
     * @return Curl
     */
    public static function curl(): Curl
    {
        return new Curl;
    }

    /**
     * Выполнение curl-запроса.
     * @param string метод
     * @param string uri
     * @param string|null тело запроса
     * @param array|null заголовки
     * @return Curl
     */
    public static function execCurl(string $method, string $uri, string $body = null, array $headers = null): Curl
    {
        $curl = static::curl()->withMethod($method)->withUri($uri);
        if (!empty($body)) {
            $curl->withBody($body);
        }
        if (!empty($headers)) {
            $curl->withHeaders($headers);
        }
        return $curl->send();
    }


    /**
     * Получение доступа по пришедшим параметрам запроса.
     * @param array параметры запроса вместе с кодом доступа
     * @throws AuthException
     * @return array
     */
    public static function accessByParams(array $params): array
    {
        extract($params);
        if (empty($code)) throw new AuthException(static::ERROR_OAUTH_CODE_EMPTY);
        return static::access($code);
    }


    // Необходимые к реализации у наследников методы:

    /**
     * Получение oauth ссылки на авторизацию.
     * @return string ссылка для перехода на авторизацию
     */
    abstract public static function getAuthLink(): string;

    /**
     * Запрос oauth на доступ.
     * @param string код доступа
     * @throws AuthException
     * @return array
     */
    abstract public static function access(string $code): array;
}
