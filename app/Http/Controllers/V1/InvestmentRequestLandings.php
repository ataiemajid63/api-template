<?php

namespace App\Http\Controllers\V1;

use App\Entities\UserResponse;
use App\Enums\HttpStatusCode;
use App\Enums\InvestmentRequestLandingsTypes;
use App\Enums\UserResponseType;
use App\Events\InvestmentRequestCreate;
use App\Http\Controllers\Controller;
use App\Http\Response;
use App\Repositories\DistrictRepository;
use App\Repositories\UserResponseRepository;
use App\Rules\Mobile;
use Illuminate\Http\Request;
use Pasoonate\Pasoonate;

class InvestmentRequestLandings extends Controller
{
    public $userResponseRepository;
    public $distrcitRepository;

    public function __construct(UserResponseRepository $userResponseRepository, DistrictRepository $districtRepository)
    {
        parent::__construct();

        $this->userResponseRepository = $userResponseRepository;
        $this->distrcitRepository = $districtRepository;
    }

    public function __invoke(Request $request, $landing)
    {
        $validator = $this->makeValidator($request, [
            'full_name' => 'required',
            'mobile' => ['required', new Mobile()],
        ]);

        if ($validator->fails()) {
            $data = [
                'errors' => $validator->errors()
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        $user = $request->user();
        $jalaliDate = Pasoonate::make()->jalali()->format('yyyy/MM/dd HH:mm:ss');
        $description = [
            'نام' => $request->get('full_name'),
            'تلفن' => $request->get('mobile'),
        ];

        $userResponseType = '';

        if ($landing == InvestmentRequestLandingsTypes::REZA_TOWER) {
            $userResponseType = UserResponseType::INVEST_ON_MADINAT_AL_REZA_HOTEL;
        } elseif ($landing == InvestmentRequestLandingsTypes::HAYAAT) {
            $userResponseType = UserResponseType::INVEST_ON_HAYAAT;
        } else {
            $data = [
                'errors' => [
                    'landing' => 'validation.exist'
                ]
            ];
            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        $userResponse = new UserResponse();

        $userResponse->setId(null);
        $userResponse->setType($userResponseType);
        $userResponse->setUserId($user ? $user->getId() : null);
        $userResponse->setDescription(json_encode($description, JSON_UNESCAPED_UNICODE));
        $userResponse->setData(null);
        $userResponse->setCreatedAt(time());
        $userResponse->setCreatedAtJalali($jalaliDate);

        $userResponse = $this->userResponseRepository->insert($userResponse);

        event(new InvestmentRequestCreate($userResponse, $landing));

        return new Response();
    }
}
