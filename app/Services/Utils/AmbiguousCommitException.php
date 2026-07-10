<?php namespace services\utils;
/**
 * Copyright 2026 OpenStack Foundation
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

/**
 * Thrown when the root transaction's COMMIT fails after being sent to the
 * server: the outcome is unknown - the transaction may already be durable
 * with only the acknowledgment lost - so re-executing the callback could
 * duplicate every write and side effect.
 *
 * Callers must treat this as "operation state unknown" and reconcile instead
 * of retrying. Queue jobs especially: without this marker, the propagated
 * driver exception (e.g. ConnectionLost) looks retryable to the queue's own
 * tries mechanism, which would re-run the whole job - catch it and fail the
 * job without retry ($this->fail($e)). The original driver exception is
 * preserved as getPrevious().
 *
 * Extends plain \RuntimeException on purpose: shouldReconnect() never matches
 * it, so it can never re-enter any retry path in this service.
 */
final class AmbiguousCommitException extends \RuntimeException
{
}
