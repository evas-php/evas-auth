<?php
/**
 * @package evas-php\evas-auth
 */
namespace Evas\Auth;

use Evas\Auth\AuthAdapter;

/**
 * Класс исключений авторизации.
 * @author Egor Vayakin <egor@evas-php.com>
 * @since 3 Sep 2020
 */
class AuthException extends \Exception
{
    /**
     * Переопределяем конструктор.
     * @param string текст сообщения
     * @param int код исключения
     * @param \Throwable|null предыдущее исключение
     */
    public function __construct(string $message = '', int $code = 0, \Throwable $previous = null)
    {
        if (empty($message) && !empty($code)) {
            $message = AuthAdapter::ERRORS_MAP[$code] ?? '';
        }
        return parent::__construct($message, $code, $previous)
    }
}
