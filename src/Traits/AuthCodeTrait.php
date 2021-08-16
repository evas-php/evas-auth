<?php
/**
 * Трейт аутентификации по коду.
 * @package evas-php\evas-auth
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Auth\Traits;

use Evas\Auth\Auth;
use Evas\Auth\AuthException;
use Evas\Auth\Interfaces\LoginUserInterface;
use Evas\Auth\Models\AuthConfirm;
use Evas\Auth\Models\AuthGrant;
use Evas\Auth\Validators\CheckCodeFieldset;
use Evas\Auth\Validators\GetCodeFieldset;

trait AuthCodeTrait
{
    /**
     * Валидация получения кода подтверждения.
     * @param array данные запроса
     * @return array
     */
    protected function validateGetCodeFieldset(array $payload = null): array
    {
        $fieldset = new GetCodeFieldset;
        $fieldset->throwIfNotValid($payload);
        $data = $fieldset->values;
        $type = $data['type'];
        $to = $data[$type];
        return [$data, $to, $type];
    }

    /**
     * Валидация проверки кода подтверждения.
     * @param array данные запроса
     * @return array
     */
    protected function validateCheckCodeFieldset(array $payload = null): array
    {
        $fieldset = new CheckCodeFieldset;
        $fieldset->throwIfNotValid($payload);
        $data = $fieldset->values;
        $type = $data['type'];
        $to = $data[$type];
        $code = $data['code'];
        return [$data, $to, $type, $code];
    }

    /**
     * Начало аутентификации по отправленному на телефон/email коду.
     * @param array|null данные запроса
     * @return string код подтверждения
     */
    protected function getCodeForLogin(array $payload = null): string
    {
        // 0. Проверяем поддержку входа по коду
        $this->throwIfNotSupportedSource('code');
        // 1. Валидируем payload с email/телефоном (?)
        list($data, $to, $type) = static::validateGetCodeFieldset($payload);

        // 2. Ищем пользователя с таким email/телефоном
        $userModel = $this->userModel();
        $user = $userModel::findByUniqueKeysFilled($to, $type);
        if (!$user) {
            // - если пользователь не найден, создаём пользователя
            $user = $userModel::insert($data);
        }
        // 3. Создаём AuthConfirm со сгенерированным кодом для пользователя
        $confirm = AuthConfirm::make($user->id, $to, $type);
        // 4. Возвращаем код подтверждения
        return (string) $confirm->code;
    }

    /**
     * Авторизаци по отправленному на телефон/email коду.
     * @param string тип
     * @param array|null данные запроса
     * @return LoginUserInterface
     * @throws AuthException
     */
    protected function loginByCode(array $payload = null): LoginUserInterface
    {
        // 0. Проверяем поддержку входа по коду
        $this->throwIfNotSupportedSource('code');
        // 1. Валидируем payload с кодом + email/телефон
        list($data, $to, $type, $code) = static::validateCheckCodeFieldset($payload);

        // 2. Ищем пользователя с таким email/телефоном
        $user = $this->userModel()::findByUniqueKeysFilled($to, $type);
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
        // 5. Создаем AuthGrant по адресу получения
        $grant = AuthGrant::makeWithCode($user->id, $to);
        // 6. Создаём или обновляем AuthSession пользователя
        $session = $this->makeSession($grant);
        // 7. Возвращаем пользователя
        return $user;
    }
}
