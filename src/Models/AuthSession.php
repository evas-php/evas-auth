<?php
/**
 * Модель сессии аутентификации.
 * @package evas-php\evas-auth
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Auth\Models;

use Evas\Auth\Auth;
use Evas\Auth\Help\Model;
use Evas\Auth\Help\Token;
use Evas\Base\App;
use Evas\Http\Interfaces\RequestInterface;

class AuthSession extends Model
{
    /** @var int id сессии */
    public $id;
    /** @var int id пользователя */
    public $user_id;
    /** @var string токен сессии (для JWT - recovery token) */
    public $token;
    /** @var int id гранта аутентификации */
    public $auth_grant_id;
    /** @var string токен гранта внешнего ресурса */
    public $grant_token;
    /** @var string ip пользователя */
    public $user_ip;
    /** @var string заголовок User-Agent пользователя */
    public $user_agent;
    /** @var string семейство операционной системы пользователя */ 
    public $user_os;
    /** @var string семейство браузера пользователя */
    public $user_browser;
    /** @var string время создания */
    public $create_time;
    /** @var string время просрочки */
    public $end_time;

    /**
     * Создание или обновление сессии аутентификации.
     * @param AuthGrant грант аутентификации
     * @param string|null токен гранта аутентификации
     * @param RequestInterface запрос
     * @return static
     */
    public static function make(
        AuthGrant &$grant, 
        string $grant_token = null,
        RequestInterface &$request = null
    ): AuthSession
    {
        if (!$request) $request = Auth::getRequest();
        $user_id = $grant->user_id;
        $auth_grant_id = $grant->id;
        $user_ip = $request->getUserIp();
        $user_agent = $request->getHeader('User-Agent');
        // $token = Token::generateUniqueIn(static::tableName());
        $token = (new Token([
            'symbols' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789',
            'token_length' => Auth::config()['token_length'],
            'token_generate_max_tries' => Auth::config()['token_generate_max_tries'],
        ]))->generateUniqueIn(static::tableName());

        $session = static::whereRowValues(
            ['user_id', 'auth_grant_id', 'user_ip', 'user_agent'],
            [$user_id, $auth_grant_id, $user_ip, $user_agent]
        )->one();

        if (empty($session)) {
            list($user_browser, $user_os) = $request->parseUserAgent();
            $session = static::create(compact(
                'user_id', 'token', 'auth_grant_id', 'grant_token',
                'user_ip', 'user_agent', 'user_os', 'user_browser'
            ));
        } else {
            $session->token = $token;
        }
        $session->updateEndTime();
        return $session;
    }

    /**
     * Выход из одной или нескольких сессий.
     * @param enum of AuthSession сессии через запятую
     */
    public static function logout(AuthSession ...$sessions)
    {
        $loggedUserId = Auth::loggedUserId();
        foreach ($sessions as &$session) {
            if ($session->user_id !== $loggedUserId) {
                $session->destroy();
            }
        }
    }

    /**
     * Выход из сессии.
     */
    public function destroy()
    {
        return $this->updateEndTime(-1); 
    }

    /**
     * Обновление времени истечения сессии.
     * @param int сдвиг времени относительно текущего
     */
    protected function updateEndTime(int $alive = null)
    {
        if (null === $alive) {
            $alive = Auth::config()['token_alive'];
        }
        $this->end_time = date('Y-m-d H:i:s', time() + $alive);
        $this->save();
        // $this->afterUpdateEndTime($withCookie);
    }

    // /**
    //  * Хук после обновления времени истечения сессиии для установки cookie.
    //  * @param bool|true установить ли при этом cookie
    //  */
    // protected function afterUpdateEndTime(bool $withCookie = true)
    // {
    //     if (true === $withCookie) {
    //         $this->setCookieToken();
    //     }
    // }

    // /**
    //  * Установка токена сессии в cookie.
    //  */
    // protected function setCookieToken()
    // {
    //     $config = Auth::config();
    //     $name = $config->get('auth_token_cookie_name');
    //     $path = $config->get('auth_token_cookie_path');
    //     $host = $config->get('auth_token_cookie_host');
    //     $time = strtotime($this->end_time);
    //     setcookie($name, $this->token, $time, $path, $host, false, true);
    // }

    /**
     * Проверка является ли сессия актуальной.
     * @return bool
     */
    public function isActual(): bool
    {
        return strtotime($this->end_time) > time();
    }

    /**
     * Хук. Обновляем время создания сессии, при её обновлении.
     */
    protected function beforeUpdate()
    {
        $this->setCreateTime();
    }

    /**
     * Поиск сессии по токену.
     * @param string токен
     * @param bool|false искать ли только актуальные
     * @return ?static
     */
    public static function findByToken(string $token, bool $actual = false): ?AuthSession
    {
        $qb = static::where('token', $token);
        if (true === $actual) $qb->whereRaw('end_time > NOW()');
        return $qb->one();
    }

    /**
     * Поиск актуальной сессии по токену.
     * @param string токен
     * @return ?static
     */
    public static function findActualByToken(string $token): ?AuthSession
    {
        return static::findByToken($token, true);
    }

    /**
     * Поиск актуальной сессии по токену с получением id пользователя.
     * @param string токен
     * @return int|null id пользователя
     */
    public static function findUserIdByToken(string $token): ?int
    {
        $session = static::findActualByToken($token);
        return $session ? $session->user_id : null;
    }

    /**
     * Поиск сессий по id пользователя.
     * @param int id пользователя
     * @param bool|false искать ли только актуальные
     * @return array
     */
    public static function findByUserId(int $user_id, bool $actual = false): array
    {
        $qb = static::where('user_id', $user_id)
        if (true === $actual) $qb->whereRaw('end_time > NOW()');
        return $qb->get();
    }

    /**
     * Поиск актуальных сессий по id пользователя.
     * @param int id пользователя
     * @return array
     */
    public static function findActualByUserId(int $user_id): array
    {
        return static::findByUserId($user_id, true);
    }
}
