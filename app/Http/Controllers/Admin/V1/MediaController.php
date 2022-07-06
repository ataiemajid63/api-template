<?php

namespace App\Http\Controllers\Admin\V1;

use App\Enums\MediaItemType;
use App\Http\Controllers\Controller;
use App\Http\Response;
use App\Repositories\MediaRepository;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    private $mediaRepository;
    private $userRepository;

    public function __construct(MediaRepository $mediaRepository, UserRepository $userRepository)
    {
        parent::__construct();

        $this->mediaRepository = $mediaRepository;
        $this->userRepository = $userRepository;
    }

    public function deleteUserAvatar(Request $request)
    {
        $validator = $this->makeValidator($request, [
            'user_id' => 'required|int',
        ]);

        if($validator->fails()) {
            $data = [
                'errors' => $validator->errors()
            ];

            return new Response($data, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = $this->userRepository->getOneById($request->get('user_id'));

        if(is_null($user)) {
            $data = [
                'errors' => [
                    'user_id' => ['validation:exists']
                ]
            ];

            return new Response($data, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $deleted = $this->mediaRepository->deleteByItem(MediaItemType::USER, $user->getId());

        if($deleted) {
            $user->setUpdatedAt(time());

            $this->userRepository->update($user);
        }

        return new Response();
    }
}
