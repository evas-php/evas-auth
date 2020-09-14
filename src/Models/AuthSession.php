<?php
/**
 * @package evas-php\evas-auth
 */
namespace Evas\Auth\Models;

use Evas\Auth\AuthAdapter;
use Evas\Auth\Helpers\Model;

/**
 * Модель сессии авторизации.
 * @author Egor Vasyakin <e.vasyakin@itevas.ru>
 * @since 14 Sep 2020
 */
class AuthSession extends Model
{
    /**
     * Поля записи.
     * @var int $id UNSIGNED PRIMARY id записи
     * @var int $user_id UNSIGNED INDEX id пользователя
     * @var int $auth_grant_id UNSIGNED INDEX id гранта авторизации
     * @var varchar(60) $token UNIQUE токен пользователя
     * @var varchar(15) $user_ip ip пользователя
     * @var varchar(250) $user_agent user_agent пользователя
     * @var datetime $end_time время истечения токена
     * @var datetime $create_time время создания записи
     */
    public $id;
    public $user_id;
    public $auth_grant_id;
    public $token;
    public $user_ip;
    public $user_agent;
    public $end_time;
    public $create_time;

    // | id | user_id | auth_grant_id | token | user_ip | user_agent | user_browser | user_os | end_time | create_time |
    // |---------------------------------------------------------------------------------------------------------------|
    // |  1 |       1 |             1 | token | 1.1.1.1 | Firefox... | Firefox      | Windows | datetime | datetime    |
    // |  1 |       2 |             2 | token | 1.1.1.1 | Chrome...  | Chrome       | MacOS   | datetime | datetime    |


    /**
     * Получение записи по токену.
     * @param string
     * @return static|null
     */
    public static function findByToken(string $token): ?AuthSession
    {
        return static::find()->where('token = ?', [$token])->one()->classObject(static::class);
    }

    /**
     * @deprecated Получение версии ос и браузера из юзер агента.
     * @param string user agent
     * @return array [browser,os]
     */
    public static function parseUserAgent(string $userAgent): array
    {
        $user_agent_sliced = preg_split("/^([^(]+)\/\S+?\s\(([^;)]+)?;?([^;)]+)?;?[^)]+?\) ?(.+\/\S+?)?\S?(.+\/\S+?)?$/m", $user_agent, -1, PREG_SPLIT_DELIM_CAPTURE);
        $user_browser = $user_agent_sliced[1];
        $user_os = isset($user_agent_sliced[2]) ? $user_agent_sliced[2] . ' ' : '' . isset($user_agent_sliced[3]) ? $user_agent_sliced[3] : '';
        return [$user_browser, $user_os];
    }

    /**
     * Авторизация.
     * Создание/обновление записи авторизации и запись токена в cookie.
     * @param AuthGrant гарант авторизации
     * @return static
     */
    public static function login(AuthGrant $authGrant): AuthSession
    {
        $user_id = $authGrant->user_id;
        $auth_grant_id = $authGrant->id;
        $user_ip = App::request()->getUserIp();
        $user_agent = App::request()->getHeader('User-Agent');        

        $end_time = date('Y-m-d h:i:s', time() + AuthAdapter::AUTH_TOKEN_ALIVE);

        $auth = static::find()
            ->where('user_id = ? AND user_ip = ? AND user_agent = ?', [$user_id, $user_ip, $user_agent])
            ->one()->classObject(static::class);

        if ($auth) {
            $auth->end_time = $end_time;
            return $auth->save();
        }
        $token = Token::generateUniqueIn(static::tableName());
        (new Cookie)
            ->withHost(App::host())
            ->set(AuthAdapter::AUTH_TOKEN_COOKIE_NAME, $auth->token, AuthAdapter::AUTH_TOKEN_ALIVE);
        return static::insert(compact('user_id', 'auth_grant_id', 'user_ip', 'user_agent', 'end_time', 'token'));
    }
}
