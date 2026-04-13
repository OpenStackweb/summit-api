<?php namespace Tests\Unit\Services;
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

use App\Services\Model\PreProcessReservationTask;
use Mockery;
use models\exceptions\ValidationException;
use models\summit\Summit;
use models\summit\SummitTicketType;
use PHPUnit\Framework\TestCase;

/**
 * Class PreProcessReservationTaskTest
 *
 * Regression unit tests for the WithPromoCode reservation guard in
 * {@see PreProcessReservationTask}. Uses Mockery on concrete Summit /
 * SummitTicketType so the tests do not require Laravel, DB, or Redis.
 *
 * @package Tests\Unit\Services
 */
class PreProcessReservationTaskTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * WithPromoCode audience + no promo code → ValidationException.
     *
     * This is the regression: previously, PreProcessReservationTask::run()
     * only validated promo codes when one was supplied, letting
     * audience=WithPromoCode tickets be reserved with just a type_id.
     */
    public function testRejectsPromoCodeOnlyTicketTypeWithoutPromoCode(): void
    {
        $ticket_type = Mockery::mock(SummitTicketType::class);
        $ticket_type->shouldReceive('getId')->andReturn(42);
        $ticket_type->shouldReceive('getName')->andReturn('VIP_PROMO_ONLY');
        $ticket_type->shouldReceive('isLive')->andReturn(true);
        $ticket_type->shouldReceive('isPromoCodeOnly')->andReturn(true);

        $summit = Mockery::mock(Summit::class);
        $summit->shouldReceive('getTicketTypeById')->with(42)->andReturn($ticket_type);

        $payload = [
            'tickets' => [
                ['type_id' => 42], // no promo_code
            ],
        ];

        $task = new PreProcessReservationTask($summit, $payload);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Ticket type VIP_PROMO_ONLY requires a promo code.');

        $task->run([]);
    }

    /**
     * Non-WithPromoCode audience + no promo code → allowed (guard does not overreach).
     */
    public function testAllowsNonPromoCodeOnlyTicketTypeWithoutPromoCode(): void
    {
        $ticket_type = Mockery::mock(SummitTicketType::class);
        $ticket_type->shouldReceive('getId')->andReturn(7);
        $ticket_type->shouldReceive('getName')->andReturn('GENERAL_ADMISSION');
        $ticket_type->shouldReceive('isLive')->andReturn(true);
        $ticket_type->shouldReceive('isPromoCodeOnly')->andReturn(false);

        $summit = Mockery::mock(Summit::class);
        $summit->shouldReceive('getTicketTypeById')->with(7)->andReturn($ticket_type);

        $payload = [
            'tickets' => [
                ['type_id' => 7],
            ],
        ];

        $task = new PreProcessReservationTask($summit, $payload);
        $state = $task->run([]);

        $this->assertEquals([7 => 1], $state['reservations']);
        $this->assertEquals([], $state['promo_codes_usage']);
        $this->assertEquals([7], $state['ticket_types_ids']);
    }

    /**
     * Mixed payload: a promo-only ticket (no promo_code) alongside an Audience_All
     * ticket. The per-ticket guard must fire on the promo-only entry even when
     * it is the first item in the payload (no prior aggregation).
     */
    public function testRejectsMixedPayloadWithPromoCodeOnlyFirst(): void
    {
        $promo_only = Mockery::mock(SummitTicketType::class);
        $promo_only->shouldReceive('getId')->andReturn(42);
        $promo_only->shouldReceive('getName')->andReturn('VIP_PROMO_ONLY');
        $promo_only->shouldReceive('isLive')->andReturn(true);
        $promo_only->shouldReceive('isPromoCodeOnly')->andReturn(true);

        $general = Mockery::mock(SummitTicketType::class);
        $general->shouldReceive('getId')->andReturn(7);
        $general->shouldReceive('getName')->andReturn('GENERAL_ADMISSION');
        $general->shouldReceive('isLive')->andReturn(true);
        $general->shouldReceive('isPromoCodeOnly')->andReturn(false);

        $summit = Mockery::mock(Summit::class);
        $summit->shouldReceive('getTicketTypeById')->with(42)->andReturn($promo_only);
        $summit->shouldReceive('getTicketTypeById')->with(7)->andReturn($general);

        $payload = [
            'tickets' => [
                ['type_id' => 42], // promo-only, no promo_code → must throw
                ['type_id' => 7],  // general admission (would be allowed on its own)
            ],
        ];

        $task = new PreProcessReservationTask($summit, $payload);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Ticket type VIP_PROMO_ONLY requires a promo code.');

        $task->run([]);
    }

    /**
     * Mixed payload, reverse order: general-admission ticket aggregated first,
     * then a promo-only ticket without a promo_code. The guard must still fire
     * even though prior iterations have already populated `reservations` and
     * `ticket_types_ids` — the exception short-circuits without partial state
     * being returned.
     */
    public function testRejectsMixedPayloadWithPromoCodeOnlySecond(): void
    {
        $general = Mockery::mock(SummitTicketType::class);
        $general->shouldReceive('getId')->andReturn(7);
        $general->shouldReceive('getName')->andReturn('GENERAL_ADMISSION');
        $general->shouldReceive('isLive')->andReturn(true);
        $general->shouldReceive('isPromoCodeOnly')->andReturn(false);

        $promo_only = Mockery::mock(SummitTicketType::class);
        $promo_only->shouldReceive('getId')->andReturn(42);
        $promo_only->shouldReceive('getName')->andReturn('VIP_PROMO_ONLY');
        $promo_only->shouldReceive('isLive')->andReturn(true);
        $promo_only->shouldReceive('isPromoCodeOnly')->andReturn(true);

        $summit = Mockery::mock(Summit::class);
        $summit->shouldReceive('getTicketTypeById')->with(7)->andReturn($general);
        $summit->shouldReceive('getTicketTypeById')->with(42)->andReturn($promo_only);

        $payload = [
            'tickets' => [
                ['type_id' => 7],  // aggregated successfully
                ['type_id' => 42], // promo-only, no promo_code → must throw
            ],
        ];

        $task = new PreProcessReservationTask($summit, $payload);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Ticket type VIP_PROMO_ONLY requires a promo code.');

        $task->run([]);
    }
}
