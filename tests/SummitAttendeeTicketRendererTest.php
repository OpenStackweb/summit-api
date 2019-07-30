<?php namespace Tests;
/**
 * Copyright 2019 OpenStack Foundation
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
use models\summit\SummitAttendeeTicket;
use App\Http\Renderers\SummitAttendeeTicketPDFRenderer;
use LaravelDoctrine\ORM\Facades\EntityManager;
use TCPDF_STATIC;
/**
 * Class SummitAttendeeTicketRendererTest
 * @package Tests
 */
class SummitAttendeeTicketRendererTest extends TestCase
{
    public function testPDFRender(){
        $repo   =  EntityManager::getRepository(SummitAttendeeTicket::class);
        $ticket = $repo->getById(19223);

        $render = new SummitAttendeeTicketPDFRenderer($ticket);
        $output = $render->render();

        $this->assertTrue(!empty($output));

        $f = TCPDF_STATIC::fopenLocal("/tmp/ticket.pdf", 'wb');
        fwrite($f, $output, strlen($output));
        fclose($f);
    }
}