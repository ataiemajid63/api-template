<?php

namespace App\Http\Controllers\V1;

use App\Entities\SearchProperty;
use App\Enums\AreaStatus;
use App\Enums\DistrictType;
use App\Enums\PostType;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\UrlQueryParamsResource;
use App\Http\Response;
use App\Repositories\AreaRepository;
use App\Repositories\DistrictRepository;
use App\Repositories\PostRepository;
use App\Repositories\PropertyRepository;
use App\Repositories\QuestionRepository;
use App\Repositories\RedirectRepository;
use App\UseCases\UrlProcessor;
use Illuminate\Http\Request;
use Throwable;

class LinksController extends Controller
{
    private $urlProcessor;
    private $redirectRepository;
    private $propertyRepository;
    private $districtRepository;
    private $areaRepository;
    private $questionRepository;
    private $postRepository;

    public function __construct(UrlProcessor $urlProcessor, RedirectRepository $redirectRepository, PropertyRepository $propertyRepository, DistrictRepository $districtRepository, AreaRepository $areaRepository, QuestionRepository $questionRepository, PostRepository $postRepository)
    {
        parent::__construct();

        $this->urlProcessor = $urlProcessor;
        $this->redirectRepository = $redirectRepository;
        $this->propertyRepository = $propertyRepository;
        $this->districtRepository = $districtRepository;
        $this->areaRepository = $areaRepository;
        $this->questionRepository = $questionRepository;
        $this->postRepository = $postRepository;
    }

    public function check(Request $request)
    {
        $decodedUrl = urldecode($request->get('url'));
        $url = preg_replace('/[+ ]/', '-', $decodedUrl);
        $urlComponents = parse_url($url);
        $path = isset($urlComponents['path']) ? trim($urlComponents['path'], '/ ') : '';
        $areaLinkPattern = '/^محله/';
        $customLinkPattern = '/^c\//';
        $questionLinkPattern = '/پرسش-پاسخ/';
        $postLinkPattern = '/blog/';
        $agenciesPagePattern = '/(?:[^\s]*)مشاورین-املاک(?:[^\s]*)/';

        if ($result = $this->checkRedirect($url)) {
            return $result;
        }

        if (preg_match($areaLinkPattern, $path)) {
            return $this->checkAreaUrl($url);
        }

        if (preg_match($customLinkPattern, $path)) {
            return $this->checkCustomUrl($url);
        }

        if (preg_match($questionLinkPattern, $path)) {
            return $this->checkQuestionUrl($url);
        }

        if (preg_match($postLinkPattern, $path)) {
            return $this->checkPostUrl($url, PostType::BLOG);
        }

        if (preg_match($agenciesPagePattern, $path)) {
            return $this->checkAgenciesPageUrl($url, $path);
        }

        return $this->checkGeneralUrl($url);
    }

    public function checkRedirect($url)
    {
        $parsedUrl = $this->urlProcessor->parse($url);
        $hashedPath = $this->urlProcessor->hashUrlPath($url);
        $redirect = $this->redirectRepository->getOneByOldLinkHash($hashedPath);
        $data = null;

        if ($redirect && $redirect->getRedirectedTo() && empty($parsedUrl['property_slug'])) {
            $link = env('APP_SITE_URL') . $redirect->getRedirectedTo();

            $this->redirectRepository->increaseAccessCount($hashedPath);

            $data = [
                'query' => null,
                'property_id' => null,
                'redirect' => true,
                'link' => $link,
            ];
        }

        return $data;
    }

    public function checkCustomUrl($url)
    {
        $urlComponents = parse_url($url);
        $query = preg_replace('/page=\d*/m', '', $urlComponents['query'] ?? '');
        $link = $urlComponents['scheme'] . '://' . $urlComponents['host'] . '/' . trim($urlComponents['path'], ' /') . (isset($query) ? ('?' . $query) : '');

        return [
            'redirect' => trim($url, ' /&?') !== trim($link, ' /&?'),
            'link' => trim($link, ' /&?')
        ];
    }

    public function checkAreaUrl($url)
    {
        $urlComponents = parse_url($url);
        $pathSegments = explode('/', trim($urlComponents['path'], '/ '));

        $segment = 1;
        $city = isset($pathSegments[$segment]) ? $this->districtRepository->getOneApprovedByNameAndType(str_replace('-', ' ', $pathSegments[$segment]), [DistrictType::AREA, DistrictType::CITY]) : null;

        if ($city && $city->getName() === 'شمال') {
            $segment++;
            $city = isset($pathSegments[$segment]) ? $this->districtRepository->getOneApprovedByNameAndType(str_replace('-', ' ', $pathSegments[$segment]), [DistrictType::AREA, DistrictType::CITY]) : null;
        }

        $isNorthernCity = $this->districtRepository->isNorthernCity($city->getId());
        $isCapitalCity = $city->getIsProvinceCapital();

        $segment++;
        $district = isset($pathSegments[$segment]) ? $this->districtRepository->getOneApprovedByNameAndTypeAndCityId(str_replace('-', ' ', $pathSegments[$segment]), [DistrictType::DISTRICT, DistrictType::REGION], $city->getId()) : null;

        if (is_null($city)) {
            return [
                'city_id' => null,
                'district_id' => null,
                'area_id' => null,
                'redirect' => false,
                'link' => null
            ];
        }

        if ($isNorthernCity && !$isCapitalCity) {
            $path = 'محله/شمال' . '/' . str_replace(' ', '-', $city->getName()) . ($district ? ('/' . str_replace(' ', '-', $district->getName())) : '');
        } else {
            $path = 'محله' . '/' . str_replace(' ', '-', $city->getName()) . ($district ? ('/' . str_replace(' ', '-', $district->getName())) : '');
        }

        $area = $this->areaRepository->getOneByDistrictId($district ? $district->getId() : $city->getId(), AreaStatus::ACTIVE);

        $link = $urlComponents['scheme'] . '://' . $urlComponents['host'] . '/' . $path . (isset($urlComponents['query']) ? ('?' . $urlComponents['query']) : '');

        return [
            'city_id' => $city->getId(),
            'district_id' => $district ? $district->getId() : null,
            'area_id' => $area ? $area->getId() : null,
            'redirect' => trim($url, ' /&') !== trim($link, ' /&'),
            'link' => $link
        ];
    }

    public function checkQuestionUrl($url)
    {
        $urlComponents = parse_url($url);
        $pathSegments = explode('/', trim($urlComponents['path'], '/ '));
        $explode = explode('-', $pathSegments[1]);
        $slug = $explode[1];

        $question = $this->questionRepository->getOneBySlug($slug);

        return [
            'district_id' => $question ? $question->getDistrictId() : null,
            'question_id' => $question ? $question->getId() : null,
            'redirect' => false,
            'link' => $url
        ];
    }

    public function checkPostUrl($url, $type)
    {
        $urlComponents = parse_url($url);
        $pathSegments = explode('/', trim($urlComponents['path'], '/ '));
        $postUrl = $pathSegments[1];

        $post = $this->postRepository->getOneByUrl($postUrl);

        return [
            'post_id' => $post ? $post->getId() : null,
            'redirect' => false,
            'link' => $url
        ];
    }

    public function checkAgenciesPageUrl($url, $path): array
    {
        $urlParts = explode('/', $path);

        $city = null;
        $district = null;
        $redirect = false;

        if (count($urlParts)) {
            $cityName = str_replace('املاک-', '', $urlParts[0]);
            $districtName = count($urlParts) > 2 ? $urlParts[2] : null;

            $city = $this->districtRepository->getOneApprovedByNameAndType(preg_replace('/-/', ' ', $cityName), [DistrictType::CITY, DistrictType::AREA]);

            if (empty($city) || empty($city->getId())) {
                $url = str_replace($path, '', $url);

                $redirect = true;
            }

            if (!empty($districtName) and !empty($city)) {
                $district = $this->districtRepository->getOneApprovedByNameAndTypeAndCityId(preg_replace('/-/', ' ', $districtName), [DistrictType::DISTRICT, DistrictType::REGION], $city->getId());

                if (empty($district) || empty($district->getId())) {
                    $url = str_replace("/$districtName", '', $url);

                    $redirect = true;
                }
            }
        } else {
            $url = str_replace($path, '', $url);
            $redirect = true;
        }

        return [
            'city_id' => !empty($city) ? $city->getId() : null,
            'district_id' => !empty($district) ? $district->getId() : null,
            'redirect' => $redirect,
            'link' => $url
        ];
    }

    public function checkGeneralUrl($url)
    {
        try {
            $parsedUrl = $this->urlProcessor->parse($url);

            switch ($this->urlProcessor->typeOfUrl($parsedUrl)) {
                case 'property':
                    $property = $this->propertyRepository->getOneBySlugWithUser($parsedUrl['property_slug']);

                    if ($property) {
                        $link = $this->urlProcessor->makePropertyUrl($property);
                        $link .= isset($parsedUrl['media']) ? ('?media=' . $parsedUrl['media']) : '';

                        $searchProperty = new SearchProperty();
                        $searchProperty->setCityId($property->getCityId());
                        $searchProperty->setDistrictId($property->getDistrictId());
                    } else {
                        $link = $this->urlProcessor->fetchMainPathFromOldUrl($parsedUrl);
                    }
                    break;
                default:
                    $params = $this->urlProcessor->extractUrlParams($url);

                    $searchProperty = $this->urlProcessor->makeSearchProperty($params);

                    $isNorthernCity = $this->districtRepository->isNorthernCity($searchProperty->getCityId());

                    $softDeletedNorthCitiesIds = json_decode(app('settings')->value('soft_deleted_north_cities_ids')) ?? [];

                    if ($isNorthernCity && str_contains($url, 'ویلا-باغ')) {
                        $link = str_replace('ویلا-باغ', 'ویلا', $url);
                    } elseif (in_array($searchProperty->getCityId(), $softDeletedNorthCitiesIds)) {
                        $link = env('APP_SITE_URL') . '/' . 'املاک-شمال/خرید-فروش-ویلا';
                    } else {
                        $link = $this->urlProcessor->redirect($searchProperty);
                    }
                    if (!strstr($link, '/properties')) {
                        $params = $this->urlProcessor->extractUrlParams($link, true);
                    }

                    $searchProperty = $this->urlProcessor->makeSearchProperty($params);
            }

            // Removing digits of arrays of link
            $link = preg_replace('/\[\d\]/m', '[]', $link);

            if (app()->environment('local')) {
                $urlComponent = parse_url($url);
                $linkComponent = parse_url($link);

                $link = $urlComponent['scheme'] . '://' . $urlComponent['host'] . (isset($urlComponent['port']) ? (':' . $urlComponent['port']) : '') . (isset($linkComponent['path']) ? ('/' . trim($linkComponent['path'], ' /')) : '') . (isset($urlComponent['query']) ? ('?' . $urlComponent['query']) : '');
            } elseif (str_contains($url, 'utm_') || str_contains($url, 'badesaba')) {
                $urlComponent = parse_url($url);
                $queryParams = [];
                $utmParams = [];

                parse_str($urlComponent['query'], $queryParams);

                foreach ($queryParams as $param => $value) {
                    if (str_contains($param, 'utm_') || str_contains($param, 'badesaba')) {
                        $utmParams[$param] = $value;
                    }
                }

                if (!empty($utmParams)) {
                    $parsedLink = parse_url($link);

                    $link .= isset($parsedLink['query']) ? '&' : '?';

                    foreach ($utmParams as $param => $value) {
                        $link .= $param . '=' . $value . '&';
                    }
                }
            }

            $data = [
                'query' => isset($searchProperty) ? (new UrlQueryParamsResource($searchProperty))->toArray() : null,
                'property_id' => isset($property) ? $property->getId() : null,
                'redirect' => $link ? trim($url, ' /&') !== trim($link, ' /&') : false,
                'link' => trim($link, '&'),
            ];
        } catch (Throwable $e) {
            $data = [
                'query' => null,
                'property_id' => null,
                'redirect' => false,
                'link' => $url,
            ];
        }

        return (new Response($data));
    }
}
