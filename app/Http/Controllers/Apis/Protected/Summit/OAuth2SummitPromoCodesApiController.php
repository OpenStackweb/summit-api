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

use App\Http\Utils\BooleanCellFormatter;
use App\Http\Utils\EpochCellFormatter;
use App\Models\Foundation\Summit\PromoCodes\PromoCodesConstants;
use App\Models\Foundation\Summit\Repositories\ISpeakersRegistrationDiscountCodeRepository;
use App\Models\Foundation\Summit\Repositories\ISpeakersSummitRegistrationPromoCodeRepository;
use App\ModelSerializers\SerializerUtils;
use Illuminate\Http\Request as LaravelRequest;
use models\main\IMemberRepository;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRegistrationPromoCodeRepository;
use models\summit\ISummitRepository;
use models\summit\SpeakersRegistrationDiscountCode;
use models\summit\SpeakersSummitRegistrationPromoCode;
use ModelSerializers\SerializerRegistry;
use services\model\ISummitPromoCodeService;
use utils\Order;
use utils\OrderElement;
use utils\PagingInfo;

/**
 * Class OAuth2SummitPromoCodesApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitPromoCodesApiController extends OAuth2ProtectedController
{

    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var IMemberRepository
     */
    private $member_repository;

    /**
     * @var ISpeakersSummitRegistrationPromoCodeRepository
     */
    private $speakers_promo_code_repository;

    /**
     * @var ISpeakersRegistrationDiscountCodeRepository
     */
    private $speakers_discount_code_repository;

    /**
     * @var ISummitPromoCodeService
     */
    private $promo_code_service;

    use RequestProcessor;

    use GetAndValidateJsonPayload;

    use ParametrizedGetAll;

    /**
     * OAuth2SummitPromoCodesApiController constructor.
     * @param ISummitRepository $summit_repository
     * @param ISummitRegistrationPromoCodeRepository $promo_code_repository
     * @param ISpeakersSummitRegistrationPromoCodeRepository $speakers_promo_code_repository
     * @param ISpeakersRegistrationDiscountCodeRepository $speakers_discount_code_repository
     * @param IMemberRepository $member_repository
     * @param ISummitPromoCodeService $promo_code_service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitRepository                               $summit_repository,
        ISummitRegistrationPromoCodeRepository          $promo_code_repository,
        ISpeakersSummitRegistrationPromoCodeRepository  $speakers_promo_code_repository,
        ISpeakersRegistrationDiscountCodeRepository     $speakers_discount_code_repository,
        IMemberRepository                               $member_repository,
        ISummitPromoCodeService                         $promo_code_service,
        IResourceServerContext                          $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->promo_code_service = $promo_code_service;
        $this->repository = $promo_code_repository;
        $this->summit_repository = $summit_repository;
        $this->member_repository = $member_repository;
        $this->speakers_promo_code_repository = $speakers_promo_code_repository;
        $this->speakers_discount_code_repository = $speakers_discount_code_repository;
    }

    /**
     * @param $summit_id
     * @return mixed
     */
    public function getAllBySummit($summit_id)
    {

        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->_getAll(
            function () {
                return [
                    'code' => ['@@', '=@', '=='],
                    'description' => ['@@', '=@'],
                    'creator' => ['@@', '=@', '=='],
                    'creator_email' => ['@@', '=@', '=='],
                    'owner' => ['@@', '=@', '=='],
                    'owner_email' => ['@@', '=@', '=='],
                    'speaker' => ['@@', '=@', '=='],
                    'speaker_email' => ['@@', '=@', '=='],
                    'sponsor' => ['@@', '=@', '=='],
                    'class_name' => ['=='],
                    'type' => ['=='],
                ];
            },
            function () {
                return [
                    'class_name' => sprintf('sometimes|in:%s', implode(',', PromoCodesConstants::$valid_class_names)),
                    'code' => 'sometimes|string',
                    'description' => 'sometimes|string',
                    'creator' => 'sometimes|string',
                    'creator_email' => 'sometimes|string',
                    'owner' => 'sometimes|string',
                    'owner_email' => 'sometimes|string',
                    'speaker' => 'sometimes|string',
                    'speaker_email' => 'sometimes|string',
                    'sponsor' => 'sometimes|string',
                    'type' => sprintf('sometimes|in:%s', implode(',', PromoCodesConstants::getValidTypes())),
                ];
            },
            function () {
                return [
                    'id',
                    'code',
                ];
            },
            function ($filter) {
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Private;
            },
            function () {
                return new Order([
                    OrderElement::buildAscFor("id"),
                ]);
            },
            null,
            function ($page, $per_page, $filter, $order, $applyExtraFilters) use ($summit) {
                return $this->repository->getBySummit
                (
                    $summit,
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            }
        );
    }

    /**
     * @param $summit_id
     * @return \Illuminate\Http\Response|mixed
     */
    public function getAllBySummitCSV($summit_id)
    {

        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->_getAllCSV(
            function () {
                return [
                    'code' => ['@@', '=@', '=='],
                    'description' => ['@@', '=@'],
                    'creator' => ['@@', '=@', '=='],
                    'creator_email' => ['@@', '=@', '=='],
                    'owner' => ['@@', '=@', '=='],
                    'owner_email' => ['@@', '=@', '=='],
                    'speaker' => ['@@', '=@', '=='],
                    'speaker_email' => ['@@', '=@', '=='],
                    'sponsor' => ['@@', '=@', '=='],
                    'class_name' => ['=='],
                    'type' => ['=='],
                ];
            },
            function () {
                return [
                    'class_name' => sprintf('sometimes|in:%s', implode(',', PromoCodesConstants::$valid_class_names)),
                    'code' => 'sometimes|string',
                    'description' => 'sometimes|string',
                    'creator' => 'sometimes|string',
                    'creator_email' => 'sometimes|string',
                    'owner' => 'sometimes|string',
                    'owner_email' => 'sometimes|string',
                    'speaker' => 'sometimes|string',
                    'speaker_email' => 'sometimes|string',
                    'sponsor' => 'sometimes|string',
                    'type' => sprintf('sometimes|in:%s', implode(',', PromoCodesConstants::getValidTypes())),
                ];
            },
            function () {
                return [
                    'id',
                    'code',
                ];
            },
            function ($filter) use ($summit) {
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_CSV;
            },
            function () {
                return [
                    'created' => new EpochCellFormatter,
                    'last_edited' => new EpochCellFormatter,
                    'redeemed' => new BooleanCellFormatter,
                    'email_sent' => new BooleanCellFormatter,
                ];
            },
            function () {
                return [
                    "id",
                    "created",
                    "last_edited",
                    "code",
                    "redeemed",
                    "email_sent",
                    "source",
                    "summit_id",
                    "creator_id",
                    "class_name",
                    "type",
                    "speaker_id",
                    "owner_name",
                    "owner_email",
                    "sponsor_name"
                ];
            },
            'promocodes-',
            [],
            function ($page, $per_page, $filter, $order, $applyExtraFilters) use ($summit) {
                return $this->repository->getBySummit
                (
                    $summit,
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            }
        );
    }

    /**
     * @param $summit_id
     * @return mixed
     */
    public function getMetadata($summit_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->ok
        (
            $this->repository->getMetadata($summit)
        );
    }

    /**
     * @param $summit_id
     * @return mixed
     */
    public function addPromoCodeBySummit($summit_id)
    {
        return $this->processRequest(function () use ($summit_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload(PromoCodesValidationRulesFactory::buildForAdd($this->getJsonData()), true);

            $promo_code = $this->promo_code_service->addPromoCode($summit, $payload, $this->resource_server_context->getCurrentUser());

            return $this->created(SerializerRegistry::getInstance()->getSerializer($promo_code)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $promo_code_id
     * @return mixed
     */
    public function updatePromoCodeBySummit($summit_id, $promo_code_id)
    {
        return $this->processRequest(function () use ($summit_id, $promo_code_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload(PromoCodesValidationRulesFactory::buildForUpdate($this->getJsonData()), true);

            $promo_code = $this->promo_code_service->updatePromoCode($summit, intval($promo_code_id), $payload, $this->resource_server_context->getCurrentUser());

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($promo_code)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
            ));

        });
    }

    /**
     * @param $summit_id
     * @param $promo_code_id
     * @return mixed
     */
    public function deletePromoCodeBySummit($summit_id, $promo_code_id)
    {
        return $this->processRequest(function () use ($summit_id, $promo_code_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->promo_code_service->deletePromoCode($summit, intval($promo_code_id));

            return $this->deleted();
        });
    }

    /**
     * @param $summit_id
     * @param $promo_code_id
     * @return mixed
     */
    public function sendPromoCodeMail($summit_id, $promo_code_id)
    {
        return $this->processRequest(function () use ($summit_id, $promo_code_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $this->promo_code_service->sendPromoCodeMail($summit, intval($promo_code_id));
            return $this->ok();
        });
    }

    /**
     * @param $summit_id
     * @param $promo_code_id
     * @return mixed
     */
    public function getPromoCodeBySummit($summit_id, $promo_code_id)
    {
        return $this->processRequest(function () use ($summit_id, $promo_code_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $promo_code = $summit->getPromoCodeById(intval($promo_code_id));
            if (is_null($promo_code))
                return $this->error404();
            return $this->ok(SerializerRegistry::getInstance()->getSerializer($promo_code)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $promo_code_id
     * @param $badge_feature_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function addBadgeFeatureToPromoCode($summit_id, $promo_code_id, $badge_feature_id)
    {

        return $this->processRequest(function () use ($summit_id, $promo_code_id, $badge_feature_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $promo_code = $this->promo_code_service->addPromoCodeBadgeFeature($summit, intval($promo_code_id), intval($badge_feature_id));
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($promo_code)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
            ));
        });
    }


    /**
     * @param $summit_id
     * @param $promo_code_id
     * @param $badge_feature_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function removeBadgeFeatureFromPromoCode($summit_id, $promo_code_id, $badge_feature_id)
    {

        return $this->processRequest(function () use ($summit_id, $promo_code_id, $badge_feature_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $promo_code = $this->promo_code_service->removePromoCodeBadgeFeature($summit, intval($promo_code_id), intval($badge_feature_id));
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($promo_code)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $promo_code_id
     * @param $ticket_type_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function addTicketTypeToPromoCode($summit_id, $promo_code_id, $ticket_type_id)
    {

        return $this->processRequest(function () use ($summit_id, $promo_code_id, $ticket_type_id) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload(
                [
                    'amount' => 'sometimes|required_without:rate|numeric|min:0',
                    'rate' => 'sometimes|required_without:amount|numeric|min:0',
                ]
            );

            $promo_code = $this->promo_code_service->addPromoCodeTicketTypeRule($summit, intval($promo_code_id), intval($ticket_type_id), $payload);
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($promo_code)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
            ));
        });
    }


    /**
     * @param $summit_id
     * @param $promo_code_id
     * @param $ticket_type_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function removeTicketTypeFromPromoCode($summit_id, $promo_code_id, $ticket_type_id)
    {

        return $this->processRequest(function () use ($summit_id, $promo_code_id, $ticket_type_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $promo_code = $this->promo_code_service->removePromoCodeTicketTypeRule($summit, intval($promo_code_id), intval($ticket_type_id));
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($promo_code)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
            ));
        });
    }

    /**
     * @param LaravelRequest $request
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function ingestPromoCodes(LaravelRequest $request, $summit_id)
    {
        return $this->processRequest(function () use ($summit_id, $request) {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $file = $request->file('file');
            if (is_null($file)) {
                return $this->error412(array('file param not set!'));
            }

            $this->promo_code_service->importPromoCodes($summit, $file, $this->resource_server_context->getCurrentUser());
            return $this->ok();

        });
    }

    /**
     * @param $summit_id
     * @param $promo_code_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getPromoCodeSpeakers($summit_id, $promo_code_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $promo_code = $this->repository->getById($promo_code_id);

        if (!$promo_code instanceof SpeakersSummitRegistrationPromoCode ) {
            return $this->error404();
        }

        return $this->_getAll(
            function () {
                return [
                    'email' => ['@@', '=@', '=='],
                    'full_name' => ['@@', '=@', '=='],
                ];
            },
            function () {
                return [
                    'email' => 'sometimes|string',
                    'full_name' => 'sometimes|string',
                ];
            },
            function () {
                return [
                    'id'
                ];
            },
            function ($filter) {
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Private;
            },
            function () {
                return new Order([
                    OrderElement::buildAscFor("id"),
                ]);
            },
            null,
            function ($page, $per_page, $filter, $order, $applyExtraFilters) use ($promo_code) {
                return $this->speakers_promo_code_repository->getPromoCodeSpeakers
                (
                    $promo_code,
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            }
        );
    }

    /**
     * @param $summit_id
     * @param $discount_code_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getDiscountCodeSpeakers($summit_id, $discount_code_id)
    {
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        $discount_code = $this->repository->getById($discount_code_id);

        if (!$discount_code instanceof SpeakersRegistrationDiscountCode ) {
            return $this->error404();
        }

        return $this->_getAll(
            function () {
                return [
                    'email' => ['@@', '=@', '=='],
                    'first_name' => ['@@', '=@', '=='],
                    'last_name' => ['@@', '=@', '=='],
                ];
            },
            function () {
                return [
                    'email' => 'sometimes|string',
                    'first_name' => 'sometimes|string',
                    'last_name' => 'sometimes|string',
                ];
            },
            function () {
                return [
                    'id',
                    'email',
                ];
            },
            function ($filter) {
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Private;
            },
            function () {
                return new Order([
                    OrderElement::buildAscFor("id"),
                ]);
            },
            null,
            function ($page, $per_page, $filter, $order, $applyExtraFilters) use ($discount_code) {
                return $this->speakers_discount_code_repository->getDiscountCodeSpeakers
                (
                    $discount_code,
                    new PagingInfo($page, $per_page),
                    call_user_func($applyExtraFilters, $filter),
                    $order
                );
            }
        );
    }

    /**
     * @param $summit_id
     * @param $promo_code_id
     * @param $speaker_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function addSpeaker($summit_id, $promo_code_id, $speaker_id)
    {
        return $this->processRequest(function () use ($summit_id, $promo_code_id, $speaker_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $promo_code = $this->repository->getById($promo_code_id);
            if (is_null($promo_code)) return $this->error404();

            $this->promo_code_service->addPromoCodeSpeaker($promo_code, $speaker_id);

            return $this->created();
        });
    }

    /**
     * @param $summit_id
     * @param $promo_code_id
     * @param $speaker_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function removeSpeaker($summit_id, $promo_code_id, $speaker_id)
    {
        return $this->processRequest(function () use ($summit_id, $promo_code_id, $speaker_id) {
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $promo_code = $this->repository->getById($promo_code_id);
            if (is_null($promo_code)) return $this->error404();

            $this->promo_code_service->removePromoCodeSpeaker($promo_code, $speaker_id);

            return $this->deleted();
        });
    }
}