<?php

namespace App\Http\Controllers\V1;

use App\Entities\InvestApplicant;
use App\Enums\HttpStatusCode;
use App\Enums\InvestApplicantMode;
use App\Enums\InvestApplicantSource;
use App\Enums\InvestApplicantStatus;
use App\Events\InvestmentApplicantCreate;
use App\Http\Controllers\Controller;
use App\Http\Response;
use App\Repositories\DistrictRepository;
use App\Repositories\InvestApplicantRepository;
use App\Rules\Mobile;
use App\UseCases\UtilityBox;
use Illuminate\Http\Request;

class InvestmentRequest extends Controller
{
    public $investApplicantRepository;
    public $distrcitRepository;

    public function __construct(InvestApplicantRepository $investApplicantRepository, DistrictRepository $districtRepository)
    {
        parent::__construct();

        $this->investApplicantRepository = $investApplicantRepository;
        $this->distrcitRepository = $districtRepository;
    }

    public function __invoke(Request $request)
    {
        $validator = $this->makeValidator($request, [
            'name' => '',
            'mobile' => ['required', new Mobile()],
            'city_id' => 'required|int',
            'district_id' => 'array',
            'deal_type' => 'required|in:rent,sale',
            'price' => 'required_if:deal_type,sale|integer|min:10000000',
            'rent' => 'required_if:deal_type,rent|integer',
            'mortgage' => 'required_if:deal_type,rent|integer'
        ]);

        if($validator->fails()) {
            $data = [
                'errors' => $validator->errors()
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        $city = $this->distrcitRepository->getOneApprovedById($request->get('city_id'));
        $districts = collect();

        if($request->filled('district_id')) {
            $districts = $this->distrcitRepository->getAllByIds($request->get('district_id'), true);
        }

        if(is_null($city)) {
            $data = [
                'errors' => [
                    'city_id' => ['validation.exists']
                ]
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        $investApplicant = new InvestApplicant();

        $investApplicant->setId(null);
        $investApplicant->setSource($request->get('source', InvestApplicantSource::_2NABSH));
        $investApplicant->setTag($request->get('tag', null));
        $investApplicant->setPropertyId(null);
        $investApplicant->setCustomerName($request->get('name'));
        $investApplicant->setMobile($request->get('mobile'));
        $investApplicant->setCityName($city->getName());
        $investApplicant->setCityId($city->getId());
        $investApplicant->setDistrictName($districts->isNotEmpty() ? $districts->implode('name', ',') : null);
        $investApplicant->setDistrictId($districts->isNotEmpty() ? ('"' . $districts->implode('id', '","') . '"') : null);
        $investApplicant->setType(null);
        $investApplicant->setMode($request->get('mode', InvestApplicantMode::_DEFAULT));
        $investApplicant->setMaxPrice(null);
        $investApplicant->setMaxRent(null);
        $investApplicant->setMaxMortgage(null);
        $investApplicant->setStatus(InvestApplicantStatus::PENDING);
        $investApplicant->setCreatedAt(time());
        $investApplicant->setDeletedAt(null);

        if($request->get('deal_type') === 'sale') {
            $investApplicant->setMaxPrice($request->get('price'));
        }
        else {
            $investApplicant->setMaxRent($request->get('rent'));
            $investApplicant->setMaxMortgage($request->get('mortgage'));
        }

        $investApplicant = $this->investApplicantRepository->insert($investApplicant);

        event(new InvestmentApplicantCreate($investApplicant));

        UtilityBox::RemoveCaptcha();

        return new Response();
    }
}
