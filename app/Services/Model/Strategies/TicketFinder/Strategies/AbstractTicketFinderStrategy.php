<?php namespace App\Services\Model\Strategies\TicketFinder\Strategies;
/*
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

use App\Services\Model\Strategies\TicketFinder\ITicketFinderStrategy;
use models\summit\ISummitAttendeeTicketRepository;
use models\summit\Summit;

/**
 * Class AbstractTicketFinderStrategy
 * @package App\Services\Model\Strategies\TicketFinder\Strategies
 */
abstract class AbstractTicketFinderStrategy implements ITicketFinderStrategy {
  /**
   * @var ISummitAttendeeTicketRepository
   */
  protected $repository;

  /**
   * @var Summit
   */
  protected $summit;

  protected $ticket_criteria;

  /**
   * @param ISummitAttendeeTicketRepository $repository
   * @param Summit $summit
   * @param $ticket_criteria
   */
  public function __construct(
    ISummitAttendeeTicketRepository $repository,
    Summit $summit,
    $ticket_criteria,
  ) {
    $this->summit = $summit;
    $this->ticket_criteria = $ticket_criteria;
    $this->repository = $repository;
  }
}
