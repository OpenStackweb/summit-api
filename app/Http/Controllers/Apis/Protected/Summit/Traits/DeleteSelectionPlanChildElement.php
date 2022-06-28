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
use models\summit\Summit;
use Illuminate\Support\Facades\Log;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use Exception;
/**
 * Trait DeleteSelectionPlanChildElement
 * @package App\Http\Controllers
 */
trait DeleteSelectionPlanChildElement
{
    use BaseSummitAPI;

    /**
     * @param SelectionPlan $selection_plan
     * @param $child_id
     * @return void
     */
    abstract protected function deleteChild(SelectionPlan $selection_plan, $child_id):void;

    /**
     * @param $summit_id
     * @param $child_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function delete($summit_id, $selection_plan_id, $child_id){
        try {

            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $selection_plan = $summit->getSelectionPlanById($selection_plan_id);
            if (is_null($selection_plan)) return $this->error404();

            $this->deleteChild($selection_plan, $child_id);

            return $this->deleted();
        } catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        } catch (EntityNotFoundException $ex2) {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }
}