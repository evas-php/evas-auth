<?php
/**
 * Хелпер генерации токенов.
 * @package evas-php\evas-auth
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Auth\Help;

use Evas\Auth\Auth;
use Evas\Auth\AuthException;
use Evas\Base\App;

class Token
{
    /** @static string символы токена */
    const TOKEN_SYMBOLS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

    /** @static int длина токена по умолчанию */
    const DEFAULT_TOKEN_LENGTH = 30;

    /** @static int максимальное количество попыток проверки уникального токена по умолчанию */
    const DEFAULT_CHECK_UNIQUE_MAX_TRY_COUNT = 20;

    /**
     * Получение максимального количества проверок токена на уникальность.
     * @return int
     */
    public static function getMaxTryCount(): int
    {
        $maxTryCount = Auth::config()['token_generate_max_tries'] ?? null;
        if (empty($maxTryCount)) $maxTryCount = static::DEFAULT_CHECK_UNIQUE_MAX_TRY_COUNT;
        return $maxTryCount;
    }

    /**
     * Генерация токена.
     * @param int|null длина токена
     * @return string токен
     */
    public static function generate(int $length = null): string
    {
        static $symLen = null;
        if (null === $symLen) $symLen = mb_strlen(static::TOKEN_SYMBOLS);
        if (!$length) $length = static::DEFAULT_TOKEN_LENGTH;

        $token = '';
        for ($i = 0; $i < $length; $i++) {
            $token .= static::TOKEN_SYMBOLS[mt_rand(1, $symLen) - 1];
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
    public static function generateUniqueIn(string $tbl, int $length = null): string
    {
        static $maxTryCount = null;
        if (null === $maxTryCount) {
            $maxTryCount = static::getMaxTryCount();
        }
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
        for ($try = 0; $try < $maxTryCount; $try++) {
            $token = static::generate($length);
            if (!App::db()->select($tbl)->where("$field = ?", [$token])->one()->rowCount()) {
                break;
            }
        }
        if ($try >= $maxTryCount) {
            throw AuthException::build('token_exceeed_max_try_generated');
        }
        return $token;
    }
}
