<?php namespace App\Http\Controllers;

/**
 * Copyright 2021 OpenStack Foundation
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

use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Log;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use utils\PagingResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class TimezonesApiController
 * @package App\Http\Controllers
 */
final class TimezonesApiController extends JsonController
{
    /**
     * @return mixed
     */
    #[OA\Get(
        path: '/api/v1/timezones',
        operationId: 'getTimezones',
        description: 'Retrieve all available timezones',
        tags: ['Timezones'],
        parameters: [
            new OA\Parameter(
                name: 'expand',
                description: 'Expansion parameters',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'List of all available timezones',
                content: new OA\JsonContent(ref: '#/components/schemas/TimezonesResponse')
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: 'Server Error'
            ),
        ]
    )]
    public function getAll(){
        try {
            $timezones   = \DateTimeZone::listIdentifiers();
            $response    = new PagingResponse
            (
                count($timezones),
                count($timezones),
                1,
                1,
                $timezones
            );

            return $this->ok($response->toArray($expand = Request::input('expand','')));
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        }
        catch(EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message'=> $ex2->getMessage()));
        }
        catch (\Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }
}