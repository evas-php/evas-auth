<?php
/**
 * Модель подтверждения телефона/email.
 * @package evas-php\evas-auth
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Auth\Models;

use Evas\Auth\Auth;
use Evas\Auth\AuthException;
use Evas\Auth\Help\Model;
use Evas\Auth\Help\Token;

class AuthConfirm extends Model
{
    const TYPE_EMAIL = 1;
    const TYPE_PHONE = 2;
    const TYPES = [
        null => 'unknown',
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
     * Определение типа источника получения кода.
     * @param string источник получения
     * @return int|null тип источника
     */
    public static function getRecipientType(string $to): ?int
    {
        return (new EmailField)->checkPattern($to) ? self::TYPE_EMAIL 
        : (
            (new PhoneField)->checkPattern($to) ? self::TYPE_PHONE
            : null
        );
    }

    /**
     * Создание подтверждения.
     * @param int id пользователя
     * @param string источник получения
     * @param string|null тип источника получения
     * @return static
     */
    public static function make(int $user_id, string $to, string $type = null)
    {
        $code = static::generateCode();
        if ($type) $type = array_search($type, self::TYPES) ?? null;
        else $type = static::getRecipientType($to);
        $end_time = date('Y-m-d H:i:s', time() + (int) Auth::config()['code_alive']);
        $data = compact('user_id', 'to', 'code', 'type', 'end_time');
        return static::insert($data);
    }

    /**
     * Генерация кода для подтверждения.
     * @return string
     */
    public static function generateCode(): string
    {
        // $codeLength = Auth::config()['code_length'] ?? 6;
        // return Token::generateUniqueIn([static::tableName(), 'code'], $codeLength);
        return (new Token([
            'symbols' => '0123456789',
            'token_length' => Auth::config()['code_length'],
            'token_generate_max_tries' => Auth::config()['token_generate_max_tries'],
        ]))->generateUniqueIn([static::tableName(), 'code']);
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
