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
use Evas\Validate\Fields\EmailField;
use Evas\Validate\Fields\PhoneField;

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
    /** @var int тип адреса получения */
    public $type;
    /** @var string адрес получения кода подтверждения */
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
     * Проверка завершённости подтверждения.
     * @return bool
     */
    public function isCompleted(): bool
    {
        return !empty($this->complete_time);
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
     * Определение типа адреса получения кода.
     * @param string адрес получения
     * @return int|null тип адреса
     */
    public static function getRecipientType(string $to): ?int
    {
        return (new EmailField)->isValid($to) ? self::TYPE_EMAIL 
        : ( (new PhoneField)->isValid($to) ? self::TYPE_PHONE : null );
    }

    /**
     * Создание или обновление подтверждения адреса входа.
     * @param int id пользователя
     * @param string адрес получения
     * @param string|null тип адреса получения
     * @return static
     */
    public static function make(int $user_id, string $to, string $type = null): AuthConfirm
    {
        $confirm = static::findByUserIdAndTo($user_id, $to);
        if ($confirm) {
            $confirm->complete_time = null;
        } else {
            if ($type) $type = array_search($type, self::TYPES) ?? null;
            else $type = static::getRecipientType($to);
            $confirm = static::create(compact('user_id', 'to', 'type'));
        }
        $confirm->code = static::generateCode();
        $confirm->end_time = date('Y-m-d H:i:s', time() + (int) Auth::config()['code_alive']);
        return $confirm->save();
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
        return static::whereRowValues(['user_id', 'code'], [$user_id, $code])
        ->whereRaw('complete_time IS NULL')->one();
    }

    /**
     * Поиск по id пользователя и адресу получения.
     * @param int id пользователя
     * @param string адрес получения
     * @return static|null
     */
    public static function findByUserIdAndTo(int $user_id, string $to): ?AuthConfirm
    {
        return static::whereRowValues(['user_id', 'to'], [$user_id, $to])->one();
    }
}
