<?php
/**
 * Валидатор пароля по умолчанию.
 * @package evas-php\evas-auth
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Auth\Validators;

use Evas\Auth\Auth;
use Evas\Validate\Field;

class PasswordField extends Field
{
    public $min = 6;
    public $max = 30;

    /**
     * Хук до инициализации валидатора поля.
     */
    public function beforeCreate()
    {
        $this->label = Auth::config()['password_label'];
    }
}
