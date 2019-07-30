<?php namespace models\utils;
/**
 * Copyright 2019 OpenStack Foundation
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

/**
 * Trait One2ManyPropertyTrait
 * @package models\utils
 */
trait One2ManyPropertyTrait
{
    /**
     * @param $name
     * @param $arguments
     * @return bool|int|null
     */
    public function __call($name, $arguments)
    {
        $property = $this->getIdMappings[$name] ?? null;
        if(!is_null($property)) {
            return $this->getPropertyId($property);
        }
        $property = $this->hasPropertyMappings[$name] ?? null;
        if(!is_null($property)) {
            return $this->hasPropertySet($property);
        }
        return null;
    }

    /**
     * @param string $property_name
     * @return int
     */
    public function getPropertyId(string $property_name){
        try {
            return is_null($this->{$property_name}) ? 0 : $this->{$property_name}->getId();
        }
        catch(\Exception $ex){
            return 0;
        }
    }

    /**
     * @param string $property_name
     * @return bool
     */
    public function hasPropertySet(string $property_name):bool{
        return $this->getPropertyId($property_name) > 0;
    }
}