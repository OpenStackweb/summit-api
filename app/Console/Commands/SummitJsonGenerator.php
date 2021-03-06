<?php namespace App\Console\Commands;
/**
 * Copyright 2016 OpenStack Foundation
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
use Illuminate\Console\Command;
use libs\utils\ICacheService;
use models\summit\ISummitRepository;
use ModelSerializers\SerializerRegistry;
use services\model\ISummitService;
use Illuminate\Support\Facades\Config;
/**
 * Class SummitJsonGenerator
 * @package App\Console\Commands
 */
final class SummitJsonGenerator extends Command {

	/**
	 * @var ISummitService
	 */
	private $service;

    /**
     * @var ISummitRepository
     */
    private $repository;

    /**
     * @var ICacheService
     */
    private $cache_service;

    /**
     * SummitJsonGenerator constructor.
     * @param ISummitRepository $repository
     * @param ISummitService $service
     * @param ICacheService $cache_service
     */
    public function __construct(
        ISummitRepository $repository,
        ISummitService $service,
        ICacheService $cache_service
    )
    {
        parent::__construct();
        $this->repository    = $repository;
        $this->service       = $service;
        $this->cache_service = $cache_service;
    }

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'summit:json-generator';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'summit:json-generator';


	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Regenerates All Summits Initial Json';

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function handle()
	{

        $summits = $this->repository->getAvailables();

        foreach($summits as $summit) {

            $this->info(sprintf("processing summit %s (%s)",  $summit->getName(), $summit->getId()));
            $start  = time();
            $expand = 'schedule';

            $data = SerializerRegistry::getInstance()->getSerializer($summit)->serialize($expand);
            if (is_null($data)) return;
            $end = time();
            $delta = $end - $start;
            $this->info(sprintf("execution call %s seconds", $delta));
            $current_time = time();
            $key_current = sprintf('/api/v1/summits/%s.expand=%s', 'current', urlencode($expand));
            $key_id = sprintf('/api/v1/summits/%s.expand=%s', $summit->getIdentifier(), urlencode($expand));

            $cache_lifetime = intval(Config::get('cache_api_response.get_summit_response_lifetime', 600));

            if ($summit->isActive()) {
                $this->cache_service->setSingleValue($key_current, gzdeflate(json_encode($data), 9), $cache_lifetime);
                $this->cache_service->setSingleValue($key_current . ".generated", $current_time, $cache_lifetime);
            }

            $this->cache_service->setSingleValue($key_id, gzdeflate(json_encode($data), 9), $cache_lifetime);
            $this->cache_service->setSingleValue($key_id . ".generated", $current_time, $cache_lifetime);

            $this->info(sprintf("regenerated cache for summit id %s", $summit->getIdentifier()));
        }
	}

}
