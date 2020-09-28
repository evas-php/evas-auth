<?php
/**
 * @package evas-php\evas-auth
 */
namespace Evas\Auth\Sources\Google;

use Evas\Auth\AuthException;
use Evas\Auth\Helpers\BaseApi;

/**
 * Oauth api авторизации через google.com
 * @link google oauth doc https://developers.google.com/identity/protocols/oauth2/web-server
 * @author Egor Vasyakin <egor@evas-php.com>
 * @since 7 Sep 2020
 */
class GoogleOauth extends BaseApi
{
    /**
     * @static string домен oauth
     */
    const OAUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';

    /**
     * @static string uri получения токена доступа
     */
    const ACCESS_URL = 'https://oauth2.googleapis.com/token';

    /**
     * @static string домен api
     */
    const API_URL = 'https://oauth2.googleapis.com/';

    /**
     * @static string путь к конфигу
     */
    const CONFIG_PATH = 'config/google.php';

    /**
     * @static string ошибка соединения
     */
    const ERROR_RESPONSE = 'Неверный ответ от Google API';

    /**
     * @static array данные конфига по умолчанию
     */
    const CONFIG_DEFAULT = [
        'response_type' => 'code',
        'scope' => '',
        'access_type' => 'offline',
        'include_granted_scopes' => 'true',
        'grant_type' => 'authorization_code',
    ];

    const USER_DATA_KEYS_REPLACES = [
        'picture' => 'pic',
        'given_name' => 'first_name',
        'family_name' => 'last_name',
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
            'response_type' => static::config()->get('response_type'),
            'access_type' => static::config()->get('access_type'),
            'include_granted_scopes' => static::config()->get('include_granted_scopes'),
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
        // POST
        // $data = static::exec(static::ACCESS_URL, [
        //     'client_id' => static::config()->get('client_id'),
        //     'client_secret' => static::config()->get('client_secret'),
        //     'redirect_uri' => static::config()->get('redirect_uri'),
        //     'grant_type' => static::config()->get('grant_type'),
        //     'code' => $code,
        // ]);
        $uri = static::buildLink(static::ACCESS_URL, [
            'client_id' => static::config()->get('client_id'),
            'client_secret' => static::config()->get('client_secret'),
            'redirect_uri' => static::config()->get('redirect_uri'),
            'grant_type' => static::config()->get('grant_type'),
            'code' => $code,
        ]);
        $curl = static::execCurl('POST', $uri);
        if (empty($curl->response)) {
            throw new AuthException('Curl response is empty');
        }
        $decoded = json_decode($curl->response, true);
        if (empty($decoded)) {
            throw new AuthException('Cant\' decoded curl response: ' . $curl->response);
        }
        return $decoded ?? [];
        if (empty($data)) {
            throw new AuthException(static::ERROR_RESPONSE);
        }
        return (array) $data;
        // $data = static::getUserData($data->access_token);
        // $data = array_merge($data, ['access_token' => $data->access_toen]);
        // return [$data->email, $data->access_token];
    }

    /**
     * Получение данные пользователя.
     * @param string токен доступа
     * @return array
     */
    public static function getUserData(string $token, array $accessData): array
    {
        // $uri = static::buildLink(static::API_URL . 'tokeninfo', [
        //     'id_token' => $token,
        // ]);
        // $curl = static::execCurl('POST', $uri);
        // if (empty($curl->response)) {
        //     throw new AuthException('Curl response is empty');
        // }
        // $decoded = json_decode($curl->response, true);
        // if (empty($decoded)) {
        //     throw new AuthException('Cant\' decoded curl response: ' . $curl->response);
        // }
        // return $decoded ?? [];
        // extract($accessData);

        $data = static::query('tokeninfo', [
            'access_token' => $token,
            'id_token' => $accessData['id_token'],
            'token_type' => $accessData['token_type'],
            'expires_in' => $accessData['expires_in'],
        ]);
        if (empty($data)) {
            throw new Exception(static::ERROR_RESPONSE);
        }
        // static::renameDataKeys($data);
        return (array) $data;
        // return
        // // These six fields are included in all Google ID Tokens.
        // "iss": "https://accounts.google.com",
        // "sub": "110169484474386276334",
        // "azp": "1008719970978-hb24n2dstb40o45d4feuo2ukqmcc6381.apps.googleusercontent.com",
        // "aud": "1008719970978-hb24n2dstb40o45d4feuo2ukqmcc6381.apps.googleusercontent.com",
        // "iat": "1433978353",
        // "exp": "1433981953",

        // // These seven fields are only included when the user has granted the "profile" and
        // // "email" OAuth scopes to the application.
        // "email": "testuser@gmail.com",
        // "email_verified": "true",
        // "name" : "Test User",
        // "picture": "https://lh4.googleusercontent.com/-kYgzyAWpZzJ/ABCDEFGHI/AAAJKLMNOP/tIXL9Ir44LE/s99-c/photo.jpg",
        // "given_name": "Test",
        // "family_name": "User",
        // "locale": "en"
    }

    public static function renameDataKeys(array &$data)
    {
        foreach ($data as $key => $value) {
            if (in_array($key, array_keys(static::USER_DATA_KEYS_REPLACES))) {
                $data[static::USER_DATA_KEYS_REPLACES[$key]] = $value;
                unset($data[$key]);
            }
        }
    }

    /**
     * Преобразование данных пользователя в единый формат.
     * @param array данные пользователя без форматирования
     * @return array данные пользователя после форматирования
     */
    public static function userDataFormatting(array $data): array
    {
        if (!empty($data['name'])) {
            list($data['first_name'], $data['last_name']) = explode(' ', $data['name']);
            unset($data['name']);
        }
        if (!empty($data['given_name'])) {
            $data['first_name'] = $data['given_name'];
            unset($data['given_name']);
        }
        if (!empty($data['family_name'])) {
            $data['last_name'] = $data['family_name'];
            unset($data['family_name']);
        }
        return $data;
    }
}
