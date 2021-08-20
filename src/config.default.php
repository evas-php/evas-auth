<?php
/**
 * Дефолтный конфиг модуля аутентификации.
 * @package evas-php\evas-auth
 * @author Egor Vasyakin <egor@evas-php.com>
 */

use Evas\Auth\Models\AuthConfirm;
use Evas\Auth\Models\AuthGrant;
use Evas\Auth\Models\AuthRecovery;
use Evas\Auth\Models\AuthSession;

use Evas\Auth\Sources\FbOauth;
use Evas\Auth\Sources\GoogleOauth;
use Evas\Auth\Sources\VkOauth;

use Evas\Base\App;

use Evas\Auth\Validators\PasswordField;

return [
    'errors' => [
        'auth_not_supported' => 'Способ аутентификации через %s не поддерживается',
        'oauth_handler_not_setted' => 'Обработчик аутентификации через %s не установлен',
        'oauth_config_not_setted' => 'Конфиг аутентификации через %s не установлен',
        'user_not_found' => 'Пользователь не найден',
        'user_fail_password' => 'Неверный логин или пароль',
        'user_already_exists' => 'Пользователь уже существует. %s занят',
        'grant_not_found' => 'Способ входа не найден',
        'confirm_not_found' => 'Способ входа не найден или уже подтверждён',
        'recovery_not_found' => 'Восстановление доступа с таким кодом не найдено',
        'oauth_code_empty' => '%s не передал ключ доступа',
        'oauth_empty_access' => '%s не передал права доступа',
        'oauth_error_response' => '%s ответил ошибкой: %s',
        'oauth_empty_user_data' => '%s не передал данные пользователя',
        'oauth_fetch_url_error' => 'Не удаётся получить данные по url: %s',
        'model_table_not_exists' => 'Имя таблицы модели данных %s не установлено',
        'token_exceeed_max_try_generated' => 'Превышено максимальное количество попыток проверки на уникальность токена',
        'incorrect_password_old' => 'Старый пароль не подходит',
        'password_grant_not_found' => 'Вход по паролю не установлен для пользователя',
        'password_grant_already_exists' => 'Аутентификация по паролю для пользователя уже установлена',
        'incorrect_password_old' => 'Старый пароль введён неверно',
        'code_is_not_active' => 'Код подтверждения не активен',
        'code_is_outdated' => 'Код подтверждения устарел',
        'invalid_db' => 'Ошибка установки базы данных',
    ],
    'auth_grant_status_string_format' => 'Вход через %s %s',
    // 'auth_grant_statuses' => [
    //     AuthGrant::STATUS_UNKNOWN => 'с неизвестным статусом',
    //     AuthGrant::STATUS_INIT => 'не подтвержден',
    //     AuthGrant::STATUS_CONFIRMED => 'подвержден',
    //     AuthGrant::STATUS_UNACTIVE => 'выключен',
    //     AuthGrant::STATUS_OUTDATED => 'необходимо обновить',
    // ],
    'models_to_tables' => [
        AuthGrant::class => 'auth_grant',
        AuthSession::class => 'auth_session',
        AuthConfirm::class => 'auth_confirm',
        AuthRecovery::class => 'auth_recovery',
    ],
    'password_enable' => true,
    'new_password_field' => PasswordField::class,
    'password_label' => 'Пароль',
    'new_password_label' => 'Новый пароль',
    'new_password_repeat_label' => 'Повторите новый пароль',
    'code_enable' => true,
    'foreignClasses' => [
        'fb' => FbOauth::class,
        'google' => GoogleOauth::class,
        'vk' => VkOauth::class,
    ],
    'foreigns' => [
        // 'fb' => [],
        // 'google' => [],
        // 'vk' => [],
    ],
    // 'user_models' => [],
    'token_length' => 30,
    'token_cookie_name' => 'token',
    'token_alive' => 2592000,
    'token_generate_max_tries' => 20,
    'code_length' => 4,
    'code_alive' => 3600,
    'db' => function (string $dbname = null) {
        return App::db($dbname);
    },
];
