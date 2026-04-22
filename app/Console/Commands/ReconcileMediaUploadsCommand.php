<?php namespace App\Console\Commands;
/**
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
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Exception;
use services\model\ISummitService;

/**
 * Class ReconcileMediaUploadsCommand
 * @package App\Console\Commands
 */
class ReconcileMediaUploadsCommand extends Command
{
    /**
     * @var ISummitService
     */
    private $summit_service;

    /**
     * @param ISummitService $summit_service
     */
    public function __construct(ISummitService $summit_service)
    {
        parent::__construct();
        $this->summit_service = $summit_service;
    }

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'summit:reconcile-media-uploads';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'summit:reconcile-media-uploads {summit_id} {media_upload_type_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reconcile media uploads missing from private storage (Dropbox) by re-uploading from public storage';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        try {

            $summit_id = $this->argument('summit_id');
            if (empty($summit_id))
                throw new \InvalidArgumentException("summit_id is required");

            $media_upload_type_id = $this->argument('media_upload_type_id');

            $this->info(sprintf(
                "ReconcileMediaUploadsCommand::handle processing summit %s media upload type %s",
                $summit_id,
                $media_upload_type_id ?? 'all'
            ));

            $start = time();

            $result = $this->summit_service->reconcileMediaUploadsToPrivateStorage(
                intval($summit_id),
                !empty($media_upload_type_id) ? intval($media_upload_type_id) : null
            );

            $end = time();
            $delta = $end - $start;

            $this->info(sprintf(
                "ReconcileMediaUploadsCommand::handle completed in %s seconds - checked: %s, reconciled: %s, missing: %s, errors: %s",
                $delta,
                $result['checked'],
                $result['reconciled'],
                $result['missing'],
                $result['errors']
            ));

            return self::SUCCESS;

        } catch (Exception $ex) {
            Log::warning($ex);
            $this->error($ex->getMessage());
            return self::FAILURE;
        }
    }
}
