<?php namespace App\Jobs\Utils;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Dispatch a queued job, and if enqueue fails (e.g., Redis down),
 * run it synchronously as a safety net.
 */
trait DispatchWithSyncFallback
{
    /**
     * @param ShouldQueue $job                 Job instance (use Queueable in the job for onConnection/onQueue).
     * @param array       $logContext          Extra context for logs (e.g., ['member_id' => 123]).
     * @param string|null $connection          Preferred queue connection (e.g., 'redis').
     * @param string|null $queue               Preferred queue name (e.g., 'default').
     * @param bool        $ensureAfterCommit   If true and the job has public $afterCommit, set it to true.
     *
     * @return mixed  Job ID (async) or result (sync)
     */
    protected function dispatchWithFallback(
        ShouldQueue $job,
        array $logContext = [],
        ?string $connection = null,
        ?string $queue = null,
        bool $ensureAfterCommit = true
    ) {
        if ($connection && method_exists($job, 'onConnection')) {
            $job->onConnection($connection);
        }
        if ($queue && method_exists($job, 'onQueue')) {
            $job->onQueue($queue);
        }

        if ($ensureAfterCommit && property_exists($job, 'afterCommit')) {
            $job->afterCommit = true;
        }

        try {
            Log::debug(sprintf("DispatchWithSyncFallback::dispatchWithFallback trying to enqueue job %s", get_class($job)));
            return Bus::dispatch($job);
        } catch (Throwable $e) {
            Log::warning('DispatchWithSyncFallback::dispatchWithFallback Queue push failed, running job synchronously', array_merge($logContext, [
                'job' => get_class($job),
                'exception' => get_class($e),
                'err'       => $e->getMessage(),
            ]));

            // Fallback
            return Bus::dispatchSync($job);
        }
    }
}
