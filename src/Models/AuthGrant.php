<?php
/**
 * @package evas-php\evas-auth
 */
namespace Evas\Auth\Models;

use Evas\Auth\AuthAdapter;
use Evas\Auth\Helpers\Model;

/**
 * Модель гранта авторизации.
 * @author Egor Vasyakin <egor@evas-php.com>
 * @since 14 Sep 2020
 */
class AuthGrant extends Model
{
    /**
     * @static int константы id статусов
     */
    const STATUS_NOT_CONFIRMED = 0;
    const STATUS_CONFIRMED = 1;
    const STATUS_OUTDATED = 2;

    /**
     * Поля записи.
     * @var int $id UNSIGNED PRIMARY id записи
     * @var int $user_id UNSIGNED INDEX id пользователя
     * @var varchar(7) $source INDEX источник входа
     * @var var_char(60) $login INDEX (+source) логин/id пользователя в источнике
     * @var var_char(250) $token INDEX (+source) токен пользователя в источнике
     * @var tinyint(3) $status_id статус
     * @var json(512) $payload дополнительная нагрузка источника (для доп. параметров гугла, например)
     * @var datetime $create_time время создания записи
     */
    public $id;
    public $user_id;
    public $source;
    public $login;
    public $token;
    public $status_id;
    public $payload;
    public $create_time;

    // | id | user_id | source | login          | token         | payload | create_time |
    // |------------------------------------------------------------------|-------------|
    // |  1 |       1 | email  | test@test.test | password_hash | null    | datetime    |
    // |  2 |       1 | vk     | 213214214      | vk_token      | null    | datetime    |
    // |  3 |       1 | fb     | 123214214      | fb_token      | null    | datetime    |
    // |  5 |       1 | google | gmail          | g_token       | {JSON}  | datetime    |

    /**
     * Создание токена пользователя.
     * @param int id пользователя
     * @param string источник входа
     * @param string логин/id источника
     * @param string токен источника
     * @throws AuthException
     * @return static
     */
    public static function make(int $user_id, string $source, string $login, string $token): AuthGrant
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
    public static function findByToken(string $token, string $source): ?AuthGrant
    {
        return static::find()
            ->where('source = ? AND token = ?', [$source, $token])
            ->one()->classObject(static::class);
    }

    /**
     * Получение строкового статуса.
     */
    public function stringStatus(): string
    {
        return 'Ваш ' . $this->source . ' ' . AuthAdapter::AUTH_GRANT_STATUSES_MAP[$this->status_id];
    }
}
