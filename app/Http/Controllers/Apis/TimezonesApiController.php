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
use Illuminate\Http\Response;

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
        path: "/api/v1/timezones",
        summary: "Get all available timezones",
        description: "Returns a paginated list of all supported timezone identifiers.",
        operationId: "getAllTimezones",
        tags: ["Timezones"],
        parameters: [
            new OA\Parameter(
                name: "expand",
                in: "query",
                required: false,
                description: "Expand related resources (not used here, for compatibility)",
                schema: new OA\Schema(type: "string")
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Paginated list of timezone identifiers",
                content: new OA\JsonContent(ref: "#/components/schemas/PaginatedTimezonesResponse")
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized"
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found"
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error"
            ),
            new OA\Response(
                response: Response::HTTP_PRECONDITION_FAILED,
                description: "Validation Error"
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