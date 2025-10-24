<?php namespace App\Http\Controllers;
/**
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

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;
use ModelSerializers\SerializerRegistry;

class OAuth2SpeakersPromoCodesApiController extends OAuth2ProtectedController
{
    /**
     * @param int $promo_code_id
     * @param Request $request
     * @return mixed
     */
    #[OA\Get(
        path: "/api/v1/summits/{id}/speakers-promo-codes/{promo_code_id}/speakers",
        operationId: "getSpeakersPromoCodeSpeakers",
        description: "Get speakers assigned to a speakers promo code",
        tags: ["Speakers Promo Codes"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "Summit ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64")
            ),
            new OA\Parameter(
                name: "promo_code_id",
                description: "Promo Code ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64")
            ),
            new OA\Parameter(
                name: "expand",
                description: "Expandable relations",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string", default: "")
            ),
            new OA\Parameter(
                name: "fields",
                description: "Fields to retrieve",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string", default: "")
            ),
            new OA\Parameter(
                name: "relations",
                description: "Relations to include",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string", default: "")
            ),
            new OA\Parameter(
                name: "page",
                description: "Page number",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer", default: 1)
            ),
            new OA\Parameter(
                name: "per_page",
                description: "Items per page",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer", default: 10)
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "List of speakers assigned to promo code",
                content: new OA\JsonContent(ref: "#/components/schemas/PaginateDataSchemaResponse")
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized"
            ),
            new OA\Response(
                response: Response::HTTP_FORBIDDEN,
                description: "Forbidden"
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found"
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error"
            ),
        ]
    )]
    public function getSpeakersPromoCodeSpeakers($id, $promo_code_id, Request $request)
    {
        try {
            return $this->_getAll(
                function () use ($id, $promo_code_id) {
                    return \App\Models\Summit\SpeakersSummitRegistrationPromoCode::findOrFail($promo_code_id)
                        ->getOwners();
                },
                SerializerRegistry::SerializerType_Public,
                [],
                function ($query) {},
                $request,
                null
            );
        } catch (\Exception $ex) {
            \Log::warning($ex);
            return $this->error500();
        }
    }

    /**
     * @param int $promo_code_id
     * @param int $speaker_id
     * @param Request $request
     * @return mixed
     */
    #[OA\Post(
        path: "/api/v1/summits/{id}/speakers-promo-codes/{promo_code_id}/speakers",
        operationId: "addSpeakerToPromoCode",
        description: "Add a speaker to a speakers promo code",
        tags: ["Speakers Promo Codes"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "Summit ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64")
            ),
            new OA\Parameter(
                name: "promo_code_id",
                description: "Promo Code ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64")
            ),
        ],
        requestBody: new OA\RequestBody(
            description: "Speaker to add",
            required: true,
            content: new OA\JsonContent(
                required: ["speaker_id"],
                properties: [
                    new OA\Property(
                        property: "speaker_id",
                        description: "Speaker ID to assign",
                        type: "integer",
                        format: "int64"
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: "Speaker added to promo code successfully"
            ),
            new OA\Response(
                response: Response::HTTP_BAD_REQUEST,
                description: "Bad Request"
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized"
            ),
            new OA\Response(
                response: Response::HTTP_FORBIDDEN,
                description: "Forbidden"
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found"
            ),
            new OA\Response(
                response: Response::HTTP_PRECONDITION_FAILED,
                description: "Validation Error"
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error"
            ),
        ]
    )]
    public function addSpeakerToPromoCode($id, $promo_code_id, Request $request)
    {
        try {
            $payload = $request->all();

            if (!isset($payload['speaker_id'])) {
                return $this->error400();
            }

            $promo_code = \App\Models\Summit\SpeakersSummitRegistrationPromoCode::findOrFail($promo_code_id);
            $speaker = \App\Models\Presentation\PresentationSpeaker::findOrFail($payload['speaker_id']);

            $promo_code->addOwner($speaker);

            return $this->created();
        } catch (\ModelNotFoundException $ex) {
            return $this->error404();
        } catch (\ValidationException $ex) {
            return $this->error412($ex->getMessages());
        } catch (\Exception $ex) {
            \Log::warning($ex);
            return $this->error500();
        }
    }

    /**
     * @param int $promo_code_id
     * @param int $speaker_id
     * @param Request $request
     * @return mixed
     */
    #[OA\Delete(
        path: "/api/v1/summits/{id}/speakers-promo-codes/{promo_code_id}/speakers/{speaker_id}",
        operationId: "removeSpeakerFromPromoCode",
        description: "Remove a speaker from a speakers promo code",
        tags: ["Speakers Promo Codes"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "Summit ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64")
            ),
            new OA\Parameter(
                name: "promo_code_id",
                description: "Promo Code ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64")
            ),
            new OA\Parameter(
                name: "speaker_id",
                description: "Speaker ID to remove",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64")
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_NO_CONTENT,
                description: "Speaker removed from promo code successfully"
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized"
            ),
            new OA\Response(
                response: Response::HTTP_FORBIDDEN,
                description: "Forbidden"
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found"
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error"
            ),
        ]
    )]
    public function removeSpeakerFromPromoCode($id, $promo_code_id, $speaker_id, Request $request)
    {
        try {
            $promo_code = \App\Models\Summit\SpeakersSummitRegistrationPromoCode::findOrFail($promo_code_id);
            $speaker = \App\Models\Presentation\PresentationSpeaker::findOrFail($speaker_id);

            $promo_code->removeOwner($speaker);

            return $this->deleted();
        } catch (\ModelNotFoundException $ex) {
            return $this->error404();
        } catch (\Exception $ex) {
            \Log::warning($ex);
            return $this->error500();
        }
    }
}