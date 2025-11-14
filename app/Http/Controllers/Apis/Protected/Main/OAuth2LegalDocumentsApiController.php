<?php

namespace App\Http\Controllers;

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
use App\Models\Foundation\Main\Repositories\ILegalDocumentRepository;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\oauth2\IResourceServerContext;
use ModelSerializers\SerializerRegistry;
use OpenApi\Attributes as OA;
use Exception;
/**
 * Class OAuth2LegalDocumentsApiController
 * @package App\Http\Controllers
 */
final class OAuth2LegalDocumentsApiController extends OAuth2ProtectedController
{
    /**
     * OAuth2LegalDocumentsApiController constructor.
     * @param ILegalDocumentRepository $repository
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ILegalDocumentRepository $repository,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->repository = $repository;
    }

    // OpenAPI Documentation

    #[OA\Get(
        path: '/api/public/v1/legal-documents/{id}',
        summary: 'Get a legal document by ID or slug',
        description: 'Retrieves a legal document (privacy policy, terms of service, etc.) by its numeric ID or URL-friendly slug. This is a public endpoint that does not require authentication.',
        tags: ['Legal Documents'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Legal document ID (numeric) or slug (string)',
                schema: new OA\Schema(
                    type: 'string',
                    example: 'privacy-policy'
                )
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Legal document retrieved successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/LegalDocument')
            ),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "Legal document not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]

    /**
     * @param $id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getById($id){
        try{

            $document = $this->repository->getBySlug(trim($id));
            if(is_null($document))
                $document = $this->repository->getById(intval($id));

            if(is_null($document)) return $this->error404();

            return $this->ok(SerializerRegistry::getInstance()->getSerializer
            (
                $document
            )->serialize());
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
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }
}
