<?php

namespace App\Http\Controllers\V1;

use App\Entities\Agency;
use App\Entities\ContactInfo;
use App\Entities\Role;
use App\Entities\User;
use App\Enums\HttpStatusCode;
use App\Enums\MediaItemType;
use App\Enums\UserContactViewClient;
use App\Enums\UserContactViewPosition;
use App\Enums\UserType;
use App\Events\ContactInfoRequest;
use App\Factories\UserContactViewFactory;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ContactInfoCompactResourceCollection;
use App\Http\Resources\V1\ContactInfoResource;
use App\Http\Response;
use App\Repositories\AgencyRepository;
use App\Repositories\DistrictRepository;
use App\Repositories\MediaRepository;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use stdClass;

class AgenciesController extends Controller
{
    private $userRepository;
    private $agencyRepository;
    private $districtRepository;
    private $mediaRepository;

    public function __construct(UserRepository $userRepository, AgencyRepository $agencyRepository, DistrictRepository $districtRepository, MediaRepository $mediaRepository)
    {
        parent::__construct();

        $this->userRepository = $userRepository;
        $this->agencyRepository = $agencyRepository;
        $this->districtRepository = $districtRepository;
        $this->mediaRepository = $mediaRepository;
    }

    public function topAgencies(Request $request)
    {
        $validator = $this->makeValidator($request, [
            'city_id' => 'required'
        ]);

        if($validator->fails()) {
            $data = [
                'errors' => $validator->errors()
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        $city = $this->districtRepository->getOneApprovedById($request->get('city_id'));

        if(is_null($city)) {
            $data = [
                'errors' => [
                    'city_id' => 'city_id not exists'
                ]
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        $vipRealtors = $this->userRepository->getAllRealtorsByCityAndRolesAndTypeAndName($city, [Role::VIP()], UserType::AGENCY, null, null, null, true)->keyBy('agencyId');

        $agencies = $this->agencyRepository->getAllByIds($vipRealtors->pluck('agencyId')->all());

        $contacts = collect();

        /**
         * @var Agency $agency
         * @var User $realtor
         */
        foreach($agencies as $agency) {
            $contact = new ContactInfo();

            /**
             * @var User $realtor
            */
            $realtor = $vipRealtors[$agency->getId()];
            $avatar = $this->mediaRepository->getLatestByItemTypeAndItemId(MediaItemType::AGENCY, $agency->getId());

            if(!is_null($avatar) && is_null($avatar->getVerifiedAt())) {
                $avatar = null;
            }

            $contact->setId(null);
            $contact->setName(null);
            $contact->setPhone1(null);
            $contact->setPhone2(null);
            $contact->setAddress(null);
            $contact->setRealtorName($realtor->fullName());
            $contact->setRealtorPhone1($realtor->getPhone1());
            $contact->setRealtorPhone2($realtor->getPhone2());
            $contact->setAgencyName($agency->getName());
            $contact->setAgencyPhone1($agency->getPhone1());
            $contact->setAgencyPhone2($agency->getPhone2());
            $contact->setAvatar($avatar);
            $contact->setStars(null);
            $contact->setStarsCount(null);

            $contacts->add($contact);
        }

        $data = [
            'contacts' => (new ContactInfoCompactResourceCollection($contacts))->toArray()
        ];

        return new Response($data);
    }

    public function getContactInfo(Request $request, $agencyId)
    {
        $agency = $this->agencyRepository->getOneById($agencyId);

        if(is_null($agency)) {
            $data = [
                'contact_info' => null
            ];

            return new Response($data);
        }

        $avatar = $this->mediaRepository->getLatestByItemTypeAndItemId(MediaItemType::AGENCY, $agencyId);

        $contactInfo = new ContactInfo();

        $contactInfo->setId($agencyId);
        $contactInfo->setName(null);
        $contactInfo->setPhone1(null);
        $contactInfo->setPhone2(null);
        $contactInfo->setAddress(null);
        $contactInfo->setRealtorId(null);
        $contactInfo->setRealtorName(null);
        $contactInfo->setRealtorPhone1(null);
        $contactInfo->setRealtorPhone2(null);
        $contactInfo->setRealtorCityName(null);
        $contactInfo->setRealtorDistrictName(null);
        $contactInfo->setRealtorSlug(null);
        $contactInfo->setRealtorUsername(null);
        $contactInfo->setAgencyId($agency->getId());
        $contactInfo->setAgencyName($agency->getName());
        $contactInfo->setAgencyPhone1($agency->getPhone1());
        $contactInfo->setAgencyPhone2($agency->getPhone2());
        $contactInfo->setAgencyCityName($agency->getCityName());
        $contactInfo->setAgencyDistrictName($agency->getDistrictName());
        $contactInfo->setAgencyUsername($agency->getUsername());
        $contactInfo->setAgencySlug($agency->getSlug());
        $contactInfo->setAvatar($avatar);
        $contactInfo->setStars(null);
        $contactInfo->setStarsCount(0);

        // #region Create The UserContactView And Trigger Event
        $userContactView = (new UserContactViewFactory())->make(new stdClass);

        $userContactView->setClient(UserContactViewClient::WEBSITE);
        $userContactView->setClientId(app('client')->id());
        $userContactView->setUserId($agency ? $agency->getUserId() : null);
        $userContactView->setVisitorUid($request->header('Session'));
        $userContactView->setContactId($contactInfo->getId());
        $userContactView->setPropertyId(null);
        $userContactView->setPosition(UserContactViewPosition::PROPERTIES_SEARCH);
        $userContactView->setCreatedAt(time());

        event(new ContactInfoRequest($userContactView));
        // #endregion

        $data = [
            'contactInfo' => (new ContactInfoResource($contactInfo))->toArray(),
        ];

        return new Response($data);
    }
}
