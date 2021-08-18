<?php
/**
 * Базовая абстрактная модель данных модуля авторизации.
 * @package evas-php\evas-auth
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Auth\Help;

use Evas\Auth\Auth;
use Evas\Db\Interfaces\DatabaseInterface;
use Evas\Orm\ActiveRecord;

abstract class Model extends ActiveRecord
{
     /**
     * Переопределяем получение имени таблицы.
     * @return string
     */
    public static function tableName(): string
    {
        return Auth::getModelTableName(static::class);
    }

    /**
     * Получение соединения с базой данных.
     * @param bool использовать ли соединение для записи
     * @return DatabaseInterface
     */
    public static function getDb(bool $write = false): DatabaseInterface
    {
        $dbname = static::getDbName($write);
        return Auth::getDb($dbname);
        // return Auth::getDb($write);
    }

    /**
     * Установка времени создания.
     */
    public function setCreateTime()
    {
        $this->create_time = date('Y-m-d H:i:s');
    }

    /**
     * Хук. Устанавливаем время создания модели при её вставке.
     */
    protected function beforeInsert()
    {
        $this->setCreateTime();
    }
}
