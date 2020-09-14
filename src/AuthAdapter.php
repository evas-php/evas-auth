<?php
/**
 * @package evas-php/evas-auth
 */
namespace Evas\Auth;

use Evas\Auth\AuthException;
use Evas\Auth\Sources\Vk\VkController;
use Evas\Auth\Sources\Fb\FbController;
use Evas\Auth\Sources\Google\GoogleController;

/**
 * Контроллер авторизации.
 * @author Egor Vasyakin <egor@evas-php.com>
 * @since 3 Sep 2020
 */
class AuthAdapter
{
    protected static $sources = [
        'vk' => VkController::class,
        'fb' => FbController::class,
        'google' => GoogleController::class,
        'email' => EmailController::class,
        'phone' => PhoneController::class,
    ];

    public static function handle(string $path)
    {
        list($source, $action) = @explode('', $path);
        if (empty($action)) {
            return false;
        }
    }

    public static function run(string $source, string $action, array $params = null)
    {
        $controllerClass = static::$sources[$source] ?? null;
        if (empty($controllerClass)) {
            throw new AuthException("Обработчик авторизации через $source не поддерживается");
        }
        if (!class_exists($controllerClass, true)) {
            throw new AuthException("Обработчик авторизации через $source не найден");
        }
        $controller = new $controllerClass;
        $action .= 'Action';
        if (!method_exists($controller, $action)) {
            throw new AuthException("Действие $action обработчика авторизации через $source не найдено");
        }
        call_user_func([$controller, $action], $params);
    }
}
