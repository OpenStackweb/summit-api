<?php namespace App\Jobs\Utils;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Centralized helpers to dispatch queued jobs with robust fallbacks.
 *
 * Usage:
 *   JobDispatcher::withDbFallback(new UpdateAttendeeInfo($id), ['member_id' => $id]);
 *   JobDispatcher::withSyncFallback(new UpdateAttendeeInfo($id), ['member_id' => $id]);
 */
final class JobDispatcher
{
    private function __construct() {} // static-only

    /**
     * Dispatch to primary (e.g., redis). If it fails, fail over to "database".
     * Optionally run sync if the DB enqueue also fails.
     */
    public static function withDbFallback(
        ShouldQueue $job,
        array $logContext = [],
        ?string $primaryConnection = 'redis',
        ?string $primaryQueue = 'default',
        string $fallbackConnection = 'database',
        ?string $fallbackQueue = 'default',
        bool $ensureAfterCommit = true,
        bool $syncOnDoubleFailure = true
    ) {
        self::applyQueueRouting($job, $primaryConnection, $primaryQueue, $ensureAfterCommit);

        try {
            Log::debug(sprintf("JobDispatcher::withDbFallback trying to enqueing job %s", get_class($job)), $logContext);
            return Bus::dispatch($job);
        } catch (Throwable $e) {
            Log::warning('JobDispatcher::withDbFallback Primary enqueue failed, failing over to database queue', array_merge($logContext, [
                'exception' => get_class($e),
                'err'       => $e->getMessage(),
                'primary_connection' => $primaryConnection,
                'primary_queue'      => $primaryQueue,
            ]));

            self::applyQueueRouting($job, $fallbackConnection, $fallbackQueue, $ensureAfterCommit);

            try {
                return Bus::dispatch($job);
            } catch (Throwable $e2) {
                Log::error('JobDispatcher::withDbFallback callback (database) enqueue failed', array_merge($logContext, [
                    'exception' => get_class($e2),
                    'err'       => $e2->getMessage(),
                    'fallback_connection' => $fallbackConnection,
                    'fallback_queue'      => $fallbackQueue,
                ]));

                if ($syncOnDoubleFailure) {
                    return Bus::dispatchSync($job);
                }
                throw $e2;
            }
        }
    }

    /**
     * Dispatch to primary (e.g., redis). If it fails, run the job synchronously.
     */
    public static function withSyncFallback(
        ShouldQueue $job,
        array $logContext = [],
        ?string $primaryConnection = 'redis',
        ?string $primaryQueue = 'default',
        bool $ensureAfterCommit = true
    ) {
        self::applyQueueRouting($job, $primaryConnection, $primaryQueue, $ensureAfterCommit);

        try {
            Log::debug(sprintf("JobDispatcher::withDbFallback trying to enqueing job %s", get_class($job)), $logContext);
            return Bus::dispatch($job);
        } catch (Throwable $e) {
            Log::warning('JobDispatcher::withDbFallback Primary enqueue failed, running job synchronously', array_merge($logContext, [
                'exception' => get_class($e),
                'err'       => $e->getMessage(),
                'primary_connection' => $primaryConnection,
                'primary_queue'      => $primaryQueue,
            ]));

            return Bus::dispatchSync($job);
        }
    }

    /**
     * Apply connection/queue/afterCommit to the job if supported.
     */
    private static function applyQueueRouting(
        ShouldQueue $job,
        ?string $connection,
        ?string $queue,
        bool $ensureAfterCommit
    ): void {
        if ($connection && method_exists($job, 'onConnection')) {
            $job->onConnection($connection);
        }
        if ($queue && method_exists($job, 'onQueue')) {
            $job->onQueue($queue);
        }
        if ($ensureAfterCommit && property_exists($job, 'afterCommit')) {
            $job->afterCommit = true;
        }
    }
}
