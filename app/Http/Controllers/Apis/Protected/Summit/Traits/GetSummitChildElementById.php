<?php namespace App\Http\Controllers;
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

use App\ModelSerializers\SerializerUtils;
use models\summit\Summit;
use models\utils\IEntity;
use ModelSerializers\SerializerRegistry;

/**
 * Trait GetSummitChildElementById
 * @package App\Http\Controllers
 */
trait GetSummitChildElementById
{
    use BaseSummitAPI;

    use RequestProcessor;

    /**
     * @param Summit $summit
     * @param $child_id
     * @return IEntity|null
     */
    abstract protected function getChildFromSummit(Summit $summit, $child_id): ?IEntity;

    /**
     * @return string
     */
    public function getChildSerializer()
    {
        return SerializerRegistry::SerializerType_Public;
    }

    /**
     * @param $summit_id
     * @param $child_id
     * @return mixed
     */
    public function get($summit_id, $child_id)
    {
        return $this->processRequest(function () use ($summit_id, $child_id) {
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit))
                return $this->error404();

            $child = $this->getChildFromSummit($summit, $child_id);
            if (is_null($child))
                return $this->error404();

            return $this->ok
            (
                SerializerRegistry::getInstance()->getSerializer($child, $this->getChildSerializer())->serialize
                (
                    SerializerUtils::getExpand(),
                    SerializerUtils::getFields(),
                    SerializerUtils::getRelations()
                )
            );
         });
    }

}