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
use App\Models\Foundation\Summit\Repositories\ISummitAttendeeBadgeRepository;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use ModelSerializers\SerializerRegistry;
use utils\Filter;
use utils\FilterElement;

/**
 * Class OAuth2SummitBadgesApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitBadgesApiController extends OAuth2ProtectedController
{
    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    // traits
    use ParametrizedGetAll;

    public function __construct
    (
        ISummitAttendeeBadgeRepository $repository,
        ISummitRepository $summit_repository,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->repository = $repository;
        $this->summit_repository = $summit_repository;
    }

    /**
     * @return ISummitRepository
     */
    protected function getSummitRepository(): ISummitRepository
    {
        return $this->summit_repository;
    }

    /**
     * @param $summit_id
     * @return mixed
     */
    public function getAllBySummit($summit_id){

        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->_getAll(
            function(){
                return [
                    'owner_first_name'           => ['=@', '=='],
                    'owner_last_name'            => ['=@', '=='],
                    'owner_full_name'            => ['=@', '=='],
                    'owner_email'                => ['=@', '=='],
                    'ticket_number'              => ['=@', '=='],
                    'order_number'               => ['=@', '=='],
                ];
            },
            function(){
                return [
                    'owner_first_name'           => 'sometimes|string',
                    'owner_last_name'            => 'sometimes|string',
                    'owner_full_name'            => 'sometimes|string',
                    'owner_email'                => 'sometimes|string',
                    'ticket_number'               => 'sometimes|string',
                    'order_number'                => 'sometimes|string',
                ];
            },
            function()
            {
                return [
                    'id',
                    'ticket_number',
                    'order_number',
                    'created'
                ];
            },
            function($filter) use($summit){
                if($filter instanceof Filter){
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));
                }
                return $filter;
            },
            function(){
                return SerializerRegistry::SerializerType_Private;
            }
        );
    }

    /**
     * @param $summit_id
     * @return mixed
     */
    public function getAllBySummitCSV($summit_id){

        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->_getAllCSV(
            function(){
                return [
                    'owner_first_name'           => ['=@', '=='],
                    'owner_last_name'            => ['=@', '=='],
                    'owner_full_name'            => ['=@', '=='],
                    'owner_email'                => ['=@', '=='],
                    'ticket_number'              => ['=@', '=='],
                    'order_number'               => ['=@', '=='],
                ];
            },
            function(){
                return [
                    'owner_first_name'           => 'sometimes|string',
                    'owner_last_name'            => 'sometimes|string',
                    'owner_full_name'            => 'sometimes|string',
                    'owner_email'                => 'sometimes|string',
                    'ticket_number'               => 'sometimes|string',
                    'order_number'                => 'sometimes|string',
                ];
            },
            function()
            {
                return [
                    'id',
                    'ticket_number',
                    'order_number',
                    'created'
                ];
            },
            function($filter) use($summit){
                if($filter instanceof Filter){
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));
                }
                return $filter;
            },
            function(){
                return SerializerRegistry::SerializerType_Private;
            },
            function(){
                return [];
            },
            function(){
                return [];
            },
            'attendees-badges-'
        );
    }



}