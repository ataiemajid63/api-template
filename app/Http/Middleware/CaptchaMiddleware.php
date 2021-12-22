<?php

namespace App\Http\Middleware;

use App\Entities\Captcha;
use App\Enums\HttpStatusCode;
use App\Http\Response;
use App\Repositories\CaptchaRepository;
use App\UseCases\CaptchaMaker;
use DateTime;
use Illuminate\Http\Request;

class CaptchaMiddleware
{

    private $captchaRepository;
    private $captchaMaker;

    /**
     * Create a new middleware instance.
     */
    public function __construct(CaptchaRepository $captchaRepository, CaptchaMaker $captchaMaker)
    {
        $this->captchaRepository = $captchaRepository;
        $this->captchaMaker = $captchaMaker;
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
        $route = $request->path();
        $ip = $request->ip();

        $captcha = $this->captchaRepository->getOneByRouteAndIP($route, $ip);

        if($captcha) {
            $captcha->setTries($captcha->getTries() + 1);
            $captcha->setDecaysAt((new DateTime())->modify('+5 minutes'));

            if($captcha->getRemaining() > 1) {
                $this->captchaRepository->update($captcha);

                return $next($request);
            }

            if($captcha->getRemaining() == 1) {
                $response = $next($request);
                $statusCode = $response->getStatusCode();
                $data = $response->getData(true) ?? [];

                if($this->captchaRepository->getOneByRouteAndIP($route, $ip)) {
                    $token = $captcha->generateToken();

                    $captcha->setToken($token);
                    $data['captcha_token'] = $token;

                    $this->captchaRepository->update($captcha);

                    return new Response($data, $statusCode);
                }

                return $response;
            }

            if($captcha->getRemaining() < 1) {
                $checkPassed = $this->captchaMaker->check($request->get('captcha_code', '0'), $captcha->getCode());

                if($checkPassed) {
                    $this->captchaRepository->deleteByRouteAndIP($route, $ip);

                    return $next($request);
                }

                $token = $captcha->generateToken();

                $captcha->setToken($token);
                $data = ['captcha_token' => $token];

                $this->captchaRepository->update($captcha);

                return new Response($data, HttpStatusCode::TO_MANY_REQUEST);
            }
        }

        $captcha = new Captcha();

        $captcha->setRoute($route);
        $captcha->setIp($ip);
        $captcha->setCode(null);
        $captcha->setToken(null);
        $captcha->setLimit(3);
        $captcha->setTries(1);
        $captcha->setDecaysAt((new DateTime())->modify('+5 minutes'));

        $this->captchaRepository->insert($captcha);

        return $next($request);
    }
}
