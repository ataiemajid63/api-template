<?php

namespace App\Http\Controllers\V1;

use App\Entities\AreaComment;
use App\Enums\AreaCommentStatus;
use App\Enums\AreaStatus;
use App\Enums\AreaType;
use App\Enums\DistrictType;
use App\Enums\HttpStatusCode;
use App\Enums\MediaItemType;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\AreaCommentResourceCollection;
use App\Http\Resources\V1\AreaCompactResource;
use App\Http\Resources\V1\AreaCompactResourceCollection;
use App\Http\Resources\V1\DistrictResourceCollection;
use App\Http\Response;
use App\Repositories\AreaRepository;
use App\Repositories\DistrictRepository;
use App\Repositories\MediaRepository;
use App\Repositories\AreaCommentRepository;
use App\Repositories\AreaMetaRepository;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;

class AreasController extends Controller
{
    private $areaRepository;
    private $areaMetaRepository;
    private $mediaRepository;
    private $districtRepository;
    private $areaCommentRepository;
    private $userRepository;

    public function __construct(
        AreaRepository $areaRepository,
        AreaMetaRepository $areaMetaRepository,
        MediaRepository $mediaRepository,
        DistrictRepository $districtRepository,
        AreaCommentRepository $areaCommentRepository,
        UserRepository $userRepository
    )
    {
        parent::__construct();

        $this->areaRepository = $areaRepository;
        $this->areaMetaRepository = $areaMetaRepository;
        $this->mediaRepository = $mediaRepository;
        $this->districtRepository = $districtRepository;
        $this->areaCommentRepository = $areaCommentRepository;
        $this->userRepository = $userRepository;
    }

    public function getByDistrictIdForType($districtId, $type)
    {
        $district = $this->districtRepository->getOneApprovedById($districtId);

        if(is_null($district)) {
            $data = [
                'errors' => [
                    'district_id' => ['validation.exists']
                ]
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        switch($district->getType()) {
            case DistrictType::AREA:
                $cities = $this->districtRepository->getAllContainsByDistrictAndType($district, DistrictType::CITY);
                $areas = $this->areaRepository->getAllByCityIdsAndType($cities->pluck('id')->all(), AreaType::CITY);
            break;
            case DistrictType::CITY:
                $districtType = $type === AreaType::DISTRICT ? DistrictType::DISTRICT : DistrictType::REGION;
                $districts = $this->districtRepository->getAllByCityIdAndTypeAndUserId($district->getId(), $districtType);
            case DistrictType::REGION:
                $districts = $districts ?? $this->districtRepository->getAllByRegionIdAndTypeAndUserId($district->getId(), DistrictType::DISTRICT);
            case DistrictType::DISTRICT:
                $districts = $districts ?? collect([$district]);
                $areas = $this->areaRepository->getAllByDistrictIdsAndType($districts->pluck('id')->all(), $type);
            break;
            default:
                $areas = collect();
        }

        if($areas->isNotEmpty()) {
            $areaIds = $areas->pluck('id')->all();

            $media = $this->mediaRepository->getAllByItemTypeAndItemIds(MediaItemType::AREA, $areaIds)->groupBy('itemId');
            $areaMetas = $this->areaMetaRepository->getAllByAreaIds($areaIds)->keyBy('areaId');

            foreach($areas as $area) {
                $area->setMedia($media[$area->getId()] ?? collect());
                $area->setMeta($areaMetas[$area->getId()] ?? null);
            }
        }

        $data = [
            'areas' => (new AreaCompactResourceCollection($areas))->toArray()
        ];

        return new Response($data);
    }

    public function getArea($id)
    {
        $area = $this->areaRepository->getOneById($id, AreaStatus::ACTIVE);

        if($area) {
            $media = $this->mediaRepository->getAllByItemTypeAndItemId(MediaItemType::AREA, $area->getId());
            $city = $area->getCityId() ? $this->districtRepository->getOneApprovedById($area->getCityId()) : null;
            $district = $area->getDistrictId() ? $this->districtRepository->getOneApprovedById($area->getDistrictId()) : null;
            $meta = $this->areaMetaRepository->getAllByAreaId($area->getId());

            $area->setMedia($media);
            $area->setCity($city);
            $area->setDistrict($district);
            $area->setMeta($meta);

            $statsOfArea = $this->areaCommentRepository->statOfAreaByAreaIdAndStatus($area->getId(), AreaCommentStatus::PUBLISH);

            $stats = [
                'transportation' => $statsOfArea['transportation'],
                'amenities' => $statsOfArea['amenities'],
                'culture' => $statsOfArea['culture'],
                'traffic' => $statsOfArea['traffic'],
                'transportation_percent' => $statsOfArea['comments'] ? ($statsOfArea['transportation'] * 100) / ($statsOfArea['comments'] * 5) : 0,
                'amenities_percent' => $statsOfArea['comments'] ? ($statsOfArea['amenities'] * 100) / ($statsOfArea['comments'] * 5) : 0,
                'culture_percent' => $statsOfArea['comments'] ? ($statsOfArea['culture'] * 100) / ($statsOfArea['comments'] * 5) : 0,
                'traffic_percent' => $statsOfArea['comments'] ? ($statsOfArea['traffic'] * 100) / ($statsOfArea['comments'] * 5) : 0,
                'rate' => $statsOfArea['rate'],
                'count' => $statsOfArea['comments'],
            ];
        }

        $data = [
            'area' => $area ? (new AreaCompactResource($area))->toArray() : null,
            'stats' => $stats ?? []
        ];

        return new Response($data);
    }

    public function getAreaByDistrictId($districtId)
    {
        $area = $this->areaRepository->getOneByDistrictId($districtId, AreaStatus::ACTIVE);

        if($area) {
            $media = $this->mediaRepository->getAllByItemTypeAndItemId(MediaItemType::AREA, $area->getId());
            $meta = $this->areaMetaRepository->getAllByAreaId($area->getId());
            $city = $area->getCityId() ? $this->districtRepository->getOneApprovedById($area->getCityId()) : null;
            $district = $area->getDistrictId() ? $this->districtRepository->getOneApprovedById($area->getDistrictId()) : null;

            $area->setMedia($media);
            $area->setMeta($meta);
            $area->setCity($city);
            $area->setDistrict($district);
        }

        $data = [
            'area' => $area ? (new AreaCompactResource($area))->toArray() : null
        ];

        return new Response($data);
    }

    public function getAreaComments(Request $request, $id)
    {
        $count = $request->get('count', 20);
        $page = $request->get('page', 1);
        $total = 0;

        $areaComments = $this->areaCommentRepository->getAllByAreaIdAndStatus($id, AreaCommentStatus::PUBLISH, $page, $count, $total);

        $ids = $areaComments->pluck('id')->all();
        $replies = $this->areaCommentRepository->getAllByParentIdsAndStatus($ids, AreaCommentStatus::PUBLISH);

        $userIds = $areaComments->pluck('userId')->merge($replies->pluck('userId'))->unique()->all();
        $users = $this->userRepository->getAllByIds($userIds)->keyBy('id');


        if($users->isNotEmpty()) {
            foreach($replies as $reply) {
                $user = $users[$reply->getUserId()] ?? null;

                if($user && $user->getAvatar() === null) {
                    $avatar = $this->mediaRepository->getLatestByItemTypeAndItemId(MediaItemType::USER, $user->getId());
                    $user->setAvatar($avatar);
                }

                $reply->setUser($user);
            }

            $replies = $replies->groupBy('parentId');

            foreach($areaComments as $areaComment) {
                $user = $users[$areaComment->getUserId()] ?? null;

                if($user && $user->getAvatar() === null) {
                    $avatar = $this->mediaRepository->getLatestByItemTypeAndItemId(MediaItemType::USER, $user->getId());
                    $user->setAvatar($avatar);
                }

                $areaComment->setUser($user);

                $areaComment->setReplies($replies[$areaComment->getId()] ?? collect());
            }
        }

        $data = [
            'comments' => (new AreaCommentResourceCollection($areaComments))->toArray(),
            'total' => $total
        ];

        return new Response($data);
    }

    public function getSelectedAreaComments(Request $request, $id)
    {
        $areaComments = $this->areaCommentRepository->getAllSelectedByAreaIdAndStatus($id, AreaCommentStatus::PUBLISH);

        $userIds = $areaComments->pluck('userId')->unique()->all();
        $users = $this->userRepository->getAllByIds($userIds)->keyBy('id');

        if($users->isNotEmpty()) {
            foreach($areaComments as $areaComment) {
                $user = $users[$areaComment->getUserId()] ?? null;

                if($user && $user->getAvatar() === null) {
                    $avatar = $this->mediaRepository->getLatestByItemTypeAndItemId(MediaItemType::USER, $user->getId());
                    $user->setAvatar($avatar);
                }

                $areaComment->setUser($user);
            }
        }

        $data = [
            'comments' => (new AreaCommentResourceCollection($areaComments))->toArray(),
        ];

        return new Response($data);
    }

    public function getStatOfArea(Request $request, $id)
    {
        $area = $this->areaRepository->getOneById($id, AreaStatus::ACTIVE);

        if(is_null($area)) {
            $data = [
                'errors' => [
                    'area_id' => ['validation.exists']
                ]
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        $statsOfArea = $this->areaCommentRepository->statOfAreaByAreaIdAndStatus($id, AreaCommentStatus::PUBLISH);

        $data = [
            'transportation' => $statsOfArea['transportation'],
            'amenities' => $statsOfArea['amenities'],
            'culture' => $statsOfArea['culture'],
            'traffic' => $statsOfArea['traffic'],
            'transportation_percent' => $statsOfArea['comments'] ? ($statsOfArea['transportation'] * 100) / ($statsOfArea['comments'] * 5) : 0,
            'amenities_percent' => $statsOfArea['comments'] ? ($statsOfArea['amenities'] * 100) / ($statsOfArea['comments'] * 5) : 0,
            'culture_percent' => $statsOfArea['comments'] ? ($statsOfArea['culture'] * 100) / ($statsOfArea['comments'] * 5) : 0,
            'traffic_percent' => $statsOfArea['comments'] ? ($statsOfArea['traffic'] * 100) / ($statsOfArea['comments'] * 5) : 0,
            'rate' => $statsOfArea['rate'],
            'count' => $statsOfArea['comments'],
        ];

        return new Response($data);
    }

    public function storeAreaComment(Request $request, $id)
    {
        $validator = $this->makeValidator($request, [
            'author_name' => $request->user() ? '' : 'required',
            'description' => 'nullable',
            'positives' => 'array',
            'negatives' => 'array',
            'transportation' => 'nullable|integer',
            'culture' => 'nullable|integer',
            'traffic' => 'nullable|integer',
            'amenities' => 'nullable|integer',
            'rate' => 'nullable|integer',
            'lived_in' => 'required|boolean',
        ]);

        if($validator->fails()) {
            $data = [
                'errors' => $validator->errors()
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        $area = $this->areaRepository->getOneById($id);

        if(is_null($area)) {
            $data = [
                'errors' => [
                    'area_id' => ['validation.exists']
                ]
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        $user = $request->user();

        $areaComment = new AreaComment();

        $areaComment->setId(null);
        $areaComment->setParentId(null);
        $areaComment->setAreaId($area->getId());
        $areaComment->setUserId($user ? $user->getId() : null);
        $areaComment->setAuthorName($user ? $user->fullname() : $request->get('author_name', ''));
        $areaComment->setDescription($request->get('description'));
        $areaComment->setTransportation($request->get('transportation'));
        $areaComment->setCulture($request->get('culture'));
        $areaComment->setTraffic($request->get('traffic'));
        $areaComment->setAmenities($request->get('amenities'));
        $areaComment->setRate($request->get('rate'));
        $areaComment->setPositives(implode(',|,', $request->get('positives', [])));
        $areaComment->setNegatives(implode(',|,', $request->get('negatives', [])));
        $areaComment->setLikes(null);
        $areaComment->setDislikes(null);
        $areaComment->setLivedIn(boolval($request->get('lived_in', 0)));
        $areaComment->setStatus(AreaCommentStatus::PENDING);
        $areaComment->setCreatedAt(time());
        $areaComment->setUpdatedAt(time());
        $areaComment->setUser($user);

        if(empty($areaComment->getDescription()) && empty($areaComment->getPositives()) && empty($areaComment->getNegatives())) {
            $areaComment->setStatus(AreaCommentStatus::PUBLISH);
        }

        $areaComment = $this->areaCommentRepository->insert($areaComment);

        return new Response();
    }

    public function likeAreaComment($areaId, $commentId)
    {
        $comment = $this->areaCommentRepository->getOneById($commentId);

        if(is_null($comment) || $comment->getAreaId() != $areaId) {
            $data = [
                'errors' => [
                    'comment_id' => ['validation.exists']
                ]
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        if($comment->getStatus() === AreaCommentStatus::PUBLISH) {
            $this->areaCommentRepository->like($commentId);
        }
    }

    public function dislikeAreaComment($areaId, $commentId)
    {
        $comment = $this->areaCommentRepository->getOneById($commentId);

        if(is_null($comment) || $comment->getAreaId() != $areaId) {
            $data = [
                'errors' => [
                    'comment_id' => ['validation.exists']
                ]
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        if($comment->getStatus() === AreaCommentStatus::PUBLISH) {
            $this->areaCommentRepository->dislike($commentId);
        }
    }

    public function answerToAreaComment(Request $request, $areaId, $commentId)
    {
        $validator = $this->makeValidator($request, [
            'author_name' => $request->user() ? 'nullable' : 'required',
            'description' => 'required',
        ]);

        if($validator->fails()) {
            $data = [
                'errors' => $validator->errors()
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        $comment = $this->areaCommentRepository->getOneById($commentId);

        if(is_null($comment) || $comment->getAreaId() != $areaId) {
            $data = [
                'errors' => [
                    'comment_id' => ['validation.exists']
                ]
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        if($comment->getStatus() !== AreaCommentStatus::PUBLISH) {
            return new Response();
        }

        $user = $request->user();

        $areaComment = new AreaComment();

        $areaComment->setId(null);
        $areaComment->setParentId($commentId);
        $areaComment->setAreaId($areaId);
        $areaComment->setUserId($user ? $user->getId() : null);
        $areaComment->setAuthorName($user ? $user->fullname() : $request->get('author_name', ''));
        $areaComment->setDescription($request->get('description'));
        $areaComment->setTransportation(null);
        $areaComment->setCulture(null);
        $areaComment->setTraffic(null);
        $areaComment->setAmenities(null);
        $areaComment->setRate(null);
        $areaComment->setPositives(null);
        $areaComment->setNegatives(null);
        $areaComment->setLikes(null);
        $areaComment->setDislikes(null);
        $areaComment->setLivedIn(0);
        $areaComment->setStatus(AreaCommentStatus::PENDING);
        $areaComment->setCreatedAt(time());
        $areaComment->setUpdatedAt(time());
        $areaComment->setUser($user);

        $areaComment = $this->areaCommentRepository->insert($areaComment);

        return new Response();
    }

    public function getDistrictsByCityId($cityId)
    {
        $city = $this->districtRepository->getOneApprovedById($cityId);

        if(is_null($city) || $city->getType() !== DistrictType::CITY) {
            $data = [
                'errors' => [
                    'city_id' => ['validation.exists']
                ]
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        $areas = $this->areaRepository->getAllByCityIdsAndType([$cityId], AreaType::DISTRICT);
        $districtIds = $areas->pluck('districtId')->all();
        $districts = $this->districtRepository->getAllByIds($districtIds);

        $data = [
            'districts' => (new DistrictResourceCollection($districts))->toArray()
        ];

        return new Response($data);
    }

    public function getAroundAreas($id)
    {
        $area = $this->areaRepository->getOneById($id);

        if(is_null($area)) {
            $data = [
                'errors' => [
                    'area_id' => ['validation.exists']
                ]
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        $district = $this->districtRepository->getOneApprovedById($area->getDistrictId());

        if(is_null($district)) {
            $data = [
                'errors' => [
                    'district_id' => ['validation.exists']
                ]
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        $aroundDistricts = $this->districtRepository->getAllAroundByDistricts(collect([$district]));
        $aroundDistrictIds = $aroundDistricts->pluck('id')->all();

        $aroundAreas = $this->areaRepository->getAllByDistrictIdsAndType($aroundDistrictIds, AreaType::DISTRICT);

        if($aroundAreas->isNotEmpty()) {
            $aroundAreas = $aroundAreas->take(20);

            $media = $this->mediaRepository->getAllByItemTypeAndItemIds(MediaItemType::AREA, $aroundAreas->pluck('id')->all())->groupBy('itemId');

            foreach($aroundAreas as $area) {
                $area->setMedia($media[$area->getId()] ?? collect());
            }
        }

        $data = [
            'areas' => (new AreaCompactResourceCollection($aroundAreas))->toArray()
        ];

        return new Response($data);
    }
}
