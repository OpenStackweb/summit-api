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
use utils\Filter;
use utils\FilterElement;
/**
 * Trait GetAllBySummit
 * @package App\Http\Controllers
 */
trait GetAllBySummit
{

    use GetAll;

    /**
     * @var mixed
     */
    protected $summit_id;

    /**
     * @param Filter $filter
     * @return Filter
     */
    protected function applyExtraFilters(Filter $filter):Filter {
        $filter->addFilterCondition(FilterElement::makeEqual("summit_id", intval($this->summit_id)));
        return $filter;
    }

    /**
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getAllBySummit($summit_id){
        $this->summit_id = $summit_id;
        $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($this->summit_id);
        if (is_null($summit)) return $this->error404();
        return $this->getAll();
    }
}