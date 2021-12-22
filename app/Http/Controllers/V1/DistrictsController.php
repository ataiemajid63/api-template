<?php

namespace App\Http\Controllers\V1;

use App\Enums\DistrictType;
use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Resources\IResourceCollection;
use App\Http\Resources\V1\DistrictCompactResourceCollection;
use App\Http\Resources\V1\DistrictResource;
use App\Http\Resources\V1\DistrictResourceCollection;
use App\Http\Response;
use App\Repositories\DistrictRepository;
use App\Repositories\PropertyRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DistrictsController extends Controller
{
    private $districtRepository;
    private $propertyRepository;

    public function __construct(DistrictRepository $districtRepository, PropertyRepository $propertyRepository)
    {
        $this->districtRepository = $districtRepository;
        $this->propertyRepository = $propertyRepository;
    }

    public function getCities(Request $request)
    {
        $userId = $request->user() ? $request->user()->getId() : null;
        $cities = $this->districtRepository->getAllByTypeAndUserId([DistrictType::CITY, DistrictType::AREA], $userId);

        $data = [
            'cities' => $this->makeDistrictResourceCollection($cities, $request->has('with_geo'))->toArray()
        ];

        return new Response($data);
    }

    public function getCitiesByAreaId(Request $request, $areaId)
    {
        $area = $this->districtRepository->getOneApprovedById($areaId);

        if(is_null($area) || $area->getType() !== DistrictType::AREA) {
            $data = [
                'errors' => [
                    'area_id' => ['validation.exists']
                ]
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        $districts = $this->districtRepository->getAllContainsByDistrictAndType($area, DistrictType::CITY);

        $data = [
            'districts' => $this->makeDistrictResourceCollection($districts, $request->has('with_geo'))->toArray()
        ];

        return new Response($data);
    }

    public function getDistricts(Request $request, $cityId)
    {
        $userId = $request->user() ? $request->user()->getId() : null;

        $types = [
            DistrictType::DISTRICT,
            DistrictType::REGION
        ];

        if($request->has('with_area')) {
            $types[] = DistrictType::AREA;
        }

        $districts = $this->districtRepository->getAllByCityIdAndTypeAndUserId($cityId, $types, $userId);

        $data = [
            'districts' => $this->makeDistrictResourceCollection($districts, $request->has('with_geo'))->toArray()
        ];

        return new Response($data);
    }

    public function getRegions(Request $request, $cityId)
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

        $districts = $this->districtRepository->getAllByCityIdAndTypeAndUserId($cityId, DistrictType::REGION, null);

        $data = [
            'districts' => $this->makeDistrictResourceCollection($districts, $request->has('with_geo'))->toArray()
        ];

        return new Response($data);
    }

    public function getTopDistricts(Request $request, $cityId)
    {
        $city = $this->districtRepository->getOneApprovedById($cityId);

        if(is_null($city) || !in_array($city->getType(), [DistrictType::CITY, DistrictType::AREA])) {
            $data = [
                'errors' => [
                    'city_id' => ['validation.exists']
                ]
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        $districts = $this->districtRepository->getAllTopsByCity($city);

        $data = [
            'districts' => $this->makeDistrictResourceCollection($districts, $request->has('with_geo'))->toArray()
        ];

        return new Response($data);
    }

    public function getCrowdedDistricts(Request $request, $cityId)
    {
        $city = $this->districtRepository->getOneApprovedById($cityId);

        if(is_null($city) || !in_array($city->getType(), [DistrictType::CITY, DistrictType::AREA])) {
            $data = [
                'errors' => [
                    'city_id' => ['validation.exists']
                ]
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        if($city->getType() === DistrictType::AREA) {
            $districts = $this->districtRepository->getAllContainsByDistrictAndType($city, DistrictType::CITY);
        }
        else {
            $districts = $this->districtRepository->getAllByCityIdAndTypeAndUserId($city->getId(), [DistrictType::DISTRICT, DistrictType::CITY]);
        }

        $count = $request->get('count', 50);

        if($districts->isNotEmpty()) {
            if($city->getType() === DistrictType::AREA) {
                $districtsByPropertiesCount = $this->propertyRepository->countByCities($districts)->sortByDesc('count')->keyBy('city_id');
            }
            else {
                $districtsByPropertiesCount = $this->propertyRepository->countByDistricts($districts)->sortByDesc('count')->keyBy('district_id');
            }

            $districtsTemp = collect();

            foreach($districtsByPropertiesCount as $districtId => $object) {
                if(isset($districts[$districtId])) {
                    $districtsTemp->add($districts[$districtId]);
                    unset($districts[$districtId]);
                }
            }

            $districts = $districtsTemp->concat($districts);
        }

        $data = [
            'districts' => $this->makeDistrictResourceCollection($districts->take($count), $request->has('with_geo'))->toArray()
        ];

        return new Response($data);
    }

    public function getDistrict($id)
    {
        $district = $this->districtRepository->getOneApprovedById($id);

        $data = [
            'district' => $district ? (new DistrictResource($district))->toArray() : null
        ];

        return new Response($data);
    }

    public function getAroundCity(Request $request)
    {
        $city = $this->districtRepository->getOneApprovedById($request->get('city_id', 0));

        if(is_null($city) || $city->getType() !== DistrictType::CITY) {
            $data = [
                'errors' => [
                    'city_id' => ['validation.exists']
                ]
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        $cities = $this->districtRepository->getAllAroundByCity($city);

        $data = [
            'cities' => $this->makeDistrictResourceCollection($cities->take(6), $request->has('with_geo'))->toArray()
        ];

        return new Response($data);
    }

    public function getAroundDistricts(Request $request)
    {
        $validator = $this->makeValidator($request, [
            'district_id' => 'required|array'
        ]);

        if($validator->fails()) {
            $data = [
                'errors' => $validator->errors()
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        $districts = $this->districtRepository->getAllByIds($request->get('district_id', []));
        $districts = $districts->filter(function ($district) { return $district->getType() === DistrictType::DISTRICT; });

        if($districts->isEmpty()) {
            $data = [
                'errors' => [
                    'district_id' => ['validation.exists']
                ]
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        $districts = $this->districtRepository->getAllAroundByDistricts($districts);

        $data = [
            'districts' => $this->makeDistrictResourceCollection($districts->take(6), $request->has('with_geo'))->toArray()
        ];

        return new Response($data);
    }

    public function getOverlappingDistricts(Request $request, $id, $type)
    {
        if(empty($type) || DistrictType::indexOf($type) === false) {
            $data = [
                'errors' => [
                    'type' => ['validation.exists']
                ]
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        $district = $this->districtRepository->getOneApprovedById($id);

        if(is_null($district)) {
            $data = [
                'errors' => [
                    'id' => ['validation.exists']
                ]
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        $districts = $this->districtRepository->getAllOverlappingByDistrictsAndType(collect([$district]), $type);

        $data = [
            'districts' => $this->makeDistrictResourceCollection($districts, $request->has('with_geo'))->toArray()
        ];

        return new Response($data);
    }

    public function getTopNearestDistricts(Request $request)
    {
        $validator = $this->makeValidator($request, [
            'district_id' => 'required|array'
        ]);

        if($validator->fails()) {
            $data = [
                'errors' => $validator->errors()
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        $districts = $this->districtRepository->getAllByIds($request->get('district_id', []));
        $districts = $districts->filter(function ($district) { return $district->getType() === DistrictType::DISTRICT; });
        $count = $request->get('count', 50);

        if($districts->isEmpty()) {
            $data = [
                'errors' => [
                    'district_id' => ['validation.exists']
                ]
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        $districts = $this->districtRepository->getAllAroundByDistricts($districts, $count)->keyBy('id');

        if($districts->isNotEmpty()) {
            $propertiesCount = $this->propertyRepository->countByDistricts($districts)->keyBy('district_id');
            $districtsTemp = collect();

            foreach($propertiesCount as $districtId => $object) {
                $districtsTemp->add($districts[$districtId]);
                unset($districts[$districtId]);
            }

            $districts = $districtsTemp->concat($districts);
        }

        $data = [
            'districts' => $this->makeDistrictResourceCollection($districts->take($count), $request->has('with_geo'))->toArray()
        ];

        return new Response($data);
    }

    /**
     * @param Collection<District> $districts
     * @param bool $withGeo
     *
     * @return IResourceCollection
     */
    private function makeDistrictResourceCollection(Collection $districts, $withGeo)
    {
        if($withGeo) {
            return new DistrictResourceCollection($districts);
        }

        return new DistrictCompactResourceCollection($districts);
    }
}
