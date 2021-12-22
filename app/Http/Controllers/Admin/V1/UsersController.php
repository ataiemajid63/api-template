<?php

namespace App\Http\Controllers\Admin\V1;

use App\Http\Controllers\Controller;
use App\Http\Response;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function update(Request $request)
    {
        $validator = $this->makeValidator($request, [
            'id' => 'required|int',
        ]);

        if($validator->fails()) {
            $data = [
                'errors' => $validator->errors()
            ];

            return new Response($data, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = $this->userRepository->getOneById($request->get('id'));

        if(is_null($user)) {
            $data = [
                'errors' => [
                    'id' => ['validation:exists']
                ]
            ];

            return new Response($data, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->userRepository->purge($user->getId());
        $this->userRepository->getOneById($request->get('id'));

        return new Response();
    }
}
