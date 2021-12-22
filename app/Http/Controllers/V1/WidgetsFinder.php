<?php

namespace App\Http\Controllers\V1;

use App\Entities\WidgetParam;
use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\PropertyResourceCollection;
use App\Http\Resources\V1\WidgetResourceCollection;
use App\Http\Response;
use App\Repositories\PropertyRepository;
use App\Repositories\WidgetRepository;
use Illuminate\Http\Request;

class WidgetsFinder extends Controller
{
    private $repository;
    private $propertyRepository;

    public function __construct(WidgetRepository $widgetRepository, PropertyRepository $propertyRepository)
    {
        parent::__construct();

        $this->repository = $widgetRepository;
        $this->propertyRepository = $propertyRepository;
    }

    public function __invoke(Request $request)
    {
        //validation

        $widgetParams = new WidgetParam();

        $widgetParams->setCityId($request->get('city_id'));
        $widgetParams->setDistrictId($request->get('district_id'));
        $widgetParams->setItemId($request->get('item_id'));
        $widgetParams->setType($request->get('type'));
        $widgetParams->setPropertiesTypes($request->get('properties_types'));
        $widgetParams->setPropertiesDealTypes($request->get('properties_deal_types'));
        $widgetParams->setPage($request->get('page'));
        $widgetParams->setSection($request->get('section'));
        $widgetParams->setUrl($request->get('url'));

        $count = $request->get('count', 20);
        $widgetParams->setCount($count <= 20 ? $count : 20);

        $widgets = $this->repository->getAllByWidgetParam($widgetParams);

        $data = [
            'widgets' => (new WidgetResourceCollection($widgets))->toArray()
        ];

        return new Response($data);
    }

    public function getWidgetById($id)
    {
        $widget = $this->repository->getWidgetById($id);

        if (empty($widget) or empty($widget->getId())) {
            $data = [
                'errors' => [
                    'widget_id' => 'required.widget_id'
                ]
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        $data = [
            'widget' => $widget
        ];

        return new Response($data);
    }

    public function getWidgetProperties($id)
    {
        $widget = $this->repository->getWidgetById($id);

        if (empty($widget) or empty($widget->getId())) {
            $data = [
                'errors' => [
                    'widget_id' => 'required.widget_id'
                ]
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        $query = json_decode($widget->query);
        $query->query = str_replace('`', '', $query->query);

        $properties = $this->propertyRepository->getAllByQuery($query->query, $query->binding);

        $data = [
            'properties' => (new PropertyResourceCollection($properties))->toArray()
        ];

        return new Response($data);
    }
}
