<?php

namespace App\Http\Controllers\V1;

use App\Entities\User;
use App\Enums\DepartmentChatId;
use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Response;
use App\Notifications\ContactUsRequestNotification;
use Illuminate\Http\Request;

class ContactUs extends Controller
{
    public function __construct()
    {

    }

    public function __invoke(Request $request)
    {
        $validator = $this->makeValidator($request, [
            'full_name' => 'required|string|min:3|max:50',
            'email' => 'required|email',
            'department' => 'required',
            'description' => 'required|string',
        ]);

        if($validator->fails()) {
            $data = [
                'erorrs' => $validator->errors()
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        $fullName = $request->get('full_name');
        $email = $request->get('email');
        $department = $request->get('department');
        $description = $request->get('description');

        $user = new User();
        $user->setChatId(env('CONTACT_US_' . strtoupper($department)));
        // $user->setChatId(DepartmentChatId::getValue($department));

        $user->notify(new ContactUsRequestNotification($fullName, $email, $department, $description));

        return new Response();
    }
}
