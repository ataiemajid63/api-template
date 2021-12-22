<?php

namespace App\Http\Controllers\V1;

use App\Enums\HttpStatusCode;
use App\Enums\UserResponseType;
use App\Events\AdviceRequestCreate;
use App\Factories\UserResponseFactory;
use App\Http\Controllers\Controller;
use App\Http\Response;
use App\Repositories\DistrictRepository;
use App\Repositories\UserResponseRepository;
use Illuminate\Http\Request;
use Pasoonate\Pasoonate;
use stdClass;

class AdviceRequest extends Controller
{
    public $userResponseRepository;
    public $distrcitRepository;

    public function __construct(UserResponseRepository $userResponseRepository, DistrictRepository $districtRepository)
    {
        parent::__construct();

        $this->userResponseRepository = $userResponseRepository;
        $this->distrcitRepository = $districtRepository;
    }

    public function __invoke(Request $request)
    {
        $validator = $this->makeValidator($request, [
            'advice_page' => '',
            'description' => '',
        ]);

        if($validator->fails()) {
            $data = [
                'errors' => $validator->errors()
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        $user = $request->user();
        $description = $request->input('description', null);
        $advicePage = $request->input('advice_page', null);
        $jalaliDate = Pasoonate::make(time(), )->jalali()->format('yyyy/MM/dd HH:mm');

        $advice = [];

        if(!empty($description)) {
            $advice[] = trans_choice('advice_request.desription', (int)empty($advicePage), compact('description', 'advicePage', 'jalaliDate'), 'fa');
        }

        if(!empty($advicePage)) {
            $advice[] = trans('advice_request.advice_page', compact('advicePage', 'jalaliDate'), 'fa');
        }

        $userResponse = $this->userResponseRepository->getOneByTypeAndUserId(UserResponseType::ADVICE_REQUEST, $user->getId());

        if($userResponse && $userResponse->getData()) {
            $data = json_decode($userResponse->getData(), true);
            $advice = array_merge($data, $advice);
        }

        if(is_null($userResponse)) {
            $userResponse = (new UserResponseFactory())->make(new stdClass());
        }

        $userResponse->setType(UserResponseType::ADVICE_REQUEST);
        $userResponse->setUserId($user->getId());
        $userResponse->setData(empty($advice) ? null : json_encode($advice, JSON_UNESCAPED_UNICODE));
        $userResponse->setCreatedAt(time());
        $userResponse->setCreatedAtJalali($jalaliDate);
        $userResponse->setUser($user);

        if($userResponse->getId()) {
            $this->userResponseRepository->update($userResponse);
        }
        else {
            $this->userResponseRepository->insert($userResponse);
        }

        event(new AdviceRequestCreate($userResponse));

        return new Response();
    }
}
