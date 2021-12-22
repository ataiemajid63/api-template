<?php

namespace App\Http\Controllers\V1;

use App\Contracts\Settings;
use App\Entities\InvestApplicant;
use App\Entities\Property;
use App\Entities\RealtorSuggest;
use App\Entities\Report;
use App\Entities\Role;
use App\Enums\HttpStatusCode;
use App\Enums\InvestApplicantMode;
use App\Enums\InvestApplicantSource;
use App\Enums\InvestApplicantStatus;
use App\Enums\KanbanItemType;
use App\Enums\MediaItemType;
use App\Enums\PropertyDealType;
use App\Enums\PropertyRegisteredOn;
use App\Enums\PropertyStage;
use App\Enums\PropertyStatus;
use App\Enums\PropertyType;
use App\Enums\RealtorSuggestPosition;
use App\Enums\ReportItemName;
use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Enums\ShortLinkItemType;
use App\Enums\UserContactViewClient;
use App\Enums\UserContactViewPosition;
use App\Events\ContactInfoRequest;
use App\Events\InvestmentApplicantCreate;
use App\Events\PropertyVisit as PropertyVisitEvent;
use App\Events\RealtorOffer;
use App\Factories\UserContactViewFactory;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ContactInfoCompactResourceCollection;
use App\Http\Resources\V1\PropertyResourceCollection;
use App\Http\Resources\V1\ContactInfoResource;
use App\Http\Resources\V1\PropertyDealTypeResourceCollection;
use App\Http\Resources\V1\PropertyResource;
use App\Http\Resources\V1\PropertyTypeResourceCollection;
use App\Http\Response;
use App\Repositories\AgencyRepository;
use App\Repositories\DistrictRepository;
use App\Repositories\FollowRepository;
use App\Repositories\InvestApplicantRepository;
use App\Repositories\KanbanRepository;
use App\Repositories\MediaRepository;
use App\Repositories\PropertyRepository;
use App\Repositories\RealtorPollRepository;
use App\Repositories\ReportRepository;
use App\Repositories\ShortLinkRepository;
use App\Repositories\UserRepository;
use App\Rules\Mobile;
use App\UseCases\PropertyContactInfo;
use App\UseCases\PropertyDealTypes;
use App\UseCases\PropertyTypes;
use App\UseCases\UtilityBox;
use Illuminate\Http\Request;
use stdClass;

class PropertiesController extends Controller
{
    private $propertyRepository;
    private $userRepository;
    private $realtorPollRepository;
    private $followRepository;
    private $mediaRepository;
    private $reportRepository;
    private $agencyRepository;
    private $shortLinkRepository;
    private $investApplicantRepository;
    private $propertyDealTypes;
    private $propertyTypes;
    private $kanbanRepository;
    private $districtRepository;

    public function __construct(
        PropertyRepository $propertyRepository,
        UserRepository $userRepository,
        RealtorPollRepository $realtorPollRepository,
        FollowRepository $followRepository,
        MediaRepository $mediaRepository,
        ReportRepository $reportRepository,
        AgencyRepository $agencyRepository,
        ShortLinkRepository $shortLinkRepository,
        InvestApplicantRepository $investApplicantRepository,
        KanbanRepository $kanbanRepository,
        DistrictRepository $districtRepository,
        PropertyDealTypes $propertyDealTypes,
        PropertyTypes $propertyTypes)
    {
        $this->propertyRepository = $propertyRepository;
        $this->userRepository = $userRepository;
        $this->realtorPollRepository = $realtorPollRepository;
        $this->followRepository = $followRepository;
        $this->mediaRepository = $mediaRepository;
        $this->reportRepository = $reportRepository;
        $this->agencyRepository = $agencyRepository;
        $this->shortLinkRepository = $shortLinkRepository;
        $this->investApplicantRepository = $investApplicantRepository;
        $this->kanbanRepository = $kanbanRepository;
        $this->districtRepository = $districtRepository;
        $this->propertyDealTypes = $propertyDealTypes;
        $this->propertyTypes = $propertyTypes;
    }

    public function getProperty(Request $request, $propertyId)
    {
        // $key = 'property:id-' . $propertyId;
        // $ttl = 30 * 60; // 30 minutes
        $user = $request->user();

        // $property = app('cache')->remember($key, $ttl, function () use($request, $propertyId, $user) {
        $property = $this->propertyRepository->getOneByIdWithUser($propertyId);

        if ($property) {
            $userIsNotOwner = is_null($user) || $user->getId() != $property->getUserId();
            $allowWhenIsArchived = !$userIsNotOwner || ($property->getArchivedAt() && in_array($property->getStatus(), [PropertyStatus::ACTIVE, PropertyStatus::INACTIVE, PropertyStatus::DEALT, PropertyStatus::EXPIRED]));
            $allowWhenIsNotArchived = !$userIsNotOwner || (is_null($property->getArchivedAt()) && in_array($property->getStatus(), [PropertyStatus::ACTIVE, PropertyStatus::INACTIVE]));

            if (!$allowWhenIsArchived && !$allowWhenIsNotArchived) {
                $property = null;
            }
        }

        if ($property) {
            $propertyContactInfo = new PropertyContactInfo($this->userRepository, $this->realtorPollRepository, $this->mediaRepository, $this->agencyRepository);

            $media = $this->mediaRepository->getAllByItemTypeAndItemId(MediaItemType::PROPERTY, $property->getId());
            $contactInfo = $propertyContactInfo->findRelatedContactInfo($request->user(), $property, null, true);
            $shortLink = $this->shortLinkRepository->getOneByItemIdAndItemType($property->getId(), ShortLinkItemType::PROPERTY);
            $kanbanCard = $this->kanbanRepository->getOneByItemTypeAndItemId(KanbanItemType::PROPERTY, $property->getId());
            $requestContactCities = json_decode(app('settings')->value('invest_cities_ids'));
            $requestContact = in_array($property->getCityId(), $requestContactCities ?? []) || in_array($property->getDistrictId(), $requestContactCities ?? []) || ($property->getUser() && in_array($property->getUser()->getRoleId(), [Role::PrivateRepresentative()->getId(), Role::Expert()->getId()])) || $property->getRegisteredOn() === PropertyRegisteredOn::DIVAR;

            $property->setContactInfo($requestContact ? null : $contactInfo);
            $property->setMedia($media);
            $property->setShortLink($shortLink);
            $property->setKanban($kanbanCard);
            $property->setKanban($kanbanCard);
            $property->setRequestContact((bool)$requestContact);
        }

        //     return $property;
        // });

        if ($property) {
            $visitorId = $request->user() ? $request->user()->getId() : null;
            $visitorUid = is_null($visitorId) ? $request->header('Session') : null;

            event(new PropertyVisitEvent($property, $visitorId, $visitorUid));
        }

        $data = [
            'property' => $property ? (new PropertyResource($property, $user))->toArray() : null
        ];

        return new Response($data);
    }

    public function getRealtorsAround(Request $request, $propertyId)
    {
        $property = $this->propertyRepository->getOneByIdWithUser($propertyId);

        if (is_null($property)) {
            $data = [
                'errors' => [
                    'property_id' => ['validation.exists']
                ],
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        $propertyContactInfo = new PropertyContactInfo($this->userRepository, $this->realtorPollRepository, $this->mediaRepository, $this->agencyRepository);

        $users = $this->userRepository->getAllRealtorsAroundProperty($property);

        $count = $request->get('count', 4);
        $realtors = $users->take($count);
        $contacts = collect();

        // please check workflow
        foreach ($realtors as $realtor) {
            $contactInfo = $propertyContactInfo->createSuggestedRealtorContactInfo($realtor);
            $contacts->add($contactInfo);

            $realtorSuggest = new RealtorSuggest();
            $realtorSuggest->setId(null);
            $realtorSuggest->setUserId($realtor->getId());
            $realtorSuggest->setAdvertiseId(null);
            $realtorSuggest->setPropertyId($property->getId());
            $realtorSuggest->setPosition(RealtorSuggestPosition::PROPERTY);
            $realtorSuggest->setCreatedAt(time());

            event(new RealtorOffer($realtorSuggest));
        }

        $data = [
            'realtors' => (new ContactInfoCompactResourceCollection($contacts))->toArray()
        ];

        return new Response($data);
    }

    public function getContactInfo(Request $request, $propertyId, $suggestedRealtorId = 0)
    {
        $property = $this->propertyRepository->getOneByIdWithUser($propertyId);
        $suggestedRealtor = $suggestedRealtorId ? $this->userRepository->getOneById($suggestedRealtorId) : null;

        if (is_null($property)) {
            $data = [
                'errors' => [
                    'property_id' => ['validation.exists']
                ]
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        if ($suggestedRealtor && !$suggestedRealtor->containsWorkScope($property->getLocation()) && $suggestedRealtor->getDistrictId() !== $property->getDistrictId()) {
            $suggestedRealtor = null;
        }

        if (is_null($suggestedRealtor) && ($property->getUser() && !$property->getUser()->isRealtor()) && PropertyStage::hasValue(PropertyStage::ASSIGNED, $property->getStage())) {
            $realtorsAround = $this->userRepository->getAllRealtorsAroundProperty($property);

            if ($realtorsAround->isNotEmpty()) {
                $data = [
                    'errors' => [
                        'suggested_realtor_id' => ['validation.required', 'validation.around']
                    ]
                ];

                return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
            }
        }

        $propertyContactInfo = new PropertyContactInfo($this->userRepository, $this->realtorPollRepository, $this->mediaRepository, $this->agencyRepository);
        $contactInfo = $propertyContactInfo->findRelatedContactInfo($request->user(), $property, $suggestedRealtor);

        #region Create The UserContactView And Trigger Event
        $user = $request->user();

        $userContactView = (new UserContactViewFactory())->make(new stdClass);

        $userContactView->setClient(UserContactViewClient::WEBSITE);
        $userContactView->setClientId(app('client')->id());
        $userContactView->setUserId($user ? $user->getId() : null);
        $userContactView->setVisitorUid($request->header('Session'));
        $userContactView->setContactId($contactInfo->getId());
        $userContactView->setPropertyId($propertyId);
        $userContactView->setPosition(UserContactViewPosition::PROPERTY_SHOW);
        $userContactView->setCreatedAt(time());

        event(new ContactInfoRequest($userContactView));
        #endregion

        $data = [
            'contactInfo' => (new ContactInfoResource($contactInfo))->toArray(),
        ];

        return new Response($data);
    }

    public function getTypes($cityId)
    {
        $customTypes = $this->propertyTypes->cityId($cityId)->allByCaption();

        $data = [
            'types' => (new PropertyTypeResourceCollection($customTypes))->toArray()
        ];

        return new Response($data);
    }

    public function getDealTypes($cityId)
    {
        $customDealTypes = $this->propertyDealTypes->cityId($cityId)->allByCaption();

        $data = [
            'deal_types' => (new PropertyDealTypeResourceCollection($customDealTypes))->toArray()
        ];

        return new Response($data);
    }

    public function follow(Request $request, $propertyId)
    {
        $this->followRepository->follow($request->user()->getId(), $propertyId);

        return new Response(null, 200);
    }

    public function unfollow(Request $request, $propertyId)
    {
        $this->followRepository->unfollow($request->user()->getId(), $propertyId);

        return new Response(null, 200);
    }

    public function getBookmarkedProperties(Request $request)
    {
        $user = $request->user();

        $bookmarks = $this->followRepository->getAllByUserId($user->getId());
        $properties = $this->propertyRepository->getAllByIdsWithUser($bookmarks->pluck('propertyId'));
        $properties = $properties->keyBy('id');

        $temp = collect();

        foreach ($bookmarks as $bookmark) {
            if ($property = ($properties[$bookmark->getPropertyId()] ?? null)) {
                $temp->add($property);
            }
        }

        $properties = $temp;

        $media = $this->mediaRepository->getAllByItemTypeAndItemIds(MediaItemType::PROPERTY, $properties->pluck('id'));

        $propertyContactInfo = new PropertyContactInfo($this->userRepository, $this->realtorPollRepository, $this->mediaRepository, $this->agencyRepository);
        /**
         * @var Property $property
         */
        foreach ($properties as $property) {
            $contactInfo = $propertyContactInfo->findRelatedContactInfo($request->user(), $property, null, true);
            $property->setContactInfo($contactInfo);
            $property->setMedia($media->where('itemId', $property->getId()));
        }

        $data = [
            'properties' => (new PropertyResourceCollection($properties))->toArray(),
        ];

        return (new Response($data));
    }

    public function getBookmarks(Request $request)
    {
        $user = $request->user();

        $bookmarks = $this->followRepository->getAllByUserId($user->getId());

        $data = [
            'properties' => $bookmarks->isNotEmpty() ? $bookmarks->pluck('propertyId')->toArray() : [],
        ];

        return (new Response($data));
    }

    public function report(Request $request)
    {
        $reportTypes = ReportType::getList();

        $validator = $this->makeValidator($request, [
            'property_id' => 'required',
            'type' => 'required|in:' . implode(',', $reportTypes)
        ]);

        if ($validator->fails()) {
            $data = [
                'errors' => $validator->errors()->toArray(),
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        $user = $request->user();
        $type = $request->get('type');
        $propertyId = $request->get('property_id');
        $description = $request->filled('description') ? $request->get('description') : null;

        $report = new Report();

        $report->setId(null);
        $report->setUserId($user ? $user->getId() : null);
        $report->setItemId($propertyId);
        $report->setItemName(ReportItemName::PROPERTY);
        $report->setType($type);
        $report->setDescription($description);
        $report->setStatusOld(null);
        $report->setStatus(ReportStatus::PENDING);
        $report->setCreatedAt(time());
        $report->setUpdatedAt(time());

        $report = $this->reportRepository->insert($report);

        return new Response();
    }

    public function getSimilarProperties($propertyId)
    {
        $property = $this->propertyRepository->getOneByIdWithUser($propertyId);

        if (is_null($property)) {
            $data = [
                'errors' => [
                    'property' => 'Property not found.'
                ],
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        $properties = $this->propertyRepository->getAllSimilarPropertiesWithUser($property);
        $propertyIds = $properties->pluck('id')->all();

        $media = $this->mediaRepository->getAllByItemTypeAndItemIds(MediaItemType::PROPERTY, $propertyIds);
        $shortLinks = $this->shortLinkRepository->getAllByItemIdsAndItemType($propertyIds, ShortLinkItemType::PROPERTY);

        foreach ($properties as $property) {
            $property->setMedia($media->where('itemId', $property->getId()));
            $property->setShortLink($shortLinks->where('itemId', $property->getId())->first());
        }

        $data = [
            'properties' => (new PropertyResourceCollection($properties))->toArray(),
        ];

        return new Response($data);
    }

    public function getMyProperties(Request $request)
    {
        $user = $request->user();

        $properties = $this->propertyRepository->getAllByUserIdWithUser($user->getId())->whereNull('archivedAt');

        $media = $this->mediaRepository->getAllByItemTypeAndItemIds(MediaItemType::PROPERTY, $properties->pluck('id'));

        /**
         * @var Property $property
         */
        foreach ($properties as $property) {
            $contactInfo = (new PropertyContactInfo($this->userRepository, $this->realtorPollRepository, $this->mediaRepository, $this->agencyRepository))->findRelatedContactInfo($request->user(), $property, null, true);
            $property->setContactInfo($contactInfo);
            $property->setMedia($media->where('itemId', $property->getId()));
        }

        $data = [
            'properties' => (new PropertyResourceCollection($properties))->toArray(),
        ];

        return new Response($data);
    }

    public function getMyProperty(Request $request, $propertyId)
    {
        $user = $request->user();

        $property = $this->propertyRepository->getOneByIdWithUser($propertyId);

        if (is_null($property) || $property->getUserId() != $user->getId()) {
            $property = null;
        }

        if ($property) {
            $media = $this->mediaRepository->getAllByItemTypeAndItemId(MediaItemType::PROPERTY, $property->getId());
            $contactInfo = (new PropertyContactInfo($this->userRepository, $this->realtorPollRepository, $this->mediaRepository, $this->agencyRepository))->findRelatedContactInfo($user, $property, null, true);

            $property->setMedia($media);
            $property->setContactInfo($contactInfo);
        }

        $data = [
            'property' => $property ? (new PropertyResource($property, null))->toArray() : null
        ];

        return new Response($data);
    }

    public function deleteMyProperty(Request $request, $propertyId)
    {
        $user = $request->user();

        $property = $this->propertyRepository->getOneByIdWithUser($propertyId);

        if (is_null($property)) {
            $data = [
                'errors' => [
                    'property_id' => ['validation.exists']
                ]
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        if ($user->getId() != $property->getUserId()) {
            $data = [
                'errors' => [
                    'property_id' => ['validation.exists']
                ]
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        // TODO Check result of archive or remove on DB and Redis and return to client
        if ($property->getStatus() === PropertyStatus::PENDING) {
            $this->propertyRepository->remove($property);
        } else if ($property->getStatus() === PropertyStatus::ACTIVE) {
            $this->propertyRepository->archive($property);
        }

        return new Response();
    }

    public function callRequest(Request $request, $propertyId)
    {
        $validator = $this->makeValidator($request, [
            'property_id' => 'required|int',
            'name' => 'nullable',
            'mobile' => ['required', new Mobile()],
            'price' => 'nullable|int',
            'rent' => 'nullable|int',
            'mortgage' => 'nullable|int',
            'mode' => 'nullable|in:' . implode(',', InvestApplicantMode::getList()),
        ]);

        if ($validator->fails()) {
            $data = [
                'errors' => $validator->errors()
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        $property = $this->propertyRepository->getOneByIdWithUser($request->get('property_id'));

        if (is_null($property)) {
            $data = [
                'errors' => [
                    'property_id' => ['validation.exists']
                ]
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        $investApplicant = new InvestApplicant();

        $investApplicant->setId(null);
        $investApplicant->setSource($request->get('source', InvestApplicantSource::_2NABSH));
        $investApplicant->setTag($request->get('tag', null));
        $investApplicant->setPropertyId($property->getId());
        $investApplicant->setCustomerName($request->get('name'));
        $investApplicant->setMobile($request->get('mobile'));
        $investApplicant->setCityName($property->getCityName());
        $investApplicant->setCityId($property->getCityId());
        $investApplicant->setDistrictName($property->getDistrictName());
        $investApplicant->setDistrictId('"' . $property->getDistrictId() . '"');
        $investApplicant->setType(PropertyType::getName($property->getType()));
        $investApplicant->setMode($request->get('mode', InvestApplicantMode::_DEFAULT));
        $investApplicant->setMaxPrice(null);
        $investApplicant->setMaxRent(null);
        $investApplicant->setMaxMortgage(null);
        $investApplicant->setStatus(InvestApplicantStatus::PENDING);
        $investApplicant->setCreatedAt(time());
        $investApplicant->setDeletedAt(null);

        if ($property->getPrice()) {
            $investApplicant->setMaxPrice($property->getPrice() * 1000);
        } else {
            $investApplicant->setMaxRent($property->getRent() * 1000);
            $investApplicant->setMaxMortgage($property->getMortgage() * 1000);
        }

        $this->investApplicantRepository->insert($investApplicant);

        if ($investApplicant->getMode() === InvestApplicantMode::_DEFAULT) {
            event(new InvestmentApplicantCreate($investApplicant));
        }

        UtilityBox::RemoveCaptcha();

        return new Response();
    }

    public function averagePropertyPricesByMonth(Request $request)
    {
        $validator = $this->makeValidator($request, [
            'min_land_area' => 'required|int',
            'max_land_area' => 'required|int',
            'city_id' => 'required|int',
            'type' => 'required|array',
        ]);

        if ($validator->fails()) {
            $data = [
                'errors' => $validator->errors()
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        $landArea = [
            'min' => $request->get('min_land_area'),
            'max' => $request->get('max_land_area'),
        ];
        $dealType = $request->get('deal_type', PropertyDealType::SALE);
        $types = PropertyType::getValue($request->get('type'));
        $cityId = $request->get('city_id');

        $city = $this->districtRepository->getOneApprovedById($cityId);

        $result = $this->propertyRepository->averagePropertyPricesByMonth($city, $dealType, $types, $landArea);

        $data = [
            'result' => $result
        ];

        return new Response($data);
    }
}
