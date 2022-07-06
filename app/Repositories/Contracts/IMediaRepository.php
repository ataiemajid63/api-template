<?php

namespace App\Repositories\Contracts;

use App\Entities\Media;
use Illuminate\Support\Collection;

interface IMediaRepository
{
    /**
     * @param Media $medium
     *
     * @return Media
     */
    public function insert(Media $medium): Media;

    /**
     * @param Media $medium
     *
     * @return bool
     */
    public function delete(Media $medium);

    /**
     * @param string $itemType
     * @param int $itemId
     * @param bool $withTrashed
     *
     * @return Collection|Media[]
     */
    public function getAllByItemTypeAndItemId($itemType, $itemId, $withTrashed = false): Collection;

    /**
     * @param string $itemType
     * @param int[] $itemIds
     * @param bool $withTrashed
     *
     * @return Collection|Media[]
     */
    public function getAllByItemTypeAndItemIds($itemType, $itemIds, $withTrashed = false): Collection;

    /**
     * @param string $itemType
     * @param int $itemId
     * @param bool $withTrashed
     *
     * @return Media|null
     */
    public function getLatestByItemTypeAndItemId($itemType, $itemId, $withTrashed = false): ?Media;
}
