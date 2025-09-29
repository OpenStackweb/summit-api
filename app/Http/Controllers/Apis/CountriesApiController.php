<?php namespace App\Http\Controllers;
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
use App\Models\Foundation\Main\CountryCodes;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use OpenApi\Attributes as OA;
use utils\PagingResponse;
use Illuminate\Support\Facades\Request;

/**
 * Class CountriesApiController
 * @package App\Http\Controllers
 */
final class CountriesApiController extends JsonController
{
    #[OA\Get(
        path: "/api/public/v1/countries",
        description: "Get all countries with ISO codes",
        summary: 'Get all countries',
        operationId: 'getAllCountries',
        tags: ['country', 'countries', 'ISO'],
        parameters: [
            new OA\Parameter(
                name: 'expand',
                in: 'query',
                required: false,
                description: 'Parameter for expanding related entity properties through serialization. Note: Has no effect on this endpoint since countries are returned as simple arrays, not complex entities. Always returns iso_code and name regardless of this parameter.',
                schema: new OA\Schema(type: 'string', example: '')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success - Returns paginated list of countries',
                content: new OA\JsonContent(
                    properties: [
                        'total' => new OA\Property(property: 'total', type: 'integer', example: 195),
                        'per_page' => new OA\Property(property: 'per_page', type: 'integer', example: 195),
                        'current_page' => new OA\Property(property: 'current_page', type: 'integer', example: 1),
                        'last_page' => new OA\Property(property: 'last_page', type: 'integer', example: 1),
                        'data' => new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    'iso_code' => new OA\Property(property: 'iso_code', type: 'string', example: 'US'),
                                    'name' => new OA\Property(property: 'name', type: 'string', example: 'United States')
                                ],
                                type: 'object'
                            )
                        )
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function getAll(){
        try {
            $countries = [];
            foreach(CountryCodes::$iso_3166_countryCodes as $iso_code => $name){
                $countries[] = [
                    'iso_code' => $iso_code,
                    'name' => $name,
                ];
            }

            $response    = new PagingResponse
            (
                count($countries),
                count($countries),
                1,
                1,
                $countries
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
