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
use App\Models\Foundation\Main\Repositories\ILanguageRepository;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use OpenApi\Attributes as OA;
use utils\PagingResponse;
use Illuminate\Support\Facades\Request;
/**
 * Class LanguagesApiController
 * @package App\Http\Controllers
 */
final class LanguagesApiController extends JsonController
{
    /**
     * @var ILanguageRepository
     */
    private $language_repository;

    /**
     * LanguagesApiController constructor.
     * @param ILanguageRepository $language_repository
     */
    public function __construct(ILanguageRepository $language_repository)
    {
        $this->language_repository = $language_repository;
    }

    #[OA\Get(
        path: "/api/public/v1/languages",
        description: "Get all available languages with ISO codes",
        summary: 'Get all languages',
        operationId: 'getAllLanguages',
        tags: ['Languages'],
        parameters: [
            new OA\Parameter(
                name: 'expand',
                in: 'query',
                required: false,
                description: 'Parameter for expanding related entity properties through serialization. Note: Has no effect on this endpoint since languages are returned as simple arrays, not complex entities. Always returns iso_code and name regardless of this parameter.',
                schema: new OA\Schema(type: 'string', example: '')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success - Returns paginated list of languages',
                content: new OA\JsonContent(
                    properties: [
                        'total' => new OA\Property(property: 'total', type: 'integer', example: 50),
                        'per_page' => new OA\Property(property: 'per_page', type: 'integer', example: 50),
                        'current_page' => new OA\Property(property: 'current_page', type: 'integer', example: 1),
                        'last_page' => new OA\Property(property: 'last_page', type: 'integer', example: 1),
                        'data' => new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    'id' => new OA\Property(property: 'id', type: 'integer', example: 1),
                                    'name' => new OA\Property(property: 'name', type: 'string', example: 'English'),
                                    'iso_code' => new OA\Property(property: 'iso_code', type: 'string', example: 'en')
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
            $languages   = $this->language_repository->getAll();
            $response    = new PagingResponse
            (
                count($languages),
                count($languages),
                1,
                1,
                $languages
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
