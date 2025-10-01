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
use OpenApi\Attributes as OA;
use utils\PagingResponse;
/**
 * Class LanguagesApiController
 * @package App\Http\Controllers
 */
final class LanguagesApiController extends JsonController
{
    use RequestProcessor;
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
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success - Returns paginated list of languages',
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedISOLanguageElementResponseSchema'),
            ),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Not Found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ]
    )]
    public function getAll()
    {
        return $this->processRequest(function () {
            $languages = $this->language_repository->getAll();
            $response = new PagingResponse
            (
                count($languages),
                count($languages),
                1,
                1,
                $languages
            );

            return $this->ok($response->toArray());
        });
    }

}
