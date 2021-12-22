<?php

namespace App\Http\Controllers\V1;

use App\Enums\DiscountOrderType;
use App\Enums\DiscountStatus;
use App\Enums\HttpStatusCode;
use App\Enums\OrderStatus;
use App\Enums\PromotionDiscount;
use App\Http\Controllers\Controller;
use App\Http\Response;
use App\Repositories\DiscountRepository;
use App\Repositories\OrderRepository;
use Illuminate\Http\Request;
use Pasoonate\Pasoonate;

class DiscountController extends Controller
{
    private $discountRepository;
    private $orderRepository;

    public function __construct(DiscountRepository $discountRepository, OrderRepository $orderRepository)
    {
        parent::__construct();

        $this->discountRepository = $discountRepository;
        $this->orderRepository = $orderRepository;
    }

    public function checkCode(Request $request)
    {
        $validator = $this->makeValidator($request, [
            'code' => 'required',
            'type' => 'required',
        ]);

        if ($validator->fails()) {
            $data = [
                'errors' => $validator->errors()
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        $code = $request->get('code');
        $type = $request->get('type');

        $user = $request->user();

        $discount = $this->discountRepository->getOneByCodeAndUserId($code, $user->getId());

        $data = [];
        $httpStatus = HttpStatusCode::OK;

        if (!empty($discount) and !empty($discount->getId())) {

            if (!empty($discount->getOrderType()) and $discount->getOrderType() != $type) {
                $data = [
                    'status' => DiscountStatus::UNEQUAL,
                    'plan_type' => $discount->getOrderType()
                ];
                $httpStatus = HttpStatusCode::UNPROCESSABLE_ENTITY;
            } elseif (!empty($discount->getExpiredAt()) and $discount->getExpiredAt() < time()) {
                $data = [
                    'status' => DiscountStatus::EXPIRED
                ];
                $httpStatus = HttpStatusCode::UNPROCESSABLE_ENTITY;
            } elseif ($discount->getStatus() == DiscountStatus::USED) {
                $data = [
                    'status' => DiscountStatus::USED
                ];
                $httpStatus = HttpStatusCode::UNPROCESSABLE_ENTITY;
            } elseif ($discount->getTitle() == DiscountStatus::INTRODUCE_PARTNER) {
                $order = $this->orderRepository->getOneByUserId($user->getId());

                if (!empty($order) and !empty($order->getId())) {
                    $jalaliDate = null;

                    if ($order->getStatus() == OrderStatus::EXPIRED) {
                        $validateDate = $order->getExpiredAt() + (86400 * 91);

                        $jalaliDate = Pasoonate::make($validateDate)->jalali()->format('%d F');
                    }

                    $reason = $order->getStatus() == OrderStatus::ACTIVE ? OrderStatus::ACTIVE_ACCOUNT : OrderStatus::EXPIRED_ACCOUNT;

                    $data = [
                        'status' => OrderStatus::CANT_USE_INTRODUCE_PARTNER,
                        'reason' => $reason,
                        'date' => $jalaliDate
                    ];
                    $httpStatus = HttpStatusCode::UNPROCESSABLE_ENTITY;
                } else {
                    $data = [
                        'status' => DiscountStatus::SUCCESS,
                        'discount' => $discount->getDiscount()
                    ];
                    $httpStatus = HttpStatusCode::OK;
                }
            } else {
                $data = [
                    'status' => DiscountStatus::SUCCESS,
                    'discount' => $discount->getDiscount()
                ];
                $httpStatus = HttpStatusCode::OK;
            }
        } elseif (!empty(PromotionDiscount::getPromotionDiscount($code))) {
            $promotion = PromotionDiscount::getPromotionDiscount($code);

            $data = [
                'status' => DiscountStatus::SUCCESS,
                'discount' => $promotion['discount']
            ];
            $httpStatus = HttpStatusCode::OK;
        }

        if (empty($data) or count($data) < 1) {
            $data = [
                'status' => DiscountStatus::WRONG,
            ];
            $httpStatus = HttpStatusCode::UNPROCESSABLE_ENTITY;
        }

        return new Response($data, $httpStatus);
    }
}
