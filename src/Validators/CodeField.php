<?php
/**
 * Валидатор проверочного кода.
 * @package evas-php\evas-auth
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Auth\Validators;

use Evas\Auth\Auth;
use Evas\Validate\Fields\IntField;

class CodeField extends IntField
{
    /**
     * Хук после инициализации валидатора поля.
     */
    public function afterCreate()
    {
        $this->min = Auth::config()['code_length'];
        $this->max = Auth::config()['code_length'];
    }
}
