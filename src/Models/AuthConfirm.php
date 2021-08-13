<?php
/**
 * Модель подтверждения телефона/email.
 * @package evas-php\evas-auth
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Auth\Models;

use Evas\Auth\AuthException;
use Evas\Auth\Help\Model;
use Evas\Auth\Help\Token;

class AuthConfirm extends Model
{
    const TYPE_EMAIL = 0;
    const TYPE_PHONE = 1;
    const TYPES = [
        self::TYPE_EMAIL => 'email',
        self::TYPE_PHONE => 'phone',
    ];

    /** @var int id подтверждения */
    public $id;
    /** @var int id пользователя */
    public $user_id;
    /** @var int тип источника получения */
    public $type;
    /** @var string источник получения кода подтверждения */
    public $to;
    /** @var string код подтверждения */
    public $code;
    /** @var string время создания */
    public $create_time;
    /** @var string время просрочки */
    public $end_time;
    /** @var string время завершения */
    public $complete_time;


    /**
     * Проверка просроченности подтверждения.
     * @return bool
     */
    public function isOutdated(): bool
    {
        return time() > strtotime($this->end_time);
    }

    /**
     * Установка завершения подтверждения.
     * @throws AuthException
     */
    public function complete()
    {
        if ($this->isOutdated()) throw AuthException::build('code_is_outdated');
        $this->complete_time = date('Y-m-d H:i:s');
        $this->save();
    }

    /**
     * Создание подтверждения.
     * @param int id пользователя
     * @param string источник получения
     * @return static
     */
    public static function make(int $user_id, string $to)
    {
        $code = static::generateCode();
        $type = self::TYPE_EMAIL;
        $data = compact('user_id', 'to', 'code', 'type');
        return static::insert($data);
    }

    /**
     * Генерация кода для подтверждения.
     * @return string
     */
    public static function generateCode(): string
    {
        return Token::generateUniqueIn([static::tableName(), 'code'], 6);
    }

    /**
     * Поиск по id пользователя и коду.
     * @param int id пользователя
     * @param string код
     * @return static|null
     */
    public static function findByUserIdAndCode(int $user_id, string $code): ?AuthConfirm
    {
        return static::find()->where('user_id = ? AND code = ?', [$user_id, $code])
        ->one()->classObject(static::class);
    }
}
