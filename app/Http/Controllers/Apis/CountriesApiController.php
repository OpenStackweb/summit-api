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
use OpenApi\Attributes as OA;
use utils\PagingResponse;

/**
 * Class CountriesApiController
 * @package App\Http\Controllers
 */
final class CountriesApiController extends JsonController
{
    use RequestProcessor;

    #[OA\Get(
        path: "/api/public/v1/countries",
        description: "Get all countries with ISO codes",
        summary: 'Get all countries',
        operationId: 'getAllCountries',
        tags: ['Countries'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success - Returns paginated list of countries',
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedISOCountryElementResponseSchema'),
            ),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function getAll()
    {
        return $this->processRequest(function () {
            $countries = [];
            foreach (CountryCodes::$iso_3166_countryCodes as $iso_code => $name) {
                $countries[] = [
                    'iso_code' => $iso_code,
                    'name' => $name,
                ];
            }

            $response = new PagingResponse
            (
                count($countries),
                count($countries),
                1,
                1,
                $countries
            );

            return $this->ok($response->toArray());
        });

    }
}
