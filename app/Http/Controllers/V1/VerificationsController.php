<?php

namespace APP\Http\Controllers\V1;

use App\Entities\EmailLog;
use App\Entities\User;
use App\Entities\Verification;
use App\Enums\EmailLogStatus;
use App\Enums\EmailLogType;
use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Response;
use App\Notifications\EmailApplicantVerificationToken;
use App\Notifications\SendEmailVerificationCode;
use App\Notifications\SendMobileVerificationCode;
use App\Repositories\ApplicantRepository;
use App\Repositories\EmailLogRepository;
use App\Repositories\VerificationRepository;
use App\Rules\Mobile;
use DateTime;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Encryption\EncryptException;
use Illuminate\Http\Request;

/**
 * @property Encrypter $encrypter
 */
class VerificationsController extends Controller
{
    private $verificationRepository;
    private $applicantRepository;
    private $emailLogRepository;
    private $encrypter;

    public function __construct(VerificationRepository $verificationRepository, ApplicantRepository $applicantRepository, EmailLogRepository $emailLogRepository)
    {
        parent::__construct();

        $this->verificationRepository = $verificationRepository;
        $this->applicantRepository = $applicantRepository;
        $this->emailLogRepository = $emailLogRepository;

        $this->encrypter = app('encrypter');
    }

    public function sendRegularCode(Request $request)
    {
        $validator = $this->makeValidator($request, [
            'mobile' => [new Mobile()],
            'email' => 'email'
        ]);

        if($validator->fails()) {
            $data = [
                'errors' => $validator->errors()->toArray(),
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        $user = new User();
        $user->setMobile($request->get('mobile', null));
        $user->setEmail($request->get('email', null));

        if($user->getMobile()) {
            $verification = $this->verificationRepository->getOneByMobile($user->getMobile());

            if($verification) {
                $verification->setCode($verification->generateCode());
                $verification->setVerifiedAt(null);
                $verification->setExpiredAt((new DateTime())->modify('+5 minutes')->getTimestamp());

                $this->verificationRepository->update($verification);
            }
            else {
                $verification = new Verification();

                $verification->setId(null);
                $verification->setUserId(null);
                $verification->setMobile($user->getMobile());
                $verification->setEmail(null);
                $verification->setCode($verification->generateCode());
                $verification->setApproved(false);
                $verification->setCreatedAt(time());
                $verification->setVerifiedAt(null);
                $verification->setExpiredAt((new DateTime())->modify('+5 minutes')->getTimestamp());

                $this->verificationRepository->insert($verification);
            }

            $user->notify(new SendMobileVerificationCode($verification->getCode()));
        }

        if($user->getEmail()) {
            $verification = $this->verificationRepository->getOneByEmail($user->getEmail());

            if($verification) {
                $verification->setCode($verification->generateCode());
                $verification->setVerifiedAt(null);
                $verification->setExpiredAt((new DateTime())->modify('+5 minutes')->getTimestamp());

                $this->verificationRepository->update($verification);
            }
            else {
                $verification = new Verification();

                $verification->setId(null);
                $verification->setUserId(null);
                $verification->setMobile(null);
                $verification->setEmail($user->getEmail());
                $verification->setCode($verification->generateCode());
                $verification->setApproved(false);
                $verification->setCreatedAt(time());
                $verification->setVerifiedAt(null);
                $verification->setExpiredAt((new DateTime())->modify('+5 minutes')->getTimestamp());

                $this->verificationRepository->insert($verification);
            }

            $user->notify(new SendEmailVerificationCode($verification->getCode()));
        }

        return new Response();
    }

    public function sendApplicantLink(Request $request)
    {
        $validator = $this->makeValidator($request, [
            'email' => 'required|email',
            'applicant_id' => 'required|int',
        ]);

        if($validator->fails()) {
            $data = [
                'errors' => $validator->errors()->toArray(),
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        $applicant = $this->applicantRepository->getOneById($request->get('applicant_id'));

        if(is_null($applicant)) {
            $data = [
                'errors' => [
                    'applicant_id' => [
                        'applicant_id not exists'
                    ]
                ],
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        $user = new User();
        $user->setId($request->get('user_id', $request->user() ? $request->user()->getId() : null));
        $user->setEmail($request->get('email'));

        $verification = $this->verificationRepository->getOneByEmail($user->getEmail());

        if($verification) {
            $verification->setCode($verification->generateCode());
            $verification->setVerifiedAt(null);
            $verification->setExpiredAt((new DateTime())->modify('+24 hours')->getTimestamp());

            $this->verificationRepository->update($verification);
        }
        else {
            $verification = new Verification();

            $verification->setId(null);
            $verification->setUserId(null);
            $verification->setMobile(null);
            $verification->setEmail($user->getEmail());
            $verification->setCode($verification->generateCode());
            $verification->setApproved(false);
            $verification->setCreatedAt(time());
            $verification->setVerifiedAt(null);
            $verification->setExpiredAt((new DateTime())->modify('+5 minutes')->getTimestamp());

            $this->verificationRepository->insert($verification);
        }

        $emailLog = new EmailLog();

        $emailLog->setId(null);
        $emailLog->setUserId($user->getId());
        $emailLog->setItemId($applicant->getId());
        $emailLog->setEmail($user->getEmail());
        $emailLog->setType(EmailLogType::APPLICANT_CONFIRM);
        $emailLog->setData(null);
        $emailLog->setStatus(EmailLogStatus::SENT);
        $emailLog->setCreatedAt(time());
        $emailLog->setUpdatedAt(time());

        $emailLog = $this->emailLogRepository->insert($emailLog);

        $params = [
            'verification_code' => $verification->getCode(),
            'applicant_id' => $applicant->getId(),
            'email_log_id' => $emailLog->getId(),
            'user_id' => $user->getId(),
            'email' => $user->getEmail(),
        ];

        $token = $this->encrypter->encrypt(json_encode($params));

        $user->notify(new EmailApplicantVerificationToken($applicant, $token));

        return new Response();
    }

    public function checkApplicantToken(Request $request)
    {
        $validator = $this->makeValidator($request, [
            'token' => 'required',
        ]);

        if($validator->fails()) {
            $data = [
                'errors' => $validator->errors()->toArray(),
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        try {
            $params = json_decode($this->encrypter->decrypt($request->get('token')), true);
        } catch (EncryptException $e) {
            $data = [
                'errors' => [
                    'token' => ['Malformed token.']
                ],
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        $verificationCode = $params['verify_code'] ?? $params['verification_code'];
        $applicantId = $params['item_id'] ?? $params['applicant_id'];
        $emailLogId = $params['email_log_id'] ?? null;
        $userId = $params['user_id'] ?? null;
        $email = $params['email'] ?? null;

        #region Update Email Log
        $emailLog = $emailLogId ? $this->emailLogRepository->getOneById($emailLogId) : $this->emailLogRepository->getOneByItemIdAndTypeAndStatus($applicantId, EmailLogType::APPLICANT_CONFIRM, EmailLogStatus::SENT);

        $emailLog->setStatus(EmailLogStatus::RETURN);
        $emailLog->setUpdatedAt(time());

        $this->emailLogRepository->update($emailLog);
        #endregion

        $data = [
            'verification_code' => $verificationCode,
            'applicant_id' => $applicantId,
            'email_log_id' => $emailLogId,
            'user_id' => $userId,
            'email' => $email,
        ];

        return new Response($data);
    }
}
