<?php
/**
 * @package evas-php\evas-auth
 */
namespace Evas\Auth\Models;

use Evas\Auth\AuthAdapter;
use Evas\Auth\Helpers\Model;
use Evas\Auth\Models\AuthGrant;

/**
 * Модель восстановления гранта авторизации.
 * @author Egor Vasyakin <e.vasyakin@itevas.ru>
 * @since 14 Sep 2020
 */
class AuthGrantRecovery extends Model
{
    /**
     * @var string первичный ключ
     */
    public static $primaryKey = 'auth_grant_id';

    /**
     * Поля записи.
     * @var int $auth_grant_id UNSIGNED PRIMARY id гранта авторизации
     * @var int $user_id UNSIGNED INDEX id пользователя
     * @var varchar(7) $code UNIQUE код подтверждения
     * @var datetime $create_time время создания записи
     */
    public $auth_grant_id;
    public $user_id;
    public $code;
    public $create_time;

    // | auth_grant_id | user_id | code    | create_time         |
    // |---------------|---------|---------|---------------------|
    // | 1             | 1       | XS4Sd12 | 2020-09-14 19:48:32 |
    // | 2             | 1       | aG4f88s | 2020-09-14 19:48:32 |

    /**
     * Поиск по коду подтверждения.
     * @param string код
     * @return static|null
     */
    public static function findByCode(string $code): ?AuthGrantRecovery
    {
        return static::find()->where('code = ?', [$code])->one()->classObject(static::class);
    }

    /**
     * Подтверждение воостановления гранта авторизации по коду.
     * @param string код
     * @throws AuthException
     */
    public static function recoveryByCode(string $code, string $token)
    {
        $row = static::findByCode($code);
        if (empty($row)) {
            AuthAdapter::throwError(AuthAdapter::ERROR_AUTH_GRANT_RECOVERY_NOT_FOUND);
        }
        // ищем грант авторизации и восстанавливаем его
        $ag = AuthGrant::findByid($row->auth_grant_id);
        if (empty($ag)) {
            AuthAdapter::throwError(AuthAdapter::ERROR_AUTH_GRANT_NOT_FOUND);
        }
        $ag->recovery($token);
        // удаляем запись о проверке
        $row->delete();
    }
}
