<?php
/**
 * @package evas-php/evas-auth
 */
namespace Evas\Auth\Helpers;

use Evas\Auth\AuthAdapter;
use Evas\Orm\Models\ActiveRecord;

/**
 * Абстрактный класс модели данных.
 * @author Egor Vasyakin <egor@evas-php.com>
 * @since 14 Sep 2020
 */
abstract class Model extends ActiveRecord
{
    /**
     * Переопределяем получение имени таблицы.
     * @return string
     */
    public static function tableName(): string
    {
        return AuthAdapter::getModelTableName(static::class);
    }

    /**
     * Получение соединения с базой данных.
     * @return Database
     */
    public static function getDb()
    {
        return AuthAdapter::getDb();
    }
}
