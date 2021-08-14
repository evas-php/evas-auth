<?php
/**
 * Валидатор поля email или телефон.
 * @package evas-php\evas-auth
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Auth\Validators;

use Evas\Validate\Field;
use Evas\Validate\Fields\EmailField;
use Evas\Validate\Fields\PhoneField;

class EmailOrPhoneField extends Field
{
    public $label = 'Email or Phone';
    
    /** @var string тип значение (email|phone) */
    public $valueType;

    /**
     * Переопределяем проверку значения на валидность полю.
     * @param mixed значение
     * @param bool пришло ли поле
     * @return bool
     */
    public function isValid($value, $isset = true): bool
    {
        if (($field = new EmailField)->isValid($value)) {
            $this->valueType = 'email';
            $this->value = $field->value;
            return true;
        } else if (($field = new PhoneField)->isValid($value)) {
            $this->valueType = 'phone';
            $this->value = $field->value;
            return true;
        }
        $this->value = $value;
        $this->buildError('pattern');
        return false;
    }
}
