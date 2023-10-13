<?php namespace App\Http\Controllers;
/*
 * Copyright 2023 OpenStack Foundation
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

use App\Http\Utils\EpochCellFormatter;
use App\Models\Foundation\Summit\Repositories\ISummitAttendeeBadgePrintRepository;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use ModelSerializers\SerializerRegistry;
use utils\Filter;
use utils\FilterElement;


/**
 * Class OAuth2SummitAttendeeBadgePrintApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitAttendeeBadgePrintApiController
    extends OAuth2ProtectedController
{
    public function __construct
    (
        ISummitRepository               $summit_repository,
        ISummitAttendeeBadgePrintRepository $repository,
        IResourceServerContext          $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->repository = $repository;
        $this->summit_repository = $summit_repository;
    }

    use ParametrizedGetAll;

    public function getAllBySummitAndTicket($summit_id, $ticket_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->_getAll(
            function () {
                return [
                    'id' =>  ['=='],
                    'view_type_id' =>  ['=='],
                    'created' =>  ['>', '<', '<=', '>=', '==','[]'],
                    'print_date' =>  ['>', '<', '<=', '>=', '==','[]'],
                    'requestor_full_name' => ['==','@@','=@'],
                    'requestor_email' => ['==','@@','=@'],
                ];
            },
            function () {
                return [
                    'id' => 'sometimes|integer',
                    'view_type_id' => 'sometimes|integer',
                    'created' =>  'sometimes|date_format:U',
                    'print_date'=>  'sometimes|date_format:U',
                    'requestor_full_name' => 'sometimes|string',
                    'requestor_email' => 'sometimes|string',
                ];
            },
            function () {
                return [
                    'id',
                    'created',
                    'view_type_id',
                    'print_date',
                    'requestor_full_name',
                    'requestor_email',
                ];
            },
            function ($filter) use ($summit, $ticket_id) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));
                    $filter->addFilterCondition(FilterElement::makeEqual('ticket_id', intval($ticket_id)));
                }
                return $filter;
            }
        );
    }

    public function getAllBySummitAndTicketCSV($summit_id, $ticket_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->_getAllCSV(
            function () {
                return [
                    'id' =>  ['=='],
                    'view_type_id' =>  ['=='],
                    'created' =>   ['>', '<', '<=', '>=', '==','[]'],
                    'print_date' =>   ['>', '<', '<=', '>=', '==','[]'],
                    'requestor_full_name' => ['==','@@','=@'],
                    'requestor_email' => ['==','@@','=@'],
                ];
            },
            function () {
                return [
                    'id' => 'sometimes|integer',
                    'view_type_id' => 'sometimes|integer',
                    'created' =>  'sometimes|date_format:U',
                    'print_date'=>  'sometimes|date_format:U',
                    'requestor_full_name' => 'sometimes|string',
                    'requestor_email' => 'sometimes|string',
                ];
            },
            function () {
                return [
                    'id',
                    'created',
                    'view_type_id',
                    'print_date',
                    'requestor_full_name',
                    'requestor_email',
                ];
            },
            function ($filter) use ($summit, $ticket_id) {
                if ($filter instanceof Filter) {
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));
                    $filter->addFilterCondition(FilterElement::makeEqual('ticket_id', intval($ticket_id)));
                }
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_CSV;
            },
            function () {
                return [
                    'created' => new EpochCellFormatter(),
                ];
            },
            function () {
                return [];
            },
            'badge-prints-'
        );
    }
}