<?php
/**
 * Авторизация по коду.
 * @package evas-php\evas-auth
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Auth\Actions;

use Evas\Auth\Auth;
use Evas\Auth\AuthException;
use Evas\Auth\Interfaces\LoginUserInterface;
use Evas\Auth\Models\AuthConfirm;
use Evas\Auth\Models\AuthGrant;
use Evas\Auth\Validators\CodeFieldset;
use Evas\Auth\Validators\CodeInitFieldset;

class CodeAuth
{
    /**
     * Начало авторизации по отправленному на телефон/email коду.
     * @param array|null данные запроса
     * @return string код подтверждения
     */
    public static function getCode(array $payload = null): string
    {
        // 0. Проверяем поддержку входа по коду
        Auth::throwIfNotSupportedSource('code');
        // 1. Валидируем payload с email/телефоном (?)
        
        $fieldset = new CodeInitFieldset;
        $fieldset->throwIfNotValid($payload ?? []);
        $data = $fieldset->values;
        $type = $data['type'];
        $to = $data[$type];

        // return json_encode(json_encode($data));
        // return json_encode([$to, $type]);

        // 2. Ищем пользователя с таким email/телефоном
        $userModel = Auth::userModel();
        $user = $userModel::findByUniqueKeysFilled($to, $type);
        if (!$user) {
            // - если пользователь не найден, создаём пользователя
            $user = $userModel::insert($data);
        }
        // $grant = AuthGrant::makeWithCode($user->id, $to);
        // 3. Создаём AuthConfirm со сгенерированным кодом для пользователя
        $confirm = AuthConfirm::make($user->id, $to, $type);
        // 4. Возвращаем код подтверждения
        return (string) $confirm->code;
    }

    /**
     * Авторизаци по отправленному на телефон/email коду.
     * @param string тип
     * @param array|null данные запроса
     * @return LoginUserInterface|null
     */
    public static function login(array $payload = null): ?LoginUserInterface
    {
        // 0. Проверяем поддержку входа по коду
        Auth::throwIfNotSupportedSource('code');
        // 1. Валидируем payload с кодом + email/телефон

        $fieldset = new CodeFieldset;
        $fieldset->throwIfNotValid($payload ?? []);
        $data = $fieldset->values;
        $type = $data['type'];
        $to = $data[$type];
        $code = $data['code'];

        // 2. Ищем пользователя с таким email/телефоном
        $user = Auth::userModel()::findByUniqueKeysFilled($to, $type);
        if (!$user) {
            // - ошибка, если пользователь не найден
            throw AuthException::build('user_not_found');
        }
        // 3. Ищем AuthConfirm с таким кодом для этого пользователя
        $confirm = AuthConfirm::findByUserIdAndCode($user->id, $code);
        if (!$confirm) {
            // - ошибка, если AuthConfirm не найден
            throw AuthException::build('code_is_not_active');
        }
        // 4. Подтверждаем AuthConfirm
        $confirm->complete();
        // 5. Создаем AuthGrant по источнику получения
        $grant = AuthGrant::makeWithCode($user->id, $to);
        // 6. Создаём или обновляем AuthSession пользователя
        $session = Auth::makeSession($grant);
        // 7. Возвращаем пользователя
        return $user;
    }
}
