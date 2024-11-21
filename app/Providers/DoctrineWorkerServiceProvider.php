<?php namespace App\Providers;
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
use App\Console\Commands\DoctrineWorkCommand;
use App\Worker\DoctrineWorker;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Doctrine\ORM\EntityManagerInterface;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Foundation\Application;

/**
 * Class DoctrineWorkerServiceProvider
 * @package App\Providers
 */
class DoctrineWorkerServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->registerWorker();
        $this->registerWorkCommand();
    }

    /**
     * @return string[]
     */
    public function provides(): array
    {
        return [
            DoctrineWorkCommand::class,
            DoctrineWorker::class,
        ];
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands(
                [
                    DoctrineWorkCommand::class,
                ]
            );
        }
    }

    private function registerWorker(): void
    {
        $this->app->singleton(
            DoctrineWorker::class,
            function (Application $app): DoctrineWorker {
                $isDownForMaintenance = function (): bool {
                    return $this->app->isDownForMaintenance();
                };

                return new DoctrineWorker(
                    $app['queue'],
                    $app['events'],
                    $app[EntityManagerInterface::class],
                    $app[ExceptionHandler::class],
                    $isDownForMaintenance
                );
            }
        );
    }

    private function registerWorkCommand(): void
    {
        $this->app->singleton(
            DoctrineWorkCommand::class,
            function($app){
                return new DoctrineWorkCommand($app[DoctrineWorker::class], $app['cache.store']);
            }
        );
    }
}
