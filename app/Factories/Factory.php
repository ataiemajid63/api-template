<?php

namespace App\Factories;

use Illuminate\Support\Collection;

abstract class Factory
{
    public function __construct()
    {

    }

    /**
     *
     * @param \stdClass $entity
     *
     * @return Entity
     */
    public abstract function make(\stdClass $entity);

    /**
     *
     * @param array $entity
     *
     * @return Entity
     */
    public function makeWithArray(array $entity)
    {
        return $this->make((object)$entity);
    }

    /**
     * @param Collection $entities
     *
     * @return Collection
     */
    public function makeFromCollection(Collection $entities) : Collection
    {
        $items = new Collection();

        foreach($entities as $entity) {
            if($entity) {
                $items->add($this->make($entity));
            }
        }

        return $items;
    }

    /**
     * @param array $entities
     *
     * @return Collection
     */
    public function makeFromArray(array $entities)
    {
        $items = new Collection();

        foreach($entities as $entity) {
            if($entity) {
                $items->add($this->makeWithArray($entity));
            }
        }

        return $items;
    }
}
