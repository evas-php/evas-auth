<?php
/**
 * @package evas-php\evas-auth
 */
namespace Evas\Auth\Sources\Fb;

use Evas\Auth\AuthException;
use Evas\Auth\Helpers\BaseApi;

/**
 * Oauth api авторизации через facebook.com
 * @link facebook oauth doc https://developers.facebook.com/docs/facebook-login
 * @author Egor Vasyakin <egor@evas-php.com>
 * @since 7 Sep 2020
 */
class FbOauth extends BaseApi
{
    /**
     * @static string домен oauth
     */
    const OAUTH_URL = 'https://www.facebook.com/v3.2/dialog/oauth';

    /**
     * @static string uri получения токена доступа
     */
    const ACCESS_URL = 'https://graph.facebook.com/v3.2/oauth/access_token';

    /**
     * @static string домен api
     */
    const API_URL = 'https://graph.facebook.com/';

    /**
     * @static string путь к конфигу
     */
    const CONFIG_PATH = 'config/fb.php';

    /**
     * @static string ошибка соединения
     */
    const ERROR_RESPONSE = 'Неверный ответ от Facebook API';

    /**
     * @static array маппинг расшифровок ошибок
     */
    const API_REQUEST_ERRORS_MAP = [
        4 => 'Первышен общий часовой лимит обращений к Facebook API',
        17 => 'Превышен ваш часовой лимит обращений к Facebook API',
        613 => 'Превышен лимит обращений к методу Facebook API',
         // HTTP X-App-Usage
    ];

    /**
     * @static array маппинг гендерных данных
     */
    const GENDERS_MAP = [
        'female' => 1,
        'male' => 2
    ];

    /**
     * Получение oauth ссылки на авторизацию.
     * @return string ссылка для перехода на авторизацию
     */
    public static function getAuthLink(): string
    {
        return static::buildLink(static::OAUTH_URL, [
            'client_id' => static::config()->get('client_id'),
            'redirect_uri' => static::config()->get('redirect_uri'),
            'scope' => static::config()->get('scope'),
            'state' => '{'. uniqid() .'='. uniqid() .'}',
        ]);
    }

    /**
     * Запрос oauth на доступ.
     * @param string код доступа
     * @throws Exception
     * @return array
     */
    public static function access(string $code): array
    {
        $data = static::exec(static::ACCESS_URL, [
            'client_id' => static::config()->get('client_id'),
            'client_secret' => static::config()->get('client_secret'),
            'redirect_uri' => static::config()->get('redirect_uri'),
            'code' => $code,
        ]);
        if (empty($data)) {
            throw new AuthException(static::ERROR_RESPONSE);
        }
        return (array) $data;
        // return [$data->user_id, $data->access_token];
    }

    /**
     * Проверка на ошибку ответа api.
     * @param object|null ответ api
     * @throws AuthException
     */
    public static function checkResponse(object $data = null)
    {
        if (empty($data) || empty($data->response)) {
            $error = null;
            if (!@empty($data->error)) $error = $data->error;
            if (!@empty($data->response->error)) $error = $data->response->error;
            if (!empty($error)) $error = ': ' . json_encode($error);
            throw new AuthException(static::ERROR_RESPONSE . $error);
        }
    }

    /**
     * Получение данных пользователя.
     * @param string токен текущего пользователя
     * @param string получаемые поля
     * @param string|null id пользователей через запятую
     * @throws AuthException
     * @return object
     */
    public static function getUserData(string $access_token, string $fields = null, string $user_ids = null): object
    {
        $queryData = compact('access_token');
        if (empty($fields)) {
            $fields = static::config()->get('user_data_fields');
        }
        if (!empty($fields)) {
            $queryData['fields'] = $fields;
        }
        // if (!empty($user_ids)) {
        //     $user_ids = substr($user_ids, 0, strpos($user_ids, ','));
        //     $queryData['user_ids'] = $user_ids;
        // }
        $data = static::query('me', $queryData);
        // static::checkResponse($data);
        return $data;
        // if (!empty($user_ids)) return $data->response;
        // return $data->response[0];
    }

    /**
     * Преобразование данных пользователя в единый формат.
     * @param array данные пользователя без форматирования
     * @return array данные пользователя после форматирования
     */
    public static function userDataFormatting(array $data): array
    {
        if (!empty($data['picture'])) {
            $data['picture'] = $data['picture']['data']['url'];
        }
        if (!empty($data['birthday'])) {
            list($m, $d, $y) = explode('/', $data['birthday']);
            if ($d < 10) $d = "0$d";
            if ($m < 10) $m = "0$m";
            $data['birthdate'] = "$d.$m.$y";
            unset($data['birthday']);
        }
        if (!empty($data['gender'])) {
            $data['gender'] = static::GENDERS_MAP[$data['gender']] ?? null;
        }
        if (!empty($data['location'])) {
            list($city, $region) = explode(',', $data['location']);
            $data['city'] = $city;
            $data['region'] = $region;
        }
        return $data;
    }
}
