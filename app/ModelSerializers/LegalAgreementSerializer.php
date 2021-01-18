<?php namespace ModelSerializers;
/**
 * Copyright 2021 OpenStack Foundation
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
use App\Models\Foundation\Main\Repositories\ILegalDocumentRepository;
use Illuminate\Support\Facades\App;
use models\main\LegalAgreement;
/**
 * Class LegalAgreementSerializer
 * @package ModelSerializers
 */
final class LegalAgreementSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'OwnerId' => 'owner_id:json_int',
        'DocumentId' => 'document_id:json_int',
    ];

    public function serialize($expand = null, array $fields = array(), array $relations = array(), array $params = array())
    {
        $legal_agreement = $this->object;
        if (!$legal_agreement instanceof LegalAgreement) return [];
        $values = parent::serialize($expand, $fields, $relations, $params);
        if (!empty($expand)) {
            $exp_expand = explode(',', $expand);
            foreach ($exp_expand as $relation) {
                switch (trim($relation)) {
                    case 'document':
                        {
                          $document = App::make(ILegalDocumentRepository::class)->getById($values['document_id']);
                          unset($values['document_id']);
                          $values['document'] = SerializerRegistry::getInstance()->getSerializer($document)->serialize($expand, [], ['none']);
                        }
                        break;
                }
            }
        }
        return $values;
    }
}