<?php

namespace App\Http\Controllers\V1;

use App\Entities\ContactInfo;
use App\Entities\RealtorPoll;
use App\Enums\HttpStatusCode;
use App\Enums\MediaItemType;
use App\Enums\RealtorPollReason;
use App\Enums\UserContactViewClient;
use App\Enums\UserContactViewPosition;
use App\Events\ContactInfoRequest;
use App\Factories\UserContactViewFactory;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ContactInfoResource;
use App\Http\Response;
use App\Repositories\MediaRepository;
use App\Repositories\RealtorPollRepository;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use stdClass;

class RealtorsController extends Controller
{
    private $realtorPollRepository;
    private $userRepository;
    private $mediaRepository;

    public function __construct(RealtorPollRepository $realtorPollRepository, UserRepository $userRepository, MediaRepository $mediaRepository)
    {
        parent::__construct();

        $this->realtorPollRepository = $realtorPollRepository;
        $this->userRepository = $userRepository;
        $this->mediaRepository = $mediaRepository;
    }

    public function getContactInfo(Request $request, $userId)
    {
        $user = $this->userRepository->getOneById($userId);

        if(is_null($user)) {
            $data = [
                'contact_info' => null
            ];

            return new Response($data);
        }

        $avatar = $this->mediaRepository->getLatestByItemTypeAndItemId(MediaItemType::USER, $userId);

        $contactInfo = new ContactInfo();
        $starsCount = 0;

        $contactInfo->setId($user->getId());
        $contactInfo->setName(null);
        $contactInfo->setPhone1(null);
        $contactInfo->setPhone2(null);
        $contactInfo->setAddress(null);
        $contactInfo->setRealtorId($user->getId());
        $contactInfo->setRealtorName($user->fullName());
        $contactInfo->setRealtorPhone1($user->getPhone1());
        $contactInfo->setRealtorPhone2($user->getPhone2());
        $contactInfo->setRealtorCityName($user->getCityName());
        $contactInfo->setRealtorDistrictName($user->getDistrictName());
        $contactInfo->setRealtorSlug($user->getSlug());
        $contactInfo->setRealtorUsername($user->getUsername());
        $contactInfo->setAgencyId(null);
        $contactInfo->setAgencyName($user->getAgencyName());
        $contactInfo->setAgencyPhone1(null);
        $contactInfo->setAgencyPhone2(null);
        $contactInfo->setAgencyCityName(null);
        $contactInfo->setAgencyDistrictName(null);
        $contactInfo->setAgencyUsername(null);
        $contactInfo->setAgencySlug(null);
        $contactInfo->setAvatar($avatar);
        $contactInfo->setStars($this->realtorPollRepository->averageStarsByRealtorId($user->getId(), $starsCount));
        $contactInfo->setStarsCount($starsCount);

        #region Create The UserContactView And Trigger Event
        $userContactView = (new UserContactViewFactory())->make(new stdClass);

        $userContactView->setClient(UserContactViewClient::WEBSITE);
        $userContactView->setClientId(app('client')->id());
        $userContactView->setUserId($user ? $user->getId() : null);
        $userContactView->setVisitorUid($request->header('Session'));
        $userContactView->setContactId($contactInfo->getId());
        $userContactView->setPropertyId(null);
        $userContactView->setPosition(UserContactViewPosition::PROPERTIES_SEARCH);
        $userContactView->setCreatedAt(time());

        event(new ContactInfoRequest($userContactView));
        #endregion

        $data = [
            'contactInfo' => (new ContactInfoResource($contactInfo))->toArray(),
        ];

        return new Response($data);
    }

    public function savePoll(Request $request)
    {
        $realtorPollReasons = array_keys(RealtorPollReason::getList());

        $validator = $this->makeValidator($request, [
            'realtor_id' => 'required|integer',
            // 'reasons' => 'required_unless:stars,5|nullable|array|in:' . strtolower(implode(',', $realtorPollReasons)),
            'stars' => 'required|integer',
        ]);

        if($validator->fails()) {
            $data = [
                'errors' => $validator->errors()
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        $user = $request->user();
        $realtor = $this->userRepository->getOneById($request->get('realtor_id'));

        if($user->isRealtor()) {
            $data = [
                'errors' => [
                    'user_id' => ['validation:realtor']
                ]
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        if(is_null($realtor) || !$realtor->isRealtor()) {
            $data = [
                'errors' => [
                    'realtor_id' => ['validation:exists']
                ]
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        $realtorPoll = new RealtorPoll();

        $realtorPoll->setId(null);
        $realtorPoll->setUserId($user->getId());
        $realtorPoll->setVerificationId(null);
        $realtorPoll->setRealtorId($realtor->getId());
        $realtorPoll->setReasons(RealtorPollReason::getValue($request->get('reasons', [])));
        $realtorPoll->setSuggest(null);
        $realtorPoll->setRead(false);
        $realtorPoll->setStars($request->get('stars'));
        $realtorPoll->setDescription($request->get('description'));
        $realtorPoll->setCreatedAt(time());

        if($this->realtorPollRepository->existsByUserIdAndRealtorId($user->getId(), $realtor->getId())) {
            $this->realtorPollRepository->update($realtorPoll);
        }
        else {
            $this->realtorPollRepository->insert($realtorPoll);
        }

        return new Response();
    }
}
