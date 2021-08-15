<?php
/**
 * Валидатор получения кода.
 * @package evas-php\evas-auth
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Auth\Validators;

use Evas\Auth\Validators\EmailOrPhoneField;
use Evas\Validate\Fieldset;

class GetCodeFieldset extends Fieldset
{
    /**
     * Предустанавливаем поле адреса получения.
     * @return array|null предустановленный набор полей
     */
    public function presetFields(): ?array
    {
        return [
            'to' => new EmailOrPhoneField,
        ];
    }

    /**
     * Хук после валидации.
     */
    public function afterValidate()
    {
        $to = $this->getField('to')->value;
        $type = $this->getField('to')->valueType;
        unset($this->values['to']);
        $this->values[$type] = $to;
        $this->values['type'] = $type;
    }
}
