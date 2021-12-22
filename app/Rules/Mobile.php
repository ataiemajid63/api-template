<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class Mobile implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $pattern = '/(^(09)[0-9]{9}$)|(^(00989)[0-9]{9}$)|(^(\+989)[0-9]{9}$)/';

        if(preg_match($pattern, $value) && mb_strlen($value) <= 14) {
            return true;
        }

        return false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return trans('validation.mobile');
    }
}
