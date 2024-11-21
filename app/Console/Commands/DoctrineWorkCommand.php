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

use App\Worker\DoctrineWorker;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Queue\Console\WorkCommand as IlluminateWorkCommand;
use Illuminate\Support\Facades\Log;

/**
 * Class DoctrineWorkCommand
 * @package App\Console\Commands
 */
class DoctrineWorkCommand extends IlluminateWorkCommand
{
    protected $description = 'Works jobs from the queue in a way that\'s compatible with Doctrine.';

    public function __construct(DoctrineWorker $worker, Cache $cache)
    {
        // Keep the same signature as built-in queue worker, but use our command name.
        $this->signature = str_replace('queue:work', 'doctrine:queue:work', $this->signature);
        Log::debug("DoctrineWorkCommand::__construct");
        parent::__construct($worker, $cache);
    }
}