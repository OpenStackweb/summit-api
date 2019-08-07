<?php namespace App\Console;
/**
 * Copyright 2017 OpenStack Foundation
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
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use models\summit\CalendarSync\CalendarSyncInfo;
use models\summit\ISummitRepository;
use Illuminate\Support\Facades\App;
/**
 * Class Kernel
 * @package App\Console
 */
class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\SummitJsonGenerator::class,
        \App\Console\Commands\MemberActionsCalendarSyncProcessingCommand::class,
        \App\Console\Commands\AdminActionsCalendarSyncProcessingCommand::class,
        \App\Console\Commands\ChatTeamMessagesSender::class,
        \App\Console\Commands\SummitListJsonGenerator::class,
        \App\Console\Commands\PromoCodesRedeemProcessor::class,
        \App\Console\Commands\SummitRoomReservationRevocationCommand::class,
        \App\Console\Commands\ExternalScheduleFeedIngestionCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //Current
        $schedule->command('summit:json-generator')->everyFiveMinutes()->withoutOverlapping();

        $summit_repository = App::make(ISummitRepository::class);

        foreach ($summit_repository->getAvailables() as $summit)
            $schedule->command('summit:json-generator',[$summit->getId()])->everyFiveMinutes()->withoutOverlapping();

        // list of available summits
        $schedule->command('summit-list:json-generator')->everyFiveMinutes()->withoutOverlapping();

        // Calendar Sync Jobs

        // Admin Actions
        $schedule->command('summit:admin-schedule-action-process')->withoutOverlapping();

        // Member Actions

        // Google Calendar
        $schedule->command('summit:member-schedule-action-process', [CalendarSyncInfo::ProviderGoogle, 1000])->withoutOverlapping();
        // Outlook
        $schedule->command('summit:member-schedule-action-process', [CalendarSyncInfo::ProviderOutlook, 1000])->withoutOverlapping();
        // iCloud
        $schedule->command('summit:member-schedule-action-process', [CalendarSyncInfo::ProvideriCloud, 1000])->withoutOverlapping();

        // redeem code processor

        //$schedule->command('summit:promo-codes-redeem-processor', [end($summit_ids)])->daily()->withoutOverlapping();

        // bookable rooms

        $schedule->command('summit:room-reservation-revocation')->everyFiveMinutes()->withoutOverlapping();
        // external schedule ingestion task

        $schedule->command("summit:external-schedule-feed-ingestion-process")->everyFifteenMinutes()->withoutOverlapping();
    }
}
