<?php namespace App\Jobs;
/*
 * Copyright 2025 OpenStack Foundation
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
use App\Events\SponsorServices\SponsorDomainEvents;
use App\Models\Foundation\Summit\Repositories\ISponsorRepository;
use App\Services\Model\Imp\Factories\RabbitPublisherFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use models\main\Company;

class CompanyEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;

    /**
     * @var string
     */
    private $op;

    /**
     * @var int
     */
    private $company_id;

    /**
     * @var string|null
     */
    private $company_name;

    /**
     * @param Company $company
     * @param string $op
     */
    public function __construct(Company $company, string $op){
        $this->op = $op;
        $this->company_id = $company->getId();
        $this->company_name = $company->getName();
        Log::debug(sprintf("CompanyEventJob::construct op %s company_id %s", $op, $this->company_id));
    }

    /**
     * @param ISponsorRepository $sponsor_repository
     * @return void
     */
    public function handle(ISponsorRepository $sponsor_repository){
        Log::debug(sprintf("CompanyEventJob::handle op %s company_id %s", $this->op, $this->company_id));
        $excerpt = $sponsor_repository->getSponsorsExcerptByCompanyID($this->company_id);
        $domain_event_publisher_service = RabbitPublisherFactory::make('domain_events_message_broker');
        Log::debug(sprintf("CompanyEventJob::handle excerpt %s", json_encode($excerpt)));
        foreach($excerpt as $entry) {
            $payload = [
                'id' => intval($entry['sponsor_id']),
                'company_id' => $this->company_id,
                'company_name' => $this->company_name,
            ];
            $routing_key = $this->op == 'UPDATE' ? SponsorDomainEvents::SponsorUpdated : SponsorDomainEvents::SponsorDeleted;
            $domain_event_publisher_service->publish($payload, $routing_key);
        }
    }


    public function failed(\Throwable $e): void
    {
        Log::error("CompanyEventJob::failed {$e->getMessage()}");
    }

}