<?php

namespace App\Http\Controllers\V1;

use App\Entities\User;
use App\Entities\UserToken;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\UserResource;
use App\Http\Response;
use App\Repositories\UserRepository;
use App\Repositories\UserTokenRepository;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    private $userRepository;
    private $userTokenRepository;

    public function __construct(UserRepository $userRepository, UserTokenRepository $userTokenRepository)
    {
        $this->userRepository = $userRepository;
        $this->userTokenRepository = $userTokenRepository;
    }

    private function username($username)
    {
        $type = 'username';

        if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
            $type = 'email';
        } elseif (preg_match('/^09[0-9-]{9}$/', $username)) {
            $type = 'mobile';
        }

        return $type;
    }

    public function getUsernameInfo($username)
    {
        if($this->username($username) === 'email') {
            $user = $this->userRepository->getOneByEmail($username);
        }
        else {
            $user = $this->userRepository->getOneByMobile($username);
        }

        $data = [
            'user_exists' => !is_null($user),
            'user_has_password' => $user && !is_null($user->getPassword()),
            'user_status' => $user ? $user->getStatus() : null,
            'username' => $username,
        ];

        return new Response($data);
    }

    public function getUserInfo(Request $request)
    {
        /**
         * @var User $user
         */
        $user = $request->user();

        $data = [
            'user' => (new UserResource($user))->toArray(),
        ];

        return new Response($data);
    }

    public function loginByPassword(Request $request)
    {
        $validator = $this->makeValidator($request, [
            'username' => 'required',
            'password' => 'required'
        ]);

        if($validator->fails()) {
            $data = [
                'errors' => $validator->errors()->toArray(),
            ];

            return new Response($data, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $username = $request->get('username');
        $password = $request->get('password');

        if($this->username($username) === 'email') {
            $user = $this->userRepository->getOneByEmail($username);
        }
        else {
            $user = $this->userRepository->getOneByMobile($username);
        }

        if(is_null($user) || !is_null($user->getDeletedAt())) {
            $data = [
                'errors' => [
                    'user' => ['validation.exists']
                ]
            ];

            return new Response($data, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if($user && $user->getStatus() === UserStatus::CREATED) {
            $data = [
                'errors' => [
                    'user' => ['validation.created']
                ]
            ];

            return new Response($data, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if(is_null($user) || password_verify($password, $user->getPassword()) === false) {
            $data = [
                'message' => 'username or password incorrect',
            ];

            return new Response($data, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $userToken = new UserToken();

        $userToken->setId(null);
        $userToken->setUserId($user->getId());
        $userToken->setToken($userToken->generateToken());
        $userToken->setDeviceId(null);
        $userToken->setDeviceDensity(null);
        $userToken->setDeviceModel(null);
        $userToken->setDeviceLanguage(null);
        $userToken->setNetworkType(null);
        $userToken->setScreenWidth(null);
        $userToken->setScreenHeight(null);
        $userToken->setIp(null);
        $userToken->setOs(null);
        $userToken->setOsVersion(null);
        $userToken->setApiVersion(null);
        $userToken->setClientVersion(null);
        $userToken->setCreatedAt(time());
        $userToken->setUpdatedAt(time());
        $userToken->setExpiredAt(0);

        $this->userTokenRepository->expireByUserId($user->getId());
        $this->userTokenRepository->insert($userToken);

        $data = [
            'user' => (new UserResource($user))->toArray(),
            'auth_token' => $userToken->getToken()
        ];

        return new Response($data);
    }
}
