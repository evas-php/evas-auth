<?php
/**
 * Трейт пользователя для авторизации.
 * @package evas-php\evas-auth
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Auth\Models;

trait LoginUserTrait
{
    /**
     * Добавление пользователя по внешней авторизации.
     * @param string источник
     * @param array данные пользователя
     */
    public static function insertByForeign(string $source, array $data)
    {
        return static::insert($data);
    }

    /**
     * Добавление пользователя по паролю.
     * @param array данные пользователя
     */
    public static function insertByPassword(array $data)
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
     * Получение уникальных ключей пользователя для авторизации по паролю.
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
    public static function findByUniqueKeys(array $data)
    {
        $where = $props = [];
        foreach (static::uniqueKeys() as &$key) {
            if (isset($data[$key])) {
                $where[] = "$key = ?";
                $props[] = $data[$key];
            }
        }
        if (empty($props)) return null;
        $where = implode(' OR ', $where);
        return static::find()->where($where, $props)->one()
        ->classObject(static::class);
    }
}
