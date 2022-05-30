<?php
/**
 * Модель гранта аутентификации.
 * @package evas-php\evas-auth
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Auth\Models;

use Evas\Auth\Auth;
use Evas\Auth\AuthException;
use Evas\Auth\Help\Model;

class AuthGrant extends Model
{
    /** @var int id гранта аутентификации */
    public $id;
    /** @var int id пользователя */
    public $user_id;
    /** @var string источник */
    public $source;
    /** @var string id пользователя в источнике или хеш пароля для гранта пароля */
    public $source_key;
    /** @var string время создания гранта */
    public $create_time;

    /**
     * Проверка соответствия пароля хешу пароля гранта пароля.
     * @param string пароль
     * @return bool
     */
    public function checkPassword(string $password): bool
    {
        $this->throwIfNotPasswordGrant();
        return password_verify($password, $this->source_key);
    }

    /**
     * Изменение пароля.
     * @param string старый пароль
     * @param string новый пароль
     * @throws AuthException
     */
    public function changePassword(string $old, string $new)
    {
        if (!$this->checkPassword($old)) {
            throw AuthException::build('incorrect_password_old');
        }
        $this->setPasswordHash($new);
    }

    /**
     * Установка хеша пароля.
     * @param string пароль
     */
    public function setPasswordHash(string $password)
    {
        $this->throwIfNotPasswordGrant();
        $this->source_key = password_hash($password, PASSWORD_DEFAULT);
        $this->save();
    }

    /**
     * Является ли грантом пароля.
     * @return bool
     */
    public function isPasswordGrant(): bool
    {
        return 'password' === $this->source;
    }

    /**
     * Выбрасывание исключения если это не грант пароля.
     * @throws AuthException
     */
    public function throwIfNotPasswordGrant()
    {
        if (!$this->isPasswordGrant()) {
            throw new AuthException('Is not password grant!');
        }
    }

    /**
     * Выбрасывание исключения, если источник не поддерживается.
     * @param string источник
     * @throws AuthException
     */
    public static function throwIfSourceNotSupported(string $source)
    {
        Auth::throwIfNotSupportedSource($source);
    }

    /**
     * Создание гранта пароля.
     * @param int id пользователя
     * @param string пароль
     * @return static
     * @throws AuthException
     */
    public static function makeWithPassword(int $user_id, string $password)
    {
        static::throwIfSourceNotSupported('password');
        $grant = static::findWithPasswordByUserId($user_id);
        if ($grant) {
            throw AuthException::build('password_grant_already_exists');
        }
        $grant = new static([
            'user_id' => $user_id,
            'source' => 'password',
        ]);
        $grant->setPasswordHash($password);
        $grant->save();
        return $grant;
    }

    /**
     * Создание гранта входа по коду.
     * @param id пользователя
     * @param string источник получения кода
     * @return static
     */
    public static function makeWithCode(int $user_id, string $to)
    {
        static::throwIfSourceNotSupported('code');
        $grant = static::findWithCodeByUserId($user_id, $to);
        if (!$grant) {
            $grant = static::insert([
                'user_id' => $user_id,
                'source' => 'code',
                'source_key' => $to,
            ]);
        }
        return $grant;
    }

    /**
     * Создание внешнего гранта.
     * @param id пользователя
     * @param string источник
     * @param string ключ источника (id/email/something)
     * @return static
     */
    public static function makeForeign(int $user_id, string $source, string $source_key)
    {
        static::throwIfSourceNotSupported($source);
        // if ($grant = static::findForeign($source, $source_key)) {
        //     if ($grant->user_id != $user_id) {
        //         throw AuthException::build('foreign_grant_already_exists');
        //     }
        // }
        return static::insert(compact('user_id', 'source', 'source_key'));
    }

    /**
     * Поиск внешнего гранта по источнику и ключу.
     * @param string источник
     * @param string ключ источника
     * @return static|null
     */
    public static function findForeign(string $source, string $source_key): ?AuthGrant
    {
        return static::whereRowValues(
            ['source', 'source_key'],
            [$source, $source_key]
        )->one();
    }

    /**
     * Поиск грантов по id пользователя.
     * @param int id пользователя
     * @param ...string|null источники
     * @return array of static
     */
    public static function findByUserId(int $user_id, string ...$sources): array
    {
        $qb = static::where('user_id', $user_id);
        if (!empty($sources)) {
            $qb->whereIn('source', $sources);
        }
        return $qb->get();
    }

    /**
     * Поиск гранта пароля по id пользователя.
     * @param int id пользователя
     * @return static|null
     */
    public static function findWithPasswordByUserId(int $user_id): ?AuthGrant
    {
        return static::whereRowValues(
            ['source', 'user_id'],
            ['password', $user_id]
        )->one();
    }

    /**
     * Поиск гранта кода по id пользователя.
     * @param int id пользователя
     * @param string источник получения кода
     * @return static|null
     */
    public static function findWithCodeByUserId(int $user_id, string $to): ?AuthGrant
    {
        return static::whereRowValues(
            ['source', 'user_id', 'source_key'],
            ['code', $user_id, $to]
        )->one();
    }
}
