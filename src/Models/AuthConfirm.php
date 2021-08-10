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
        if ($this->isOutdated()) {
            throw new AuthException('Код подтверждения устарел');
        }
        $this->complete_time = date('Y-m-d H:i:s');
        $this->save();
    }

    /**
     * Создание подтверждения через email.
     * @param int id пользователя
     * @param string email
     * @return static
     */    
    public static function createToEmail(int $user_id, string $to)
    {
        $code = static::generateCode();
        $data = compact('user_id', 'to', 'code');
        $data['type'] = self::TYPE_EMAIL;
        return new static($data);
    }

    /**
     * Создание подтверждения через телефон.
     * @param int id пользователя
     * @param string номер телефона
     * @return static
     */   
    public static function createToPhone(int $user_id, string $to)
    {
        $code = static::generateCode();
        $data = compact('user_id', 'to', 'code');
        $data['type'] = self::TYPE_PHONE;
        return new static($data);
    }

    /**
     * Подтверждение email.
     * @param string email
     * @param string код подтверждения
     */
    public static function completeEmail(string $email, string $code)
    {
        // 
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
     * Поиск по коду отправленному на email.
     * @param string код
     * @return static|null
     */
    public static function findByEmailCode(string $code): ?AuthRecovery
    {
        return static::findByTypeCode(self::TYPE_EMAIL, $code);
    }

    /**
     * Поиск по коду отправленному на телефон.
     * @param string код
     * @return static|null
     */
    public static function findByPhoneCode(string $code): ?AuthRecovery
    {
        return static::findByTypeCode(self::TYPE_PHONE, $code);
    }

    /**
     * Поиск по коду и типу отправки.
     * @param string тип отправки кода
     * @param string код
     * @return static|null
     */
    public static function findByTypeCode(int $type, string $code): ?AuthRecovery
    {
        return static::find()->where(
            '`type` = ? AND `code` = ?', [$type, $code]
        )->one()->classObject(static::class);
    }
}
