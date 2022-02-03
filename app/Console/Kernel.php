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

use App\Console\Commands\PresentationMaterialsCreateMUXAssetsCommand;
use App\Console\Commands\SummitSyncAllPresentationActions;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\App;
use models\summit\CalendarSync\CalendarSyncInfo;
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
        \App\Console\Commands\ChatTeamMessagesSender::class,
        \App\Console\Commands\SummitListJsonGenerator::class,
        \App\Console\Commands\PromoCodesRedeemProcessor::class,
        \App\Console\Commands\SummitRoomReservationRevocationCommand::class,
        \App\Console\Commands\ExternalScheduleFeedIngestionCommand::class,
        \App\Console\Commands\SummitEventSetAvgRateProcessor::class,
        \App\Console\Commands\RegistrationSummitOrderRevocationCommand::class,
        \App\Console\Commands\RegistrationSummitOrderReminderEmailCommand::class,
        \App\Console\Commands\SummitForwardXDays::class,
        \App\Console\Commands\SummitEmailFlowEventSeederCommand::class,
        \App\Console\Commands\SummitEmailFlowTypeSeederCommand::class,
        \App\Console\Commands\PresentationMaterialsCreateMUXAssetsCommand::class,
        \App\Console\Commands\RecalculateAttendeesStatusCommand::class,
        \App\Console\Commands\EnableMP4SupportAtMUXCommand::class,
        \App\Console\Commands\SummitSyncAllPresentationActions::class,
        \App\Console\Commands\SummitMediaUploadMigratePrivateToPublicStorage::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // regenerate available summits cache

        $env = App::environment();

        // summit json cache
        $schedule->command('summit:json-generator')->everyFiveMinutes()->withoutOverlapping()->onOneServer();

        // synch presentation actions
        $schedule->command('summit:synch-presentation-actions')->everyFiveMinutes()->withoutOverlapping()->onOneServer();

        // list of available summits
        $schedule->command('summit-list:json-generator')->everyFiveMinutes()->withoutOverlapping()->onOneServer();

        // redeem code processor

        //$schedule->command('summit:promo-codes-redeem-processor', [end($summit_ids)])->daily()->withoutOverlapping();

        // bookable rooms

        $schedule->command('summit:room-reservation-revocation')->everyMinute()->withoutOverlapping()->onOneServer();
        // external schedule ingestion task

        $schedule->command("summit:external-schedule-feed-ingestion-process")->everyFifteenMinutes()->withoutOverlapping()->onOneServer();

        // AVG schedule feedback rate
        $schedule->command("summit:feedback-avg-rate-processor")->everyFifteenMinutes()->withoutOverlapping()->onOneServer();
        // registration orders

        $schedule->command('summit:order-reservation-revocation')->everyFiveMinutes()->withoutOverlapping()->onOneServer();

        // reminder emails

        $schedule->command('summit:registration-order-reminder-action-email')->everyThirtyMinutes()->timezone(new \DateTimeZone('UTC'))->withoutOverlapping()->onOneServer();

        if ($env == 'production') {
            // FNTECH production YOCO (13) advance AT 0700 AM ( 12:00 AM PST)
            $schedule->command("summit:forward-x-days", ["FNTECH", 13, 2, '--check-ended'])->dailyAt("07:00")->timezone('UTC')->withoutOverlapping()->onOneServer();
            // FNTECH production Hybrid Alive (30) advance AT 0700 AM ( 12:00 AM PST)
            $schedule->command("summit:forward-x-days", ["FNTECH", 30, 3, '--check-ended'])->dailyAt("07:00")->timezone('UTC')->withoutOverlapping()->onOneServer();
        }

    }
}
