<?php
/**
 * @package evas-php\evas-auth
 */
namespace Evas\Auth\Helpers;

use Evas\Auth\AuthException;
use Evas\Base\App;

/**
 * Класс конфига api.
 * @author Egor Vasyakin <egor@evas-php.com>
 * @since 7 Sep 2020
 */
class ApiConfig
{
    /**
     * @var string путь к конфигу
     */
    public $filepath;

    /**
     * @var array данные конфига
     */
    protected $data = [];

    /**
     * @var bool была ли попытка загрузки конфига
     */
    public $loaded = false;

    /**
     * @var bool игнорирование несуществующих значений
     */
    public $ignoreUndefined = true;

    /**
     * Конструктор.
     * @param string имя конфига
     * @param array|null данные конфига по умолчанию
     * @throws AuthException
     */
    public function __construct(string $filepath, array $default = null)
    {
        $this->filepath = $filepath;
        $this->load();
        if (!empty($default)) {
            $this->data = array_merge($default, $this->data);
        }
    }

    /**
     * Загрузка конфига.
     * @throws AuthException
     */
    public function load()
    {
        if (false === $this->loaded) {
            $data = App::loadByApp($this->filepath);
            if (empty($data)) {
                throw new AuthException("Config \"$this->filepath\" not found");
            }
            if (!is_array($data)) {
                throw new AuthException(sprintf("Config \"$this-filepath\" data must be type of array, %s given"), gettype($data));
            }
            $this->data = $data;
            $this->loaded = true;
        }
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
                throw new AuthException("Config \"$this->filepath\" not has property \"$name\"");
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
