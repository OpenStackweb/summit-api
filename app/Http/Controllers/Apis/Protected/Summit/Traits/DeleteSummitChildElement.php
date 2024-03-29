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

use models\summit\Summit;

/**
 * Trait DeleteSummitChildElement
 * @package App\Http\Controllers
 */
trait DeleteSummitChildElement
{
    use BaseSummitAPI;

    use RequestProcessor;

    /**
     * @param Summit $summit
     * @param $child_id
     * @return void
     */
    abstract protected function deleteChild(Summit $summit, $child_id):void;

    /**
     * @param $summit_id
     * @param $child_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function delete($summit_id, $child_id){
        return $this->processRequest(function () use ($summit_id, $child_id) {

            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->deleteChild($summit, $child_id);

            return $this->deleted();
        });
    }
}