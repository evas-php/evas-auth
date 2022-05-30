<?php
/**
 * Трейт пользователя для аутентификации.
 * @package evas-php\evas-auth
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Auth\Models;

use Evas\Auth\Interfaces\LoginUserInterface;

trait LoginUserTrait
{
    /**
     * Добавление пользователя по внешней аутентификации.
     * @param string источник
     * @param array данные пользователя
     * @return static
     */
    public static function insertByForeign(string $source, array $data): LoginUserInterface
    {
        $user = new static;
        $user->setForeignData($source, $data);
        $user->save();
        return $user;
    }

    /**
     * Установка данных пользователя, полученных из внешней аутентификации.
     * @param string источник
     * @param array данные пользователя
     * @return self
     */
    public function setForeignData(string $source, array $data): LoginUserInterface
    {
        foreach ($data as $name => &$value) {
            if (!(is_string($value) || is_numeric($value) || is_null($value))) {
                $value = json_encode($value);
            }
        }
        $this->fill($data);
        $this->save();
        return $this;
    }

    /**
     * Добавление пользователя по паролю.
     * @param array данные пользователя
     * @return static
     */
    public static function insertByPassword(array $data): LoginUserInterface
    {
        return static::insert($data);
    }

    /** @static array маппинг уникальных ключей пользователя с их именами*/
    public static $uniqueKeysWithLabels = [
        'email' => 'Email', 
        'phone' => 'Телефон', 
        'login' => 'Логин',
    ];

    /**
     * Получение уникальных ключей пользователя для аутентификации по паролю.
     * @return array
     */
    public static function uniqueKeys(): array
    {
        static $keys = null;
        if (null === $keys) {
            // $keys = ['email', 'phone', 'login'];
            $keys = array_keys(static::$uniqueKeysWithLabels);
            $modelKeys = static::getDb()->table(static::tableName())->columns();
            $keys = array_filter($keys, function ($key) use (&$modelKeys) {
                return in_array($key, $modelKeys);
            });
        }
        return $keys;
    }

    /**
     * Получение имени уникального ключа пользователя.
     * @param string ключ
     * @return string|null имя ключа
     */
    public static function getUniqueKeyLabel(string $name): ?string
    {
        return static::$uniqueKeysWithLabels[$name] ?? null;
    }

    /**
     * Поиск записи по уникальным ключам для регистрации или входа по паролю.
     * @param array данные
     * @return static|null
     */
    public static function findByUniqueKeys(array $data): ?LoginUserInterface
    {
        // $where = $props = [];
        $qb = null;
        foreach (static::uniqueKeys() as &$key) {
            if (isset($data[$key])) {
                // $where[] = "$key = ?";
                // $props[] = $data[$key];
                if (!$qb) $qb = static::where($key, $data[$key]);
                else $qb = $qb->orWhere($key, $data[$key]);
            }
        }
        // if (empty($props)) return null;
        // $where = implode(' OR ', $where);
        return $qb ? $qb->one() : null;
    }

    /**
     * Поиск записи по уникальным ключам с одинаковым значением для входа.
     * @param mixed значение оиска
     * @param string|null имя ключа поиска
     * @return static|null
     */
    public static function findByUniqueKeysFilled($value, string $name = null): ?LoginUserInterface
    {
        if ($name && in_array($name, static::uniqueKeys())) {
            $data = [$name => $value];
        } else {
            $data = array_fill_keys(static::uniqueKeys(), $value);
        }
        return static::findByUniqueKeys($data);
    }
}
