<?php
/**
 * @package evas-php\evas-auth
 */
namespace Evas\Auth\Helpers;

use Evas\Auth\AuthException;
use Evas\Base\App;
use Evas\Base\Helpers\PhpHelper;

/**
 * Класс конфига.
 * @author Egor Vasyakin <egor@evas-php.com>
 * @since 7 Sep 2020
 */
class Config
{
    /**
     * @var array данные конфига
     */
    protected $data = [];

    /**
     * @var bool игнорирование несуществующих значений
     */
    public $ignoreUndefined = true;

    /**
     * Конструктор.
     * @param string|null путь к конфигу
     * @param array|null данные конфига по умолчанию
     * @throws AuthException
     */
    public function __construct(string $filename = null, array $default = null)
    {
        if (!empty($default)) $this->mergeData($default);
        if (!empty($filename)) $this->load($filename);
    }

    /**
     * Склеивание данных.
     * @param array данные
     * @return self
     */
    public function mergeData(array $data)
    {
        foreach ($data as $key => &$value) {
            if (PhpHelper::isAssoc($value)) {
                $before = $this->data[$key] ?? [];
                $value = array_merge($before, $value);
            }
        }
        $this->data = array_merge($this->data, $data);
        return $this;
    }

    /**
     * Загрузка конфига.
     * @param string путь к конфигу
     * @throws AuthException
     * @return self
     */
    public function load(string $filename)
    {
        $data = App::loadByApp($filename);
        if (empty($data)) {
            throw new AuthException("Config \"$filename\" not found");
        }
        if (!PhpHelper::isAssoc($data)) {
            throw new AuthException(sprintf("Config \"$filename\" data must be type of assoc array, %s given"), 
                gettype($data));
        }
        return $this->mergeData($data);
    }

    /**
     * Отключить игнорирование несутствующих значений.
     * @return self
     */
    public function unignoreUndefined(): ApiConfig
    {
        $this->ignoreUndefined = false;
        return $this;
    }

    /**
     * Получение свойства конфига.
     * @param string имя свойства
     * @throws AuthException
     * @return mixed
     */
    public function get(string $name)
    {
        if (!isset($this->data[$name])) {
            if (false === $this->ignoreUndefined) {
                throw new AuthException("Config \"$filename\" not has property \"$name\"");
            }
        }
        return $this->data[$name] ?? null;
    }

    /**
     * Получение свойств конфига.
     * @param array массив имен свойств
     * @throws AuthException
     * @return array маппинг свойств
     */
    public function getList(array $names = null): array
    {
        if (!empty($names)) {
            $data = [];
            foreach ($names as &$name) {
                $data[$name] = $this->get($name);
            }
            return $data;
        } else {
            return $this->data;
        }
    }
}
