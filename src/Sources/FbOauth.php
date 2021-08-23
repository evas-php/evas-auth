<?php
/**
 * Oauth api аутентификации через facebook.com
 * @package evas-php\evas-auth
 * @author Egor Vasyakin <egor@evas-php.com>
 * @link facebook oauth doc https://developers.facebook.com/docs/facebook-login
 */
namespace Evas\Auth\Sources;

use Evas\Auth\AuthException;
use Evas\Auth\Help\BaseOauth;

class FbOauth extends BaseOauth
{
    /** @static string имя источника аутентификации */
    const SOURCE = 'fb';
    /** @static string полное имя источника аутентификации */
    const SOURCE_NAME = 'facebook.com';

    /** @static string домен oauth */
    const OAUTH_URL = 'https://www.facebook.com/v3.2/dialog/oauth';
    /** @static string uri получения токена доступа */
    const ACCESS_URL = 'https://graph.facebook.com/v3.2/oauth/access_token';
    /** @static string домен api */
    const API_URL = 'https://graph.facebook.com/';

    /** @static array маппинг замены ключей пользовательских данных */
    const USER_DATA_KEYS_REPLACES = [
        'id' => 'fb_id',
        'birthday' => 'bdate',
        'gender' => 'sex',
    ];

    /** 
     * Получение данных конфига по умолчанию.
     * @return array|null
     */
    public function configDefault(): ?array
    {
        return [
            'scope' => implode(',', [
                'public_profile', 'email', 'user_link',
                'user_location', 'user_hometown',
                'user_birthday', 'user_gender',
            ]),
            'user_data_fields' => implode(',', [
                'id', 'first_name', 'last_name', //'name',
                'picture', 'email', 'link',
                'location', 'birthday', 'gender',
            ]),
        ];
    }

    /**
     * Получение oauth ссылки на аутентификацию.
     * @return string ссылка для перехода на аутентификацию
     */
    public function getAuthLink(): string
    {
        return static::buildLink(static::OAUTH_URL, [
            'client_id' => $this->config['client_id'],
            'redirect_uri' => $this->config['redirect_uri'],
            'scope' => $this->config['scope'],
            'state' => '{'. uniqid() .'='. uniqid() .'}',
        ]);
    }

    /**
     * Запрос oauth на доступ.
     * @param string код доступа
     * @return array данные доступа
     * @throws AuthException
     */
    public function fetchAccess(string $code): array
    {
        $data = static::exec(static::ACCESS_URL, [
            'client_id' => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
            'redirect_uri' => $this->config['redirect_uri'],
            'code' => $code,
        ]);
        if (empty($data)) {
            throw AuthException::build('oauth_empty_access', static::SOURCE_NAME);
        }
        return $data;
    }

    /**
     * Проверка на ошибку ответа api.
     * @param array|null ответ api
     * @throws AuthException
     */
    public static function checkResponse(array $data = null)
    {
        if (empty($data) || empty($data['response'])) {
            $error = null;
            if (!@empty($data['error'])) $error = $data['error'];
            if (!@empty($data['response']['error'])) {
                $error = $data['response']['error'];
            }
            if (!empty($error)) $error = ': ' . json_encode($error);
            throw AuthException::build('oauth_error_response', static::SOURCE_NAME, $error);
        }
    }

    /**
     * Запрос данных пользователя по данным доступа.
     * @param array данные доступа
     * @return array данные пользователя
     */
    public function fetchUserDataByAccess(array $accessData): array
    {
        return $this->fetchUserData($accessData['access_token']);
    }

    /**
     * Запрос данных пользователя по токену доступа.
     * @param string токен пользователя
     * @param string получаемые поля
     * @param string|null id пользователей через запятую
     * @return array данные пользователя
     * @throws AuthException
     */
    public function fetchUserData(string $access_token, string $fields = null, string $user_ids = null): array
    {
        $queryData = compact('access_token');
        if (empty($fields)) {
            $fields = $this->config['user_data_fields'];
        }
        if (!empty($fields)) {
            $queryData['fields'] = $fields;
        }
        // if (!empty($user_ids)) {
        //     $user_ids = substr($user_ids, 0, strpos($user_ids, ','));
        //     $queryData['user_ids'] = $user_ids;
        // }
        $data = $this->queryApi('me', $queryData);
        // static::checkResponse($data);
        return $data;
        // if (!empty($user_ids)) return $data->response;
        // return $data->response[0];
    }

    /**
     * Подготовка пользовательских данных.
     * @return array
     */
    protected function prepareData(): array
    {
        $data = $this->getUserData();
        static::renameDataKeys($data);
        if (isset($data['picture']) && is_array($data['picture'])) {
            $data['picture'] = $data['picture']['data']['url'];
        }
        if (isset($data['bdate'])) {
            @list($month, $day, $year) = explode('/', $data['bdate']);
            if ($day < 10) $day = "0$day";
            if ($month < 10) $month = "0$day";
            $data['bdate'] = "$year-$month-$day";
        }
        if (isset($data['sex'])) {
            $sex_ids = ['female' => 1, 'male' => 2];
            $data['sex'] = $sex_ids[$data['sex']];
        }
        if (isset($data['location']) && isset($data['location']['name'])) {
            @list($city, $region) = explode(',', $data['location']['name']);
            $data['city'] = $city;
            $data['region'] = $region;
        }
        return $data;
    }


    /**
     * @deprecated
     * @static array маппинг расшифровок ошибок 
     */
    const API_REQUEST_ERRORS_MAP = [
        4 => 'Превышен общий часовой лимит обращений к Facebook API',
        17 => 'Превышен ваш часовой лимит обращений к Facebook API',
        613 => 'Превышен лимит обращений к методу Facebook API',
         // HTTP X-App-Usage
    ];
}
