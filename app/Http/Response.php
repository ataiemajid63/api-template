<?php

namespace App\Http;

use Illuminate\Http\JsonResponse;

class Response extends JsonResponse
{
    public function __construct($data = null, $status = 200, $headers = [], $options = 0)
    {
        parent::__construct($data, $status, $headers, $options);
    }
}
