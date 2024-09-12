<?php namespace App\Console\Commands;
/*
 * Copyright 2024 OpenStack Foundation
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

use App\Models\Foundation\ExtraQuestions\ExtraQuestionTypeConstants;
use App\Models\Foundation\Summit\Repositories\ISummitOrderExtraQuestionTypeRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use libs\utils\ICacheService;
use models\summit\ISummitAttendeeRepository;
use models\summit\ISummitRepository;
use models\summit\SummitAccessLevelType;
use models\summit\SummitAttendee;
use models\summit\SummitOrderExtraQuestionTypeConstants;
use ModelSerializers\SerializerRegistry;
use utils\Filter;
use utils\FilterElement;
use utils\Order;
use utils\OrderElement;
use utils\PagingInfo;
use const App\Services\Model\Imp\Traits\MaxPageSize;

/**
 * Class PopulateAllowedExtraQuestionsCacheCommand
 * @package App\Console\Commands
 */
final class PopulateAllowedExtraQuestionsCacheCommand extends Command {

    private $summit_repository;

    private $repository;

    private $cache_service;

    private $attendee_repository;

    /**
     * @param ISummitOrderExtraQuestionTypeRepository $repository
     * @param ICacheService $cache_service
     */
    public function __construct
    (
        ISummitRepository $summit_repository,
        ISummitAttendeeRepository $attendee_repository,
        ISummitOrderExtraQuestionTypeRepository $repository,
        ICacheService $cache_service
    )
    {
        parent::__construct();
        $this->repository = $repository;
        $this->attendee_repository = $attendee_repository;
        $this->summit_repository = $summit_repository;
        $this->cache_service = $cache_service;
    }

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'summit:populate-allowed-extra-questions-cache';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'summit:populate-allowed-extra-questions-cache {summit_id}';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerates All Summits Allowed Extra Questions Cache';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $this->info('PopulateAllowedExtraQuestionsCacheCommand handle');
        $summit_id = $this->argument('summit_id');

        if(empty($summit_id))
            $summits = $this->summit_repository->getOnGoing();
        else
            $summits = [$this->summit_repository->getById(intval($summit_id))];

        $expand = '*sub_question_rules,*sub_question,*values';
        $cache_lifetime = 600;

        foreach($summits as $summit) {

            $start  = time();
            $this->info(sprintf("PopulateAllowedExtraQuestionsCacheCommand processing summit %s", $summit->getId()));
            $count = 0;
            $page = 1;
            // attendees filters
            $filter = new Filter();
            $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));
            $filter->addFilterCondition(FilterElement::makeEqual('has_checkin', 'false'));
            $filter->addFilterCondition(FilterElement::makeEqual('status', SummitAttendee::StatusIncomplete));
            $filter->addFilterCondition(FilterElement::makeEqual('has_tickets', 'true'));

            // questions filters
            $filterQuestions = new Filter();
            $filterQuestions->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));
            $filterQuestions->addFilterCondition(FilterElement::makeEqual('class', ExtraQuestionTypeConstants::QuestionClassMain));
            $filterQuestions->addFilterCondition(FilterElement::makeEqual('usage', SummitOrderExtraQuestionTypeConstants::TicketQuestionUsage));

            do{
                $ids = $this->attendee_repository->getAllIdsByPage(new PagingInfo($page, MaxPageSize), $filter);

                if (!count($ids)) {
                    // if we are processing a page, then break it
                    $this->info(sprintf("PopulateAllowedExtraQuestionsCacheCommand no attendees found for summit %s", $summit->getId()));
                    break;
                }

                foreach ($ids as $attendee_id) {
                    try {

                        $attendee = $this->attendee_repository->getById(intval($attendee_id));
                        if (!$attendee instanceof SummitAttendee)
                            return;

                        if(!$attendee->hasAccessLevel(SummitAccessLevelType::IN_PERSON)){
                            $this->info(sprintf("PopulateAllowedExtraQuestionsCacheCommand attendee %s has no access level", $attendee->getId()));
                            continue;
                        }

                        $this->info(sprintf("PopulateAllowedExtraQuestionsCacheCommand processing attendee %s", $attendee->getId()));

                        $res = $this->repository->getAllAllowedByPage
                        (
                            $attendee,
                            new PagingInfo(1, 100),
                            $filterQuestions,
                            new Order([
                                OrderElement::buildAscFor("order"),
                            ])
                        );

                        $this->info(sprintf("PopulateAllowedExtraQuestionsCacheCommand found %s questions for attendee %s", $res->getTotal(), $attendee->getId()));

                        $data = $res->toArray
                        (
                            $expand,
                            [],
                            [],
                            ['attendee' => $attendee],
                            SerializerRegistry::SerializerType_Public
                        );

                        $key = sprintf
                        (
                            '/api/v2/summits/%s/attendees/%s/allowed-extra-questions.expand=%s.order=%s.page=1.per_page=100',
                            $summit->getId(),
                            $attendee->getId(),
                            urlencode($expand),
                            'order'
                        );

                        $this->cache_service->setSingleValue($key, gzdeflate(json_encode($data), 9), $cache_lifetime);
                        $this->cache_service->setSingleValue($key . ".generated", time(), $cache_lifetime);
                        ++$count;
                        $this->info(sprintf("PopulateAllowedExtraQuestionsCacheCommand cache key %s generated", $key));

                    } catch (\Exception $ex) {
                        Log::error($ex);
                    }
                }

                ++$page;
            }while(1);
            $end = time();
            $delta = $end - $start;
            $this->info
            (
                sprintf
                (
                    "PopulateAllowedExtraQuestionsCacheCommand summit %s execution call %s seconds processed records %s",
                    $summit->getId(),
                    $delta,
                    $count
                )
            );
        }

        $this->info('PopulateAllowedExtraQuestionsCacheCommand done');
    }
}