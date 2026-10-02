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
use Illuminate\Support\Facades\Log;
use LaravelDoctrine\ORM\Facades\Registry;
use models\utils\SilverstripeBaseModel;
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

        // keep only the ids, so entities can be detached between summits
        // without leaving stale (detached) instances in the loop
        $summit_ids = array_map(function ($summit) {
            return $summit->getId();
        }, $this->repository->getAvailables());

        $em = Registry::getManager(SilverstripeBaseModel::EntityManager);
        $em->clear();

        $expand = 'schedule';
        $cache_lifetime = intval(Config::get('cache_api_response.get_summit_response_lifetime', 600));

        foreach ($summit_ids as $summit_id) {
            try {
                $summit = $this->repository->getById($summit_id);
                if (is_null($summit)) continue;

                $this->info(sprintf("processing summit %s (%s)", $summit->getName(), $summit->getId()));
                $start = time();

                $data = SerializerRegistry::getInstance()->getSerializer($summit)->serialize($expand);
                if (is_null($data)) {
                    Log::warning(sprintf("SummitJsonGenerator: null serialization for summit %s, skipping", $summit_id));
                    continue;
                }

                $this->info(sprintf("execution call %s seconds", time() - $start));
                $current_time = time();
                $key_current = sprintf('/api/v1/summits/%s.expand=%s', 'current', urlencode($expand));
                $key_id = sprintf('/api/v1/summits/%s.expand=%s', $summit->getIdentifier(), urlencode($expand));

                // encode and compress once, reuse for both keys
                $payload = gzdeflate(json_encode($data), 9);
                unset($data);

                if ($summit->isActive()) {
                    $this->cache_service->setSingleValue($key_current, $payload, $cache_lifetime);
                    $this->cache_service->setSingleValue($key_current . ".generated", $current_time, $cache_lifetime);
                }

                $this->cache_service->setSingleValue($key_id, $payload, $cache_lifetime);
                $this->cache_service->setSingleValue($key_id . ".generated", $current_time, $cache_lifetime);

                $this->info(sprintf("regenerated cache for summit id %s", $summit->getIdentifier()));
            } catch (\Throwable $ex) {
                Log::error($ex);
                $this->error(sprintf("error processing summit %s: %s", $summit_id, $ex->getMessage()));
            } finally {
                unset($payload, $summit, $ex);
                // after the cache writes, so nothing in this iteration still uses detached entities
                $em->clear();
                gc_collect_cycles();
            }
        }

        $this->info(sprintf("peak memory usage %.2f MB", memory_get_peak_usage(true) / 1048576));
	}

}
