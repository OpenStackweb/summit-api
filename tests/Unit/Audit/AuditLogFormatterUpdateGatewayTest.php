<?php namespace Tests\Unit\Audit;
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

use Tests\TestCase;

/**
 * buildChangeDetails() is private on AbstractAuditLogFormatter, so a formatter that
 * calls it cannot skip the "no meaningful changes" null-check. That alone does not
 * stop a formatter from never calling buildChangeDetails() at all for its
 * EVENT_ENTITY_UPDATE case (e.g. an unconditional return sprintf(...)) - this test
 * closes that gap by scanning ConcreteFormatters/ for exactly that mistake.
 */
class AuditLogFormatterUpdateGatewayTest extends TestCase
{
    private const KNOWN_EXCEPTIONS = [
        // Legacy formatter for the separate AuditLogStrategy DB-audit path - has its
        // own independent change-comparison logic, never calls buildChangeDetails().
        'EntityUpdateAuditLogFormatter.php',
        // Abstract template itself - dispatches EVENT_ENTITY_UPDATE to an abstract
        // formatUpdate() method, never calls buildChangeDetails() directly.
        'BasePresentationAuditLogFormatter.php',
    ];

    public function testEveryUpdateCapableFormatterRoutesThroughTheGateway(): void
    {
        $offenders = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(app_path('Audit/ConcreteFormatters'))
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php' || in_array($file->getFilename(), self::KNOWN_EXCEPTIONS, true)) {
                continue;
            }

            $source = file_get_contents($file->getPathname());
            if (str_contains($source, 'EVENT_ENTITY_UPDATE') && !str_contains($source, 'formatUpdateMessage(')) {
                $offenders[] = $file->getPathname();
            }
        }

        $this->assertEmpty(
            $offenders,
            "Formatters handling EVENT_ENTITY_UPDATE must call \$this->formatUpdateMessage() - " .
            "buildChangeDetails() is private specifically to prevent bypassing it. Offending files:\n" .
            implode("\n", $offenders)
        );
    }
}
