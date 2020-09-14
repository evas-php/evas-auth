<?php
/**
 * @package evas-php\evas-auth
 */
namespace Evas\Auth\Models;

use Evas\Auth\Helpers\Model;

/**
 * Модель гранта авторизации.
 * @author Egor Vasyakin <egor@evas-php.com>
 * @since 14 Sep 2020
 */
class AuthGrant extends Model
{
    /**
     * @var string имя таблицы
     */
    public static $tableName = 'auth_grants';

    /**
     * Поля записи.
     * @var int UNSIGNED INDEX id пользователя
     * @var varchar(7) INDEX источник входа
     * @var var_char(60) UNIQUE логин/id пользователя в источнике
     * @var var_char(128) UNIQUE(+source) токен пользователя в источнике
     */
    public $user_id;
    public $source;
    public $login;
    public $token;

    // | id | user_id | source | login          | token         | create_time |
    // |----------------------------------------------------------------------|
    // |  1 |       1 | email  | test@test.test | password_hash | datetime    |
    // |  2 |       1 | vk     | 213214214      | vk_token      | datetime    |
    // |  3 |       1 | fb     | 123214214      | fb_token      | datetime    |
    // |  5 |       1 | google | gmail          | g_token       | datetime    |

    /**
     * Создание токена пользователя.
     * @param int id пользователя
     * @param string источник входа
     * @param string логин/id источника
     * @param string токен источника
     * @throws AuthException
     * @return static
     */
    public static function make(int $user_id, string $source, string $login, string $token): object
    {
        // валидация!
        // if (!in_array($source, static::SOURCES)) {
        //     throw new AuthException(static::setMessageVarsStatic(
        //         static::ERROR_INCORRECT_AUTH_SOURCE, compact('source')
        //     ));
        // }
        return static::insert(compact('user_id', 'source', 'login', 'token'));
    }

    /**
     * Поиск по токену и источнику.
     * @param string токена
     * @param string источник
     * @return static|null
     */
    public static function findByToken(string $token, string $source): ?object
    {
        return static::find()
            ->where('source = ? AND token = ?', [$source, $token])
            ->one()->classObject(static::class);
    }
}
