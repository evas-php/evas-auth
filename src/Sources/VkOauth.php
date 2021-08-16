<?php
/**
 * Oauth api аутентификации через vk.com
 * @package evas-php\evas-auth
 * @author Egor Vasyakin <egor@evas-php.com>
 * @link vk oauth doc https://vk.com/dev/access_token
 */
namespace Evas\Auth\Sources;

use Evas\Auth\AuthException;
use Evas\Auth\Help\BaseOauth;

class VkOauth extends BaseOauth
{
    /** @static string имя источника аутентификации */
    const SOURCE = 'vk';
    /** @static string полное имя источника аутентификации */
    const SOURCE_NAME = 'vk.com';

    /** @static string домен oauth */
    const OAUTH_URL = 'https://oauth.vk.com/authorize';
    /** @static string uri получения токена доступа */
    const ACCESS_URL = 'https://oauth.vk.com/access_token';
    /** @static string домен api */
    const API_URL = 'https://api.vk.com/method/';

    /** @static array маппинг замены ключей пользовательских данных */
    const USER_DATA_KEYS_REPLACES = [
        'id' => 'vk_id',
        'photo_max' => 'picture',
    ];

    /** 
     * Получение данных конфига по умолчанию.
     * @return array|null
     */
    public function configDefault(): ?array
    {
        return [
            'v' => '5.80',
            'display' => 'page',
            'scope' => 'offline,friends',
            'responce_type' => 'token',
            'scope' => 'email',
            'user_data_fields' => implode(', ', [
                'photo_max', 'email', 'country', 'city', 'bdate', 
                'sex', 'activities', 'site', 'connections'
            ]),
        ];
    }

    /**
     * Переопределяем запрос api.
     * @param string|null метод запроса
     * @param array|null параметры запроса
     * @return array
     */
    public static function queryApi(string $method = null, array $params = null): array
    {
        $params = array_merge(['v' => $this->config['v']], $params);
        return parent::queryApi($method, $params);
    }

    /**
     * Получение oauth ссылки на аутентификацию.
     * @return string ссылка для перехода на аутентификацию
     */
    public function getAuthLink(): string
    {
        return static::buildLink(static::OAUTH_URL, [
            'v' => $this->config['v'],
            'client_id' => $this->config['client_id'],
            'redirect_uri' => $this->config['redirect_uri'],
            'display' => $this->config['display'],
            'scope' => $this->config['scope'],
            'responce_type' => $this->config['responce_type'],
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
        if (!empty($user_ids)) {
            $user_ids = substr($user_ids, 0, strpos($user_ids, ','));
            $queryData['user_ids'] = $user_ids;
        }
        $data = static::queryApi('users.get', $queryData);
        static::checkResponse($data);
        if (!empty($user_ids)) return $data['response'];
        return $data['response'][0];
    }

    /**
     * Запрос данных пользователей по токену доступа.
     * @param string токен текущего пользователя
     * @param string получаемые поля
     * @param array id пользователей
     * @return array данные пользователя
     * @throws AuthException
     */
    public function fetchUsersData(string $access_token, string $fields = null, array $user_ids): array
    {
        if (!$fields) $fields = $this->config['user_fields'];
        $user_ids = implode(',', $user_ids);
        $data = static::queryApi('users.get', compact('access_token', 'fields', 'user_ids'));
        static::checkResponse($data);
        if (!empty($user_ids)) return [$data['response']];
        return $data['response']; 
    }

    /**
     * Подготовка пользовательских данных.
     */
    protected function prepareData()
    {
        $data = $this->getUserData();
        static::renameDataKeys($data);
        if (isset($data['bdate'])) {
            list($day, $month, $year) = explode('.', $data['bdate']);
            if ($day < 10) $day = "0$day";
            if ($month < 10) $month = "0$day";
            $data['bdate'] = "$year-$month-$day";
        }
        if (isset($data['country']) && is_array($data['country'])) {
            $data['country'] = $data['country']['title'];
        }
        if (isset($data['city']) && is_array($data['city'])) {
            $data['city'] = $data['city']['title'];
        }
        if (isset($this->accessData['email'])) {
            $data['email'] = $this->accessData['email'];
        }
        $this->prepareData = $data;
    }


    /**
     * @deprecated
     * @static array маппинг расшифровок ошибок 
     */
    const API_REQUEST_ERRORS_MAP = [
        1 => 'Произошла неизвестная ошибка.',
        2 => 'Приложение выключено.',
        3 => 'Передан неизвестный метод.',
        4 => 'Неверная подпись.',
        5 => 'Авторизация пользователя не удалась. Сделайте выход из кабинета и зайдите заново',
        6 => 'Слишком много запросов в секунду.',
        7 => 'Нет прав для выполнения этого действия.',
        8 => 'Неверный запрос.',
        9 => 'Слишком много однотипных действий.',
        10 => 'Произошла внутренняя ошибка сервера.',
        11 => 'В тестовом режиме приложение должно быть выключено или пользователь должен быть залогинен.',
        14 => 'Требуется ввод кода с картинки (Captcha).',
        15 => ' Доступ запрещён.',
        16 => 'Нужен протокол HTTPS',
        17 => 'Требуется валидация пользователя.',
        18 => 'Страница удалена или заблокирована.',
        20 => 'Данное действие запрещено для не Standalone приложений.',
        21 => 'Данное действие разрешено только для Standalone и Open API приложений.',
        23 => 'Метод был выключен.',
        24 => 'Требуется подтверждение со стороны пользователя.',
        27 => ' Ключ доступа сообщества недействителен.',
        28 => 'Ключ доступа приложения недействителен.',
        29 => 'Достигнут количественный лимит на вызов метода.',
        30 => 'Профиль является приватным.',
        100 => 'Один из необходимых параметров был не передан или неверен.',
        101 => 'Неверный API ID приложения.',
        113 => 'Неверный идентификатор пользователя.',
        150 => 'Неверный timestamp (метка времени).',
        203 => 'Доступ к группе запрещён.',
    ];
}
