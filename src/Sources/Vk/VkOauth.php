<?php
/**
 * @package evas-php\evas-auth
 */
namespace Evas\Auth\Sources\Vk;

use Evas\Auth\AuthException;
use Evas\Auth\Helpers\BaseApi;

/**
 * Oauth api авторизации через vk.com
 * @link vk oauth doc https://vk.com/dev/access_token
 * @author Egor Vasyakin <egor@evas-php.com>
 * @since 7 Sep 2020
 */
class VkOauth extends BaseApi
{
    /**
     * @static string домен oauth
     */
    const OAUTH_URL = 'https://oauth.vk.com/authorize';

    /**
     * @static string uri получения токена доступа
     */
    const ACCESS_URL = 'https://oauth.vk.com/access_token';

    /**
     * @static string домен api
     */
    const API_URL = 'https://api.vk.com/method/';

    /**
     * @static string путь к конфигу
     */
    const CONFIG_PATH = 'config/vk.php';

    /**
     * @static array данные конфига по умолчанию
     */
    const CONFIG_DEFAULT = [
        'v' => '5.80',
        'display' => 'page',
        'scope' => 'offline,friends',
        'responce_type' => 'token',
    ];

    /**
     * @static string ошибка соединения
     */
    const ERROR_RESPONSE = 'Неверный ответ от VK API';

    /**
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

    /**
     * Переопределяем запрос api.
     * @param string|null метод запроса
     * @param array|null параметры запроса
     * @return object
     */
    public static function query(string $method = null, array $params = null): object
    {
        return parent::query(
            $method, 
            array_merge(['v' => static::config()->get('v')], $params)
        );
    }

    /**
     * Получение oauth ссылки на авторизацию.
     * @return string ссылка для перехода на авторизацию
     */
    public static function getAuthLink(): string
    {
        return static::buildLink(static::OAUTH_URL, [
            'v' => static::config()->get('v'),
            'client_id' => static::config()->get('client_id'),
            'redirect_uri' => static::config()->get('redirect_uri'),
            'display' => static::config()->get('display'),
            'scope' => static::config()->get('scope'),
            'responce_type' => static::config()->get('responce_type'),
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
        if (!empty($user_ids)) {
            $user_ids = substr($user_ids, 0, strpos($user_ids, ','));
            $queryData['user_ids'] = $user_ids;
        }
        $data = static::query('users.get', $queryData);
        static::checkResponse($data);
        if (!empty($user_ids)) return $data->response;
        return $data->response[0];
    }

    /**
     * Получение данных пользователей.
     * @param string токен текущего пользователя
     * @param string получаемые поля
     * @param array id пользователей
     * @throws AuthException
     * @return array
     */
    public static function getUsersData(string $access_token, string $fields = null, array $user_ids): array
    {
        // $fields = static::config()->get('user_fields');
        $user_ids = implode(',', $user_ids);
        $data = static::query('users.get', compact('access_token', 'fields', 'user_ids'));
        static::checkResponse($data);
        if (!empty($user_ids)) return [$data->response];
        return $data->response; 
    }

    /**
     * Преобразование данных пользователя в единый формат.
     * @param array данные пользователя без форматирования
     * @return array данные пользователя после форматирования
     */
    public static function userDataFormatting(array $data): array
    {
        $data['picture'] = $data['photo_max'];
        unset($data['photo_max']);
        if (!empty($data['bdate'])) {
            list($d, $m, $y) = explode('.', $data['bday']);
            if ($d < 10) $d = "0$d";
            if ($m < 10) $m = "0$m";
            $data['birthdate'] = "$d.$m.$y";
            unset($data['bdate']);
        }
        if (!empty($data['sex'])) {
            $data['gender'] = $data['sex'];
            unset($data['sex']);
        }
        if (!empty($data['city'])) {
            $data['city'] = $data['city']['title'];
        }
        if (!empty($data['country'])) {
            $data['country'] = $data['country']['title'];
        }
        return $data;
    }
}
