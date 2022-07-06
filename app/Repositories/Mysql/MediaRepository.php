<?php

namespace App\Repositories\Mysql;

use App\Entities\Media;
use App\Factories\MediaFactory;
use App\Repositories\Contracts\IMediaRepository;
use Illuminate\Support\Collection;

class MediaRepository extends Repository implements IMediaRepository
{
    public function __construct()
    {
        parent::__construct();

        $this->table = 'media';
    }

    public function insert(Media $medium): Media
    {
        $query = $this->query();

        $id = $query->insertGetId([
            'user_id' => $medium->getUserId(),
            'item_id' => $medium->getItemId(),
            'item_type' => $medium->getItemType(),
            'type' => $medium->getType(),
            'file_name' => $medium->getFileName(),
            'code' => $medium->getCode(),
            'title' => $medium->getTitle(),
            'ordering' => $medium->getOrdering(),
            'is_cover' => $medium->getIsCover(),
            'verified_at' => $medium->getVerifiedAt(),
            'created_at' => $medium->getCreatedAt(),
            'updated_at' => $medium->getUpdatedAt(),
            'deleted_at' => $medium->getDeletedAt(),
        ]);

        $medium->setId($id);

        return $medium;
    }

    public function delete(Media $medium)
    {
        $query = $this->query();

        $query->where('id', $medium->getId());
        $query->whereNull('deleted_at');

        return (bool)$query->update(['deleted_at' => time()]);
    }

    public function deleteByItem($itemType, $itemId)
    {
        $query = $this->query();

        $query->where('item_type', $itemType);
        $query->where('item_id', $itemId);
        $query->whereNull('deleted_at');

        return (bool)$query->update(['deleted_at' => time()]);
    }

    public function getAllByItemTypeAndItemId($itemType, $itemId, $withTrashed = false): Collection
    {
        $query = $this->query();

        if(!$withTrashed) {
            $query->whereNull('deleted_at');
        }

        $query->where('item_type', $itemType);
        $query->where('item_id', $itemId);

        $query->orderBy('ordering');

        $entities = $query->get();

        return (new MediaFactory())->makeFromCollection($entities);
    }

    public function getAllByItemTypeAndItemIds($itemType, $itemIds, $withTrashed = false): Collection
    {
        $query = $this->query();

        if(!$withTrashed) {
            $query->whereNull('deleted_at');
        }

        $query->where('item_type', $itemType);
        $query->whereIn('item_id', $itemIds);

        $query->orderBy('item_id');
        $query->orderBy('ordering');

        $entities = $query->get();

        return (new MediaFactory())->makeFromCollection($entities);
    }

    public function getLatestByItemTypeAndItemId($itemType, $itemId, $withTrashed = false): ?Media
    {
        $query = $this->query();

        if(!$withTrashed) {
            $query->whereNull('deleted_at');
        }

        $query->where('item_type', $itemType);
        $query->where('item_id', $itemId);

        $query->orderByDesc('created_at');

        $entity = $query->first();

        return $entity ? (new MediaFactory())->make($entity) : null;
    }
}
