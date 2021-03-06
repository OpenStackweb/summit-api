<?php namespace App\Models\Foundation\Main;
/**
 * Copyright 2018 OpenStack Foundation
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 **/
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;
use models\exceptions\ValidationException;
use Illuminate\Support\Facades\Log;
/**
 * Trait OrderableChilds
 * @package App\Models\Foundation\Main
 */
trait OrderableChilds
{
    /**
     * @param string|null $className
     * @return string
     */
    protected static function getOrderFieldForOrderableChild(?string $className = null):string{
        return 'order';
    }

    /**
     * @param array $collection
     * @param IOrderable $entity
     * @param int $new_order
     * @throws ValidationException
     */
    private static function recalculateOrderForCollection(array $collection , IOrderable $entity, $new_order){

        $former_order = 1;

        foreach ($collection as $e){
            if($e->getId() == $entity->getId()) break;
            $former_order++;
        }

        $collection = array_slice($collection,0, count($collection), false);
        $max_order  = count($collection);

        if($new_order > $max_order)
            throw new ValidationException(sprintf("Max Allowed order is %s.", $max_order));

        unset($collection[$former_order - 1]);

        $collection = array_merge
        (
            array_slice($collection, 0, $new_order-1 , true) ,
            [$entity] ,
            array_slice($collection, $new_order -1 , count($collection), true)
        );

        $order = 1;
        foreach($collection as $e){
            $e->setOrder($order);
            $order++;
        }
    }
    /**
     * @param Selectable $collection
     * @param IOrderable $element
     * @param int $new_order
     * @param string $className
     * @throws ValidationException
     */
    private static function recalculateOrderForSelectable(Selectable $collection, IOrderable $element, int $new_order, string $className = null){
        $criteria     = Criteria::create();
        $criteria->orderBy([static::getOrderFieldForOrderableChild($className) => 'ASC']);
        self::recalculateOrderForCollection($collection->matching($criteria)->toArray(), $element, $new_order);
    }

    /**
     * @param Selectable $collection
     * @param string $className
     */
    private static function resetOrderForSelectable(Selectable $collection, string $className = null):void{
        $criteria = Criteria::create();
        $criteria->orderBy([static::getOrderFieldForOrderableChild($className) => 'ASC']);
        $order = 1;
        foreach($collection->matching($criteria) as $e){
            Log::debug
            (
                sprintf
                (
                    "OrderableChilds::resetOrderForSelectable newOrder %s",
                    $order,
                )
            );
            $e->setOrder($order);
            $order++;
        }
    }
}