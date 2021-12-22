<?php

namespace App\Http\Controllers\V1;

use App\Contracts\Settings;
use App\Enums\UserResponseType;
use App\Events\AdviceRequestCreate;
use App\Factories\UserResponseFactory;
use App\Http\Controllers\Controller;
use App\Http\Response;
use App\Repositories\UserResponseRepository;
use Illuminate\Http\Request;
use Pasoonate\Pasoonate;

class PricesController extends Controller
{
    protected $userResponseRepository;

    public function __construct(UserResponseRepository $userResponseRepository)
    {
        parent::__construct();

        $this->userResponseRepository = $userResponseRepository;
    }

    public function getPlans(Request $request, Settings $settings): Response
    {
        $plans = $settings->value('account_packages');

        if (!empty($plans)) {
            $plans = json_decode($plans, true);
        }

        $user = $request->user();

        if (!empty($user)) {
            $cityId = $user->getCityId();

            if (!empty($cityId)) {
                $cityPlans = $settings->value('account_packages_city_' . $cityId);

                if (!empty($cityPlans)) {
                    $cityPlans = json_decode($cityPlans, true);

                    $plans = array_merge($plans, $cityPlans);
                }
            }
        }

        $data = [
            'plans' => $plans
        ];

        return new Response($data);
    }

    public function setPageView(Request $request): Response
    {
        $user = $request->user();
        $jalaliDate = Pasoonate::make(time(),)->jalali()->format('yyyy/MM/dd HH:mm');

        $userResponse = $this->userResponseRepository->getOneByTypeAndUserId(UserResponseType::PRICING_PAGEVIEW, $user->getId());

        if (is_null($userResponse)) {
            $userResponse = (new UserResponseFactory())->make(new \stdClass());
        }

        $userResponse->setType(UserResponseType::PRICING_PAGEVIEW);
        $userResponse->setUserId($user->getId());
        $userResponse->setData(null);
        $userResponse->setCreatedAt(time());
        $userResponse->setCreatedAtJalali($jalaliDate);
        $userResponse->setUser($user);

        if ($userResponse->getId()) {
            $this->userResponseRepository->update($userResponse);
        } else {
            $this->userResponseRepository->insert($userResponse);
        }

        event(new AdviceRequestCreate($userResponse));

        return new Response();
    }
}
