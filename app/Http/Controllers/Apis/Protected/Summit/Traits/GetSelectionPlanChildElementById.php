<?php namespace App\Http\Controllers;
/**
 * Copyright 2022 OpenStack Foundation
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

use App\Models\Foundation\Summit\SelectionPlan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\summit\Summit;
use models\utils\IEntity;
use ModelSerializers\SerializerRegistry;
use Exception;
/**
 * Trait GetSelectionPlanChildElementById
 * @package App\Http\Controllers
 */
trait GetSelectionPlanChildElementById
{
    use BaseSummitAPI;

    /**
     * @param SelectionPlan $selection_plan
     * @param $child_id
     * @return IEntity|null
     */
    abstract protected function getChildFromSelectionPlan(SelectionPlan $selection_plan, $child_id):?IEntity;

    /**
     * @return string
     */
    public function getChildSerializer(){
        return SerializerRegistry::SerializerType_Public;
    }
    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $child_id
     * @return mixed
     */
    public function get($summit_id, $selection_plan_id, $child_id)
    {
        try {
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $selection_plan = $summit->getSelectionPlanById($selection_plan_id);
            if (is_null($selection_plan)) return $this->error404();
            $child = $this->getChildFromSelectionPlan($summit, $selection_plan, $child_id);
            if(is_null($child))
                return $this->error404();
            return $this->ok(SerializerRegistry::getInstance()->getSerializer($child, $this->getChildSerializer())->serialize(Request::input('expand', '')));
        } catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412($ex1->getMessages());
        } catch (EntityNotFoundException $ex2) {
            Log::warning($ex2);
            return $this->error404(['message' => $ex2->getMessage()]);
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

}