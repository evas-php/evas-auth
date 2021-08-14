<?php
/**
 * Валидатор набора полей для проверки кода и адреса получения.
 * @package evas-php\evas-auth
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Auth\Validators;

use Evas\Auth\Validators\CodeField;
use Evas\Auth\Validators\CodeInitFieldset;

class CodeFieldset extends CodeInitFieldset
{
    /**
     * Предустанавливаем к полю email или телефон поле кода.
     * @return array|null
     */
    public function presetFields(): ?array
    {
        return array_merge(parent::presetFields(), [
            'code' => new CodeField
        ]);
    }
}
