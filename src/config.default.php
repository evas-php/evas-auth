<?php
/**
 * Дефолтный конфиг модуля авторизации.
 * @author Egor Vasyakin <egor@evas-php.com>
 * @since 16 Sep 2020
 */

use Evas\Auth\AuthAdapter as AA;
use Evas\Auth\Models\AuthGrant;
use Evas\Auth\Models\AuthGrantConfirm;
use Evas\Auth\Models\AuthGrantRecovery;
use Evas\Auth\Models\AuthSession;
use Evas\Auth\Sources\Email\EmailController;
use Evas\Auth\Sources\Google\GoogleController;
use Evas\Auth\Sources\Fb\FbController;
use Evas\Auth\Sources\Phone\PhoneController;
use Evas\Auth\Sources\Vk\VkController;
use Evas\Base\App;

return [
    'errors_map' => [
        AA::ERROR_SOURCE_CONTROLLER_NOT_SUPPORTED => 'Обработчик авторизации через %s не поддерживается',
        AA::ERROR_SOURCE_CONTROLLER_NOT_FOUND => 'Обработчик авторизации через %s не найден',
        AA::ERROR_SOURCE_CONTROLLER_ACTION_NOT_FOUND => 'Действие %s обработчика авторизации через %s не найдено',
        AA::ERROR_MODEL_TABLE_NOT_FOUND => 'Имя таблицы модели данных %s не найдено',
        AA::ERROR_USER_NOT_FOUND => 'Пользователь не найден',
        AA::ERROR_USER_FAIL_PASSWORD => 'Неверный пароль',
        AA::ERROR_VALIDATOR => 'Неверное имя пользователя/пароль',
        AA::ERROR_USER_ALREADY_EXISTS => 'Пользователь уже существует',
        AA::ERROR_AUTH_GRANT_NOT_FOUND => 'Способ входа не найден',
        AA::ERROR_AUTH_GRANT_CONFIRM_NOT_FOUND => 'Способ входа не найден или уже подтвержден',
        AA::ERROR_AUTH_GRANT_RECOVERY_NOT_FOUND => 'Восстановление доступа с таким кодом не найдено',
    ],
    'auth_grant_status_string_format' => 'Вход через %s %s',
    'auth_grant_statuses_map' => [
        AuthGrant::STATUS_UNKNOWN => 'с неизвестным статусом',
        AuthGrant::STATUS_INIT => 'не подтвержден',
        AuthGrant::STATUS_CONFIRMED => 'подвержден',
        AuthGrant::STATUS_UNACTIVE => 'выключен',
        AuthGrant::STATUS_OUTDATED => 'необходимо обновить',
    ],
    'models_tables' => [
        AuthGrant::class => 'auth_grants',
        AuthSession::class => 'auth_sessions',
        AuthGrantConfirm::class => 'auth_grant_confirm',
        AuthGrantRecovery::class => 'auth_grant_recovery',
    ],
    'sources' => [
        'vk' => VkController::class,
        'facebook' => FbController::class,
        'google' => GoogleController::class,
        'email' => EmailController::class,
        'phone' => PhoneController::class,
    ],
    'supported_sources' => ['vk', 'facebook', 'google', 'email', 'phone'],
    'auth_token_cookie_name' => 'token',
    'auth_token_alive' => 2592000,
    'database' => function () {
        return App::getDb();
    },
];
