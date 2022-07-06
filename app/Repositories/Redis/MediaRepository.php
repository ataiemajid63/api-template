<?php

namespace App\Repositories\Redis;

use App\Entities\Media;
use App\Factories\MediaFactory;
use App\Repositories\Contracts\IMediaRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class MediaRepository extends Repository implements IMediaRepository
{

    public function __construct()
    {
        parent::__construct();

        $this->table = 'media';
        $this->ttl = 24 * 60 * 60; // 1 Day
    }

    public function insert(Media $medium) : Media
    {
        $key = $this->key('id', $medium->getId());
        $itemKey = $this->key('item', "{$medium->itemType}-{$medium->itemId}");

        $value = $medium->toArray(true);

        $this->set($key, $value, $this->ttl);
        $this->command('sadd', [$itemKey, $medium->getId()]);
        $this->command('expire', [$itemKey, $this->ttl]);

        return $medium;
    }

    public function bulkInsert(Collection $media)
    {
        $entities = [];
        $items = [];

        foreach($media as $medium) {
            $key = $this->key('id', $medium->getId());
            $itemKey = $this->key('item', "{$medium->itemType}-{$medium->itemId}");

            if(isset($items[$itemKey])) {
                $items[$itemKey][] = $medium->getId();
            } else {
                $items[$itemKey] = [$medium->getId()];
            }

            $entities[$key] = $medium->toArray(true);
        }

        $this->setMultiple($entities, $this->ttl);

        foreach($items as $key => $item) {
            $this->command('sadd', [$key, ...$item]);
            $this->command('expire', [$key, $this->ttl]);
        }
    }

    public function delete(Media $medium)
    {
        $key = $this->key('id', $medium->getId());

        $this->purge($key);

        return true;
    }

    public function deleteByItem($itemType, $itemId)
    {
        $itemKey = $this->key('item', "$itemType-$itemId");
        $ids = $this->command('smembers', [$itemKey]);

        $keys = [];

        foreach($ids as $id) {
            $keys[] = $this->key('id', $id);
        }

        if(!empty($keys)) {
            $this->purge([...$keys, $itemKey]);
        }

        return true;
    }

    public function getAllByItemTypeAndItemId($itemType, $itemId, $withTrashed = false): Collection
    {
        $key = $this->key('item', "$itemType-$itemId");
        $media = collect();

        $ids = $this->command('smembers', [$key]) ?: [];
        $keys = [];

        foreach($ids as $id) {
            $keys[] = $this->key('id', $id);
        }

        if(!empty($keys)) {
            $entities = $this->getMultiple($keys);

            $media = (new MediaFactory())->makeFromArray($entities);
        }

        if(count($ids) !== $media->count()) {
            $media = collect();
        }

        if(!$withTrashed) {
            $media = $media->filter(function ($medium) { return is_null($medium->getDeletedAt()); });
        }

        return $media;
    }

    public function getAllByItemTypeAndItemIds($itemType, $itemIds, $withTrashed = false): Collection
    {
        $media = collect();
        $ids = [];
        $keys = [];

        foreach($itemIds as $itemId) {
            $itemkey = $this->key('item', "$itemType-$itemId");

            $set = $this->command('smembers', [$itemkey]) ?: [];

            $ids = array_merge($ids, $set);
        }

        foreach($ids as $id) {
            $keys[] = $this->key('id', $id);
        }

        if(!empty($keys)) {
            $entities = $this->getMultiple($keys);

            $media = (new MediaFactory())->makeFromArray($entities);
        }

        if(!$withTrashed) {
            $media = $media->filter(function ($medium) { return is_null($medium->getDeletedAt()); });
        }

        if(count($ids) !== $media->count()) {
            $media = collect();
        }

        return $media;
    }

    public function getLatestByItemTypeAndItemId($itemType, $itemId, $withTrashed = false): ?Media
    {
        $key = $this->key('item', "$itemType-$itemId");

        $id = $this->command('smembers', [$key]) ?: [];

        $medium = $this->getOneById($id[0] ?? null);

        if(!is_null($medium) && !$withTrashed && !is_null($medium->getDeletedAt())) {
            $medium = null;
        }

        return $medium;
    }

    public function getOneById($id)
    {
        $key = $this->key('id', $id);

        $entity = $this->get($key);

        return $entity ? (new MediaFactory())->makeWithArray($entity) : null;
    }
}
