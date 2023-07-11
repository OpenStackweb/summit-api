<?php namespace App\ModelSerializers\Summit;
/**
 * Copyright 2020 OpenStack Foundation
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

use App\Jobs\Emails\EmailTemplatesSchemaSerializerRegistry;
use App\Models\Foundation\Summit\EmailFlows\SummitEmailEventFlow;
use ModelSerializers\SilverStripeSerializer;

/**
 * Class SummitEmailEventFlowSerializer
 * @package App\ModelSerializers\Summit
 */
class SummitEmailEventFlowSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = array
    (
        'SummitId' => 'summit_id:json_int',
        'EmailTemplateIdentifier' => 'email_template_identifier:json_string',
        'FlowName' => 'flow_name:json_string',
        'EventTypeName' => 'event_type_name:json_string',
        'EmailRecipient' => 'recipient:json_string'
    );

    /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = array(), array $relations = array(), array $params = array())
    {
        $event_flow = $this->object;
        if (!$event_flow instanceof SummitEmailEventFlow) return [];

        $values = parent::serialize($expand, $fields, $relations, $params);
        $values['template_schema'] = EmailTemplatesSchemaSerializerRegistry::getInstance()->serialize($event_flow->getEventType()->getSlug());
        return $values;
    }
}