<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;

class ConvertDigit
{
    public function __construct()
    {

    }

    /**
     * Handle an incoming request.
     *
     * @param  Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle(Request $request, \Closure $next)
    {
        $input = $request->input();
        $result = $this->replaceFarsiToEnglishDigit($input);

        $request->replace($result);

        return $next($request);
    }

    /**
     * @param array $input
     *
     * @return array
     */
    public function replaceFarsiToEnglishDigit(array $input)
    {
        $farsiDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $englishDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        $result = array_map(function ($item) use ($farsiDigits, $englishDigits) {
            return str_replace($farsiDigits, $englishDigits, $item);
        }, $input);

        return $result;
    }
}
