<?php
/**
 * Валидатор проверки кода.
 * @package evas-php\evas-auth
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Auth\Validators;

use Evas\Auth\Validators\CodeField;
use Evas\Auth\Validators\GetCodeFieldset;

class CheckCodeFieldset extends GetCodeFieldset
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
