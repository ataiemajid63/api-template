<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    protected $validator = null;

    public function __construct()
    {

    }

    /**
     * @param Request $request
     * @param array $rules
     * @param array $messages
     * @param array $customAttributes
     * @return Validator
     */
    public function makeValidator(Request $request, array $rules, array $messages = [], array $customAttributes = [])
    {
        return app('validator')->make($request->all(), $rules, $messages, $customAttributes);
    }
}
