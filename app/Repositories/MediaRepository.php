<?php

namespace App\Repositories;

use App\Entities\Media;
use App\Repositories\Contracts\IMediaRepository;
use App\Repositories\Mysql\MediaRepository as MediaMysqlRepository;
use App\Repositories\Redis\MediaRepository as MediaRedisRepository;
use App\Repositories\Postgres\MediaRepository as MediaPostgresRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class MediaRepository extends Repository implements IMediaRepository
{
    private $mediaMysqlRepository;
    private $mediaRedisRepository;
    private $mediaPostgresRepository;

    public function __construct(MediaMysqlRepository $mediaMysqlRepository, MediaRedisRepository $mediaRedisRepository, MediaPostgresRepository $mediaPostgresRepository)
    {
        parent::__construct();

        $this->mediaMysqlRepository = $mediaMysqlRepository;
        $this->mediaRedisRepository = $mediaRedisRepository;
        $this->mediaPostgresRepository = $mediaPostgresRepository;
    }

    public function insert(Media $medium): Media
    {
        $medium = $this->mediaPostgresRepository->insert($medium);

        if($medium->getId()) {
            $this->mediaRedisRepository->insert($medium);
        }

        return $medium;
    }

    public function delete(Media $medium)
    {
        if($this->mediaPostgresRepository->delete($medium)) {
            $this->mediaRedisRepository->delete($medium);

            return true;
        }

        return false;
    }

    public function deleteByItem($itemType, $itemId)
    {
        if($this->mediaPostgresRepository->deleteByItem($itemType, $itemId)) {
            $this->mediaRedisRepository->deleteByItem($itemType, $itemId);

            return true;
        }

        return false;
    }

    public function getAllByItemTypeAndItemId($itemType, $itemId, $withTrashed = false): Collection
    {
        $media = $this->mediaRedisRepository->getAllByItemTypeAndItemId($itemType, $itemId, $withTrashed);

        if($media->isEmpty()) {
            $media = $this->mediaPostgresRepository->getAllByItemTypeAndItemId($itemType, $itemId, $withTrashed);

            if($media->isNotEmpty()) {
                $this->mediaRedisRepository->bulkInsert($media);
            }
        }

        return $media;
    }

    public function getAllByItemTypeAndItemIds($itemType, $itemIds, $withTrashed = false): Collection
    {
        // $media = $this->mediaRedisRepository->getAllByItemTypeAndItemIds($itemType, $itemIds, $withTrashed);

        // if($media->isEmpty()) {
            $media = $this->mediaPostgresRepository->getAllByItemTypeAndItemIds($itemType, $itemIds, $withTrashed);

            if($media->isNotEmpty()) {
                $this->mediaRedisRepository->bulkInsert($media);
            }
        // }

        return $media;
    }

    public function getLatestByItemTypeAndItemId($itemType, $itemId, $withTrashed = false): ?Media
    {
        $medium = $this->mediaRedisRepository->getLatestByItemTypeAndItemId($itemType, $itemId, $withTrashed);

        if(is_null($medium)) {
            $medium = $this->mediaPostgresRepository->getLatestByItemTypeAndItemId($itemType, $itemId, $withTrashed);

            if(!is_null($medium)) {
                $this->mediaRedisRepository->insert($medium);
            }
        }

        return $medium;
    }

    public function purgeAllByItemTypeAndItemId($itemType, $itemId)
    {
        $this->mediaRedisRepository->deleteByItem($itemType, $itemId);
    }
}
