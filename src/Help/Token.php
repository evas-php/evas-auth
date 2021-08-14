<?php
/**
 * Хелпер генерации токенов.
 * @package evas-php\evas-auth
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Auth\Help;

use Evas\Auth\Auth;
use Evas\Auth\AuthException;

class Token
{
    /** @var string символы доступные в токене */
    public $symbols = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    /** @var int длина генерируемого токена */
    public $token_length = 30;
    /** @var int максимальное количество проверки сгенерированного токена на уникальность */
    public $token_generate_max_tries = 20;

    /**
     * Конструктор.
     * @param array|null параметры
     */
    public function __construct(array $props = null)
    {
        if ($props) foreach ($props as $name => $value) {
            $this->$name = $value;
        }
    }

    /**
     * Генерация токена.
     * @param int|null длина токена
     * @return string токен
     */
    public function generate(int $length = null): string
    {
        $symLen = mb_strlen($this->symbols);
        if (!$length) $length = $this->token_length;
        $token = '';
        for ($i = 0; $i < $length; $i++) {
            $token .= $this->symbols[mt_rand(1, $symLen) - 1];
        }
        return $token;
    }

    /**
     * Генерация уникального токена для поля таблицы.
     * @param string|array 'имя таблицы' или ['имя таблицы', 'имя столбца']
     * @param int|null длина токена
     * @return string токен
     * @throws \InvalidArgumentException
     * @throws AuthException
     */
    public function generateUniqueIn($tbl, int $length = null): string
    {
        if (is_array($tbl) && 2 == count($tbl)) {
            list($tbl, $field) = $tbl;
        } else if (is_string($tbl)) {
            $field = 'token';
        } else {
            throw new \InvalidArgumentException(sprintf(
                'Argument 1 passed to %s() must be a string or an array, %s given',
                __METHOD__, gettype($tbl)
            ));
            
        }
        for ($try = 0; $try < $this->token_generate_max_tries; $try++) {
            $token = $this->generate($length);
            if (!Auth::getDb()->select($tbl)->where("$field = ?", [$token])->one()->rowCount()) {
                break;
            }
        }
        if ($try >= $this->token_generate_max_tries) {
            throw AuthException::build('token_exceeed_max_try_generated');
        }
        return $token;
    }
}
