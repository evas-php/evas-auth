<?php
/**
 * Интерфейс пользователя для авторизации.
 * @package evas-php\evas-auth
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Auth\Interfaces;

interface LoginUserInterface
{
    /**
     * Добавление пользователя по внешней авторизации.
     * @param string источник
     * @param array данные пользователя
     * @return static
     */
    public static function insertByForeign(string $source, array $data): LoginUserInterface;

    /**
     * Установка данных пользователя, полученных из внешней авторизации.
     * @param string источник
     * @param array данные пользователя
     * @return self
     */
    public function setForeignData(string $source, array $data): LoginUserInterface;

    /**
     * Добавление пользователя по паролю.
     * @param array данные пользователя
     * @return static
     */
    public static function insertByPassword(array $data): LoginUserInterface;

    /**
     * Валидация для входа по паролю.
     * @param array данные запроса
     * @return aray отвалидированные данные
     */
    public static function validateLogin(array $payload): array;

    /**
     * Валидация для регистрации по паролю.
     * @param array данные запроса
     * @return aray отвалидированные данные
     */
    public static function validateRegistration(array $payload): array;

    /**
     * Получение уникальных ключей пользователя для авторизации по паролю.
     * @return array
     */
    public static function uniqueKeys(): array;

    /**
     * Получение имени уникального ключа пользователя.
     * @param string ключ
     * @return string|null имя ключа
     */
    public static function getUniqueKeyLabel(string $name): ?string;

    /**
     * Поиск записи по уникальным ключам для регистрации или входа по паролю.
     * @param array данные
     * @return static|null
     */
    public static function findByUniqueKeys(array $data): ?LoginUserInterface;
}
