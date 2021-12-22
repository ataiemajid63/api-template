<?php

namespace App\Http\Controllers\V1;

use App\Entities\Role;
use App\Enums\HttpStatusCode;
use App\Enums\SubscribeTypes;
use App\Enums\UserSignedBy;
use App\Enums\UserStatus;
use App\Factories\SubscribeFactory;
use App\Factories\UserFactory;
use App\Http\Controllers\Controller;
use App\Http\Response;
use App\Repositories\SubscribeRepository;
use App\Repositories\UserRepository;
use App\UseCases\UtilityBox;
use Illuminate\Http\Request;
use Pasoonate\Pasoonate;
use stdClass;

class SubscribeController extends Controller
{
    private $userRepository;
    private $subscribeRepository;

    public function __construct(UserRepository $userRepository, SubscribeRepository $subscribeRepository)
    {
        parent::__construct();

        $this->userRepository = $userRepository;
        $this->subscribeRepository = $subscribeRepository;
    }

    public function storeSubscribeByEmail(Request $request)
    {
        $validator = $this->makeValidator($request, [
            'email' => 'required|email'
        ]);

        if ($validator->fails()) {
            $data = [
                'errors' => $validator->errors()->toArray()
            ];
            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        $email = $request->get('email');

        $user = $this->userRepository->getOneByEmail($email);

        if (empty($user) or empty($user->getId())) {
            $user = $this->makeUserByEmail($email);
        }

        $timestamp = time();

        $subscribe = (new SubscribeFactory())->make(new stdClass);

        $subscribe->setUserId($user->getId());
        $subscribe->setQuestionId(null);
        $subscribe->setPostId(null);
        $subscribe->setTelegramChatId(null);
        $subscribe->setActive(true);
        $subscribe->setTelegramCount(0);
        $subscribe->setType(SubscribeTypes::EMAIL);
        $subscribe->setCreatedAt($timestamp);
        $subscribe->setUpdatedAt($timestamp);

        $this->subscribeRepository->insert($subscribe);

        return new Response([]);
    }

    private function makeUserByEmail($email)
    {
        $preRegisteredRole = Role::PreRegistered();
        $timestamp = time();
        $jalaliTime = Pasoonate::make()->jalali()->format('yyyy/MM/dd HH:mm:ss');

        $user = (new UserFactory())->make(new stdClass);

        $user->setEmail($email);
        $user->setRoleId($preRegisteredRole->getId());
        $user->setRoleName($preRegisteredRole->getName());
        $user->setSignedBy(UserSignedBy::MANISHEN);
        $user->setStatus(UserStatus::PENDING);
        $user->setHasCover(false);
        $user->setHasAvatar(false);
        $user->setCityId(null);
        $user->setCityName(null);
        $user->setCreatedAt($timestamp);
        $user->setUpdatedAt($timestamp);
        $user->setCreatedAtJalali($jalaliTime);
        $user->setUpdatedAtJalali($jalaliTime);

        $this->userRepository->insert($user);

        UtilityBox::RemoveCaptcha();

        return $user;
    }
}
