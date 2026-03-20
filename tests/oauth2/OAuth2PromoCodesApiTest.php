<?php namespace Tests;
/*
 * Copyright 2018 OpenStack Foundation
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

use App\Jobs\Emails\Registration\PromoCodes\SponsorPromoCodeEmail;
use App\Models\Foundation\Summit\PromoCodes\PromoCodesConstants;
use Illuminate\Http\UploadedFile;
use models\summit\SpeakersRegistrationDiscountCode;
use models\summit\SpeakersSummitRegistrationPromoCode;
use models\summit\SummitBadgeFeatureType;
use models\summit\SummitTicketType;

/**
 * Class OAuth2PromoCodesApiTest
 */
final class OAuth2PromoCodesApiTest extends ProtectedApiTestCase
{
    use InsertSummitTestData;

    protected function setUp():void
    {
        parent::setUp();
        self::insertSummitTestData();
        self::$summit_permission_group->addMember(self::$member);
        self::$em->persist(self::$summit);
        self::$em->persist(self::$summit_permission_group);
        self::$em->flush();
    }

    protected function tearDown():void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    public function testGetPromoCodesDiscount(){
        $params = [
            'id'       => self::$summit->getId(),
            'page'     => 1,
            'per_page' => 10,
            //'filter'   => 'code=@DISCOUNT_',
            'order'    => '-code'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitPromoCodesApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $promo_codes = json_decode($content);
        $this->assertTrue(!is_null($promo_codes));
    }

    public function testGetPromoCodesByClassNameSpeakersSummitRegistrationPromoCodeOrSpeakersRegistrationDiscountCode(){
        $params = [
            'id'       => self::$summit->getId(),
            'page'     => 1,
            'per_page' => 10,
            'filter'   => 'class_name=='.SpeakersSummitRegistrationPromoCode::ClassName.'||'.SpeakersRegistrationDiscountCode::ClassName,
            'order'    => '+code'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitPromoCodesApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $promo_codes = json_decode($content);
        $this->assertTrue(!is_null($promo_codes));
    }

    public function testGetPromoCodesByClassNameSpeakerSummitRegistrationPromoCodeCSV(){
        $params = [

            'id'       => self::$summit->getId(),
            //'filter'   => 'class_name=='.\models\summit\SpeakerSummitRegistrationPromoCode::ClassName,
            'order'    => '+code',
            'columns'  => 'code,type,owner_name,owner_email,sponsor_name,redeemed,email_sent',
            'expand'   => 'owner_name,owner_email,sponsor_name',
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitPromoCodesApiController@getAllBySummitCSV",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $this->assertTrue(!is_null($content));
    }

    public function testGetPromoCodesByClassNameOR(){
        $params = [

            'id'       => self::$summit->getId(),
            'page'     => 1,
            'per_page' => 10,
            'filter'   => [
                'class_name=='.\models\summit\SummitRegistrationDiscountCode::ClassName.','. 'class_name=='.\models\summit\MemberSummitRegistrationDiscountCode::ClassName,
            ],
            'order'    => '+code'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitPromoCodesApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $promo_codes = json_decode($content);
        $this->assertTrue(!is_null($promo_codes));
    }

    public function testGetPromoCodesByClassNameORInvalidClassName(){
        $params = [
            'id'       => self::$summit->getId(),
            'page'     => 1,
            'per_page' => 10,
            'filter'   => [
                'class_name=='.\models\summit\SpeakerSummitRegistrationPromoCode::ClassName.','. 'class_name==invalid'
            ],
            'order'    => '+code'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitPromoCodesApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(412);
    }

    protected function getPromoCodesFilterBy($filter){
        $params = [
            'id'       => self::$summit->getId(),
            'page'     => 1,
            'per_page' => 10,
            //'filter'   => $filter,
            'order'    => '+code',
            'expand'   => 'speaker,creator,tags'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitPromoCodesApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $promo_codes = json_decode($content);
        $this->assertTrue(!is_null($promo_codes));
    }

    public function testGetPromoCodesFilterByTag(){
        //$code       = str_random(16).'_PROMOCODE_TEST';
        //$promo_code = $this->testAddSpeakersRegistrationPromoCode($code);
        //$this->getPromoCodesFilterBy(['tag==' . $promo_code->getTags()[0]]);
        $this->getPromoCodesFilterBy(['tag=@Artificial']);
    }

    public function testGetPromoCodesFilterByCreator(){
        $this->getPromoCodesFilterBy(['creator=@Marcet']);
    }

    public function testGetPromoCodesFilterByType(){
        $this->getPromoCodesFilterBy(['type==ACCEPTED']);
    }

    public function testGetPromoCodesFilterByOwnerEmail(){
        $this->getPromoCodesFilterBy(['speaker_email=@jpmaxman@tipit.net']);
    }

    public function testGetPromoCodesByClassNameSpeakerSummitRegistrationPromoCode(){
        $this->getPromoCodesFilterBy('class_name=='.\models\summit\SpeakerSummitRegistrationPromoCode::ClassName);
    }

    public function testGetPromoCodesMetadata(){
        $params = [
            'id' => self::$summit->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitPromoCodesApiController@getMetadata",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $metadata = json_decode($content);
        $this->assertTrue(!is_null($metadata));
    }

    public function testAddGenericDiscountCode(){
        $code = str_random(16).'_DISCOUNT_CODE';
        $params = ['rate' => 50.00];
        return $this->testAddPromoCode
        (
            $code,
            \models\summit\SummitRegistrationDiscountCode::ClassName,
            $params
        );
    }

    public function testAddMemberDiscountCode(){
        $code = str_random(16).'_MEMBER_DISCOUNT_CODE';
        $params = [
            'amount' => 100.00,
            'email' => 'smarcet@gmail.com',
            'first_name' => 'sebastian',
            'last_name' => 'marcet',
            'type' => PromoCodesConstants::MemberSummitRegistrationPromoCodeTypes[0]
         ];
        return $this->testAddPromoCode
        (
            $code,
            \models\summit\MemberSummitRegistrationDiscountCode::ClassName,
            $params
        );
    }

    public function testAddPromoCode(
        $code = "PROMOCODE",
        $class_name =\models\summit\SummitRegistrationPromoCode::ClassName,
        array $extra_params = []
    ){
        $params = [
            'id' => self::$summit->getId(),
        ];

        $data = [
            'code'               => $code,
            'class_name'         => $class_name,
            'quantity_available' => 100,
        ];

        $data = array_merge($data, $extra_params);


        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitPromoCodesApiController@addPromoCodeBySummit",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $promo_code = json_decode($content);
        $this->assertTrue(!is_null($promo_code));
        return $promo_code;
    }

    public function testAddSpeakersRegistrationPromoCode(
        $code = "SPSPROMOCODE",
        $class_name =\models\summit\SpeakersSummitRegistrationPromoCode::ClassName,
        array $extra_params = []
    ){
        $params = [
            'id' => self::$summit->getId(),
        ];

        $data = [
            'code'               => $code,
            'class_name'         => $class_name,
            'quantity_available' => 100,
            'speaker_ids'        => [33145],
            'type'               => PromoCodesConstants::SpeakerSummitRegistrationPromoCodeTypeAccepted
        ];

        $data = array_merge($data, $extra_params);

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitPromoCodesApiController@addPromoCodeBySummit",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $promo_code = json_decode($content);
        $this->assertTrue(!is_null($promo_code));
        return $promo_code;
    }

    public function testUpdateSpeakersRegistrationPromoCode(
        $class_name =\models\summit\SpeakersSummitRegistrationPromoCode::ClassName,
        array $extra_params = []
    ){
        $code       = str_random(16).'_PROMOCODE_TEST';
        $promo_code = $this->testAddSpeakersRegistrationPromoCode($code);

        $params = [
            'id'            => self::$summit->getId(),
            'promo_code_id' => $promo_code->id
        ];

        $data = [
            'code'               => $code.'_UPDATE',
            'class_name'         => $class_name,
            'quantity_available' => 100,
            'speaker_ids'        => [33145],
            'type'               => PromoCodesConstants::SpeakerSummitRegistrationPromoCodeTypeAccepted
        ];

        $data = array_merge($data, $extra_params);

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitPromoCodesApiController@updatePromoCodeBySummit",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $promo_code = json_decode($content);
        $this->assertTrue(!is_null($promo_code));
        return $promo_code;
    }

    public function testAddDiscountSpeakerCode($code = ""){
        $params = [
            'id' => self::$summit->getId(),
        ];

        if(empty($code))
            $code = str_random(16).'_PROMOCODE';
        $data = [
            'code'       => $code,
            'class_name' => \models\summit\SpeakerSummitRegistrationDiscountCode::ClassName,
            'quantity_available' => 100,
            'speaker_id' => 1,
            'type'       => PromoCodesConstants::SpeakerSummitRegistrationPromoCodeTypeAccepted
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitPromoCodesApiController@addPromoCodeBySummit",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $promo_code = json_decode($content);
        $this->assertTrue(!is_null($promo_code));
        return $promo_code;
    }

    public function testAddDiscountSpeakerCodeTicketRule(){
        $promo_code = $this->testAddDiscountSpeakerCode();
        $params = [
            'id' => self::$summit->getId(),
            'promo_code_id' => $promo_code->id,
            'ticket_type_id' => self::$default_ticket_type->getId()
        ];

        $data = [
            'rate' => 50.50
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitPromoCodesApiController@addTicketTypeToPromoCode",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $promo_code = json_decode($content);
        $this->assertTrue(!is_null($promo_code));
        return $promo_code;
    }

    public function testUpdatePromoCode(){

        $code       = str_random(16).'_PROMOCODE_TEST';
        $promo_code = $this->testAddPromoCode($code);
        $params = [
            'id'            => self::$summit->getId(),
            'promo_code_id' => $promo_code->id
        ];

        $data = [
            'code'       => $code.'_UPDATE',
            'class_name' => \models\summit\SummitRegistrationPromoCode::ClassName,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitPromoCodesApiController@updatePromoCodeBySummit",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $promo_code = json_decode($content);
        $this->assertTrue(!is_null($promo_code));
        return $promo_code;
    }

    public function testDeletePromoCode(){

        $code       = str_random(16).'_PROMOCODE_TEST';
        $promo_code = $this->testAddPromoCode($code);
        $params = [
            'id'            => self::$summit->getId(),
            'promo_code_id' => $promo_code->id
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitPromoCodesApiController@deletePromoCodeBySummit",
            $params,
            [],
            [],
            [],
            $headers

        );

        $content = $response->getContent();
        $this->assertResponseStatus(204);
    }

    public function testGetPromoCodeById(){

        $code       = str_random(16).'_PROMOCODE_TEST';
        $promo_code = $this->testAddPromoCode($code);
        $params = [
            'id'            => self::$summit->getId(),
            'promo_code_id' => $promo_code->id
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitPromoCodesApiController@getPromoCodeBySummit",
            $params,
            [],
            [],
            [],
            $headers

        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $promo_code = json_decode($content);
        $this->assertTrue(!is_null($promo_code));
        return $promo_code;
    }

    public function testEmailPromoCode(){

        $code       = str_random(16).'_MEMBER_PROMO_TEST';
        $promo_code = $this->testAddPromoCode($code, \models\summit\MemberSummitRegistrationPromoCode::ClassName, [
            'first_name' => 'Test',
            'last_name'  => 'User',
            'email'      => 'test@test.com',
            'type'       => PromoCodesConstants::MemberSummitRegistrationPromoCodeTypes[0],
        ]);
        $params = [
            'id'            => self::$summit->getId(),
            'promo_code_id' => $promo_code->id
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitPromoCodesApiController@sendPromoCodeMail",
            $params,
            [],
            [],
            [],
            $headers

        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
    }

    public function testEmailPromoCodeSendTwice(){

        $code       = str_random(16).'_MEMBER_PROMO_TEST';
        $promo_code = $this->testAddPromoCode($code, \models\summit\MemberSummitRegistrationPromoCode::ClassName, [
            'first_name' => 'Test',
            'last_name'  => 'User',
            'email'      => 'test@test.com',
            'type'       => PromoCodesConstants::MemberSummitRegistrationPromoCodeTypes[0],
        ]);
        $params = [
            'id'            => self::$summit->getId(),
            'promo_code_id' => $promo_code->id
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitPromoCodesApiController@sendPromoCodeMail",
            $params,
            [],
            [],
            [],
            $headers

        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $response = $this->action(
            "POST",
            "OAuth2SummitPromoCodesApiController@sendPromoCodeMail",
            $params,
            [],
            [],
            [],
            $headers

        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
    }

    public function testAddDiscountCodeTicketRule(){
        $discount_code = $this->testAddGenericDiscountCode();
        $params = [
            'id'             => self::$summit->getId(),
            'promo_code_id'  => $discount_code->id,
            'ticket_type_id' => self::$default_ticket_type->getId(),
            'expand' => 'ticket_types_rules,ticket_types_rules.discount_code'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json"
        ];

        $data = [
            'rate' => 10,
            'amount' => 0,
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitPromoCodesApiController@addTicketTypeToPromoCode",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );
        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $promo_code = json_decode($content);
        $this->assertTrue(!is_null($promo_code));
        return $promo_code;
    }

    public function testDeleteDiscountCodeTicketRule(){
        $promo_code = $this->testAddDiscountCodeTicketRule();
        $params = [
            'id'             => self::$summit->getId(),
            'promo_code_id'  => $promo_code->id,
            'ticket_type_id' => self::$default_ticket_type->getId()
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitPromoCodesApiController@removeTicketTypeFromPromoCode",
            $params,
            [],
            [],
            [],
            $headers
        );
        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $promo_code = json_decode($content);
        $this->assertTrue(!is_null($promo_code));
        return $promo_code;
    }

    public function testAddTagsToPromoCode(){
        $code       = str_random(16).'_TAG_PROMO_TEST';
        $promo_code = $this->testAddPromoCode($code);

        $params = [
            'id'            => self::$summit->getId(),
            'promo_code_id' => $promo_code->id,
            'expand'        => 'creator,tags,allowed_ticket_types,badge_features'
        ];

        $data = [
            'class_name' => \models\summit\SummitRegistrationPromoCode::ClassName,
            'tags'       => ['Artificial Intelligence']
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitPromoCodesApiController@updatePromoCodeBySummit",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $promo_code = json_decode($content);
        $this->assertTrue(!is_null($promo_code));
        return $promo_code;
    }

    // --- Badge Feature Tests ---

    public function testAddBadgeFeatureToPromoCode(){
        $badge_feature = new SummitBadgeFeatureType();
        $badge_feature->setName("TEST_FEATURE_" . str_random(8));
        $badge_feature->setDescription("Test badge feature");
        self::$summit->addFeatureType($badge_feature);
        self::$em->persist(self::$summit);
        self::$em->flush();

        $code       = str_random(16).'_BADGE_PROMO_TEST';
        $promo_code = $this->testAddPromoCode($code);

        $params = [
            'id'               => self::$summit->getId(),
            'promo_code_id'    => $promo_code->id,
            'badge_feature_id' => $badge_feature->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitPromoCodesApiController@addBadgeFeatureToPromoCode",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $result = json_decode($content);
        $this->assertTrue(!is_null($result));
        return [
            'promo_code'    => $promo_code,
            'badge_feature' => $badge_feature,
        ];
    }

    public function testRemoveBadgeFeatureFromPromoCode(){
        $data = $this->testAddBadgeFeatureToPromoCode();

        $params = [
            'id'               => self::$summit->getId(),
            'promo_code_id'    => $data['promo_code']->id,
            'badge_feature_id' => $data['badge_feature']->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitPromoCodesApiController@removeBadgeFeatureFromPromoCode",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $result = json_decode($content);
        $this->assertTrue(!is_null($result));
    }

    // --- CSV Ingest Tests ---

    public function testIngestPromoCodes(){
        $csv_content = "code,class_name,quantity_available\nTEST_CSV_IMPORT_1_" . str_random(8) . ",SUMMIT_PROMO_CODE,10\nTEST_CSV_IMPORT_2_" . str_random(8) . ",SUMMIT_PROMO_CODE,20";
        $tmp_path    = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($tmp_path, $csv_content);

        $file = new UploadedFile($tmp_path, 'promo_codes.csv', 'text/csv', null, true);

        $params = [
            'id' => self::$summit->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitPromoCodesApiController@ingestPromoCodes",
            $params,
            [],
            [],
            ['file' => $file],
            $headers
        );

        $this->assertResponseStatus(200);
        @unlink($tmp_path);
    }

    // --- Sponsor Promo Codes Tests ---

    public function testGetSponsorPromoCodesCSV(){
        $params = [
            'id'    => self::$summit->getId(),
            'order' => '+code',
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitPromoCodesApiController@getSponsorPromoCodesAllBySummitCSV",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $this->assertTrue(!is_null($content));
    }

    public function testIngestSponsorPromoCodes(){
        $sponsor = self::$sponsors[0];
        $csv_content = "code,class_name,quantity_available,rate,sponsor_id,contact_email\nTEST_SP_CSV_1_" . str_random(8) . ",SPONSOR_DISCOUNT_CODE,10,50.00," . $sponsor->getId() . ",test@test.com";
        $tmp_path    = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($tmp_path, $csv_content);

        $file = new UploadedFile($tmp_path, 'sponsor_promo_codes.csv', 'text/csv', null, true);

        $params = [
            'id' => self::$summit->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitPromoCodesApiController@ingestSponsorPromoCodes",
            $params,
            [],
            [],
            ['file' => $file],
            $headers
        );

        $this->assertResponseStatus(200);
        @unlink($tmp_path);
    }

    public function testGetAllSponsorPromoCodesBySummit(){
        $params = [
            'id'     => self::$summit->getId(),
            'expand' => 'sponsor,sponsor.company',
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitPromoCodesApiController@getAllSponsorPromoCodesBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $promo_codes = json_decode($content);
        $this->assertTrue(!is_null($promo_codes));
    }

    // --- PreValidate Promo Code Test ---

    public function testPreValidatePromoCode(){
        $params = [
            'id'             => self::$summit->getId(),
            'promo_code_val' => self::$default_prepaid_discount_code->getCode(),
            'filter' => [
                'ticket_type_id==' . self::$default_ticket_type->getId(),
                'ticket_type_qty==1',
                'ticket_type_subtype==' . SummitTicketType::Subtype_PrePaid,
            ]
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitPromoCodesApiController@preValidatePromoCode",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(200);
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('allows_to_reassign', $content);
    }

    // --- Send Sponsor Promo Codes Test ---

    public function testSendSponsorPromoCodes(){
        $params = [
            'id' => self::$summit->getId(),
            'filter' => [
                'id==' . implode('||', [
                    self::$default_sponsors_promo_codes[0]->getId(),
                    self::$default_sponsors_promo_codes[1]->getId(),
                ]),
                'email_sent==0',
            ]
        ];

        $data = [
            'email_flow_event'     => SponsorPromoCodeEmail::EVENT_SLUG,
            'test_email_recipient' => 'test@nomail.com',
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitPromoCodesApiController@sendSponsorPromoCodes",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $this->assertResponseStatus(200);
    }

    // --- Speaker Management Tests (Promo Codes) ---

    public function testAddSpeakerToPromoCode(){
        $code       = str_random(16).'_SPEAKER_PC_TEST';
        $promo_code = $this->testAddSpeakersRegistrationPromoCode($code, SpeakersSummitRegistrationPromoCode::ClassName, [
            'speaker_ids' => [],
        ]);

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $url = sprintf('/api/v1/summits/%s/speakers-promo-codes/%s/speakers/%s',
            self::$summit->getId(), $promo_code->id, self::$speaker->getId());

        $response = $this->call("POST", $url, [], [], [], $headers);

        $this->assertResponseStatus(201);
        return $promo_code;
    }

    public function testGetPromoCodeSpeakers(){
        $promo_code = $this->testAddSpeakerToPromoCode();

        $params = [
            'id'            => self::$summit->getId(),
            'promo_code_id' => $promo_code->id,
            'expand'        => 'speaker',
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitPromoCodesApiController@getPromoCodeSpeakers",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $speakers = json_decode($content);
        $this->assertTrue(!is_null($speakers));
    }

    public function testRemoveSpeakerFromPromoCode(){
        $promo_code = $this->testAddSpeakerToPromoCode();

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $url = sprintf('/api/v1/summits/%s/speakers-promo-codes/%s/speakers/%s',
            self::$summit->getId(), $promo_code->id, self::$speaker->getId());

        $response = $this->call("DELETE", $url, [], [], [], $headers);

        $this->assertResponseStatus(204);
    }

    // --- Speaker Management Tests (Discount Codes) ---

    public function testAddSpeakerToDiscountCode(){
        $code = str_random(16).'_SPEAKER_DC_TEST';
        $promo_code = $this->testAddSpeakersRegistrationPromoCode($code, SpeakersRegistrationDiscountCode::ClassName, [
            'speaker_ids' => [],
            'amount'      => 10.00,
        ]);

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $url = sprintf('/api/v1/summits/%s/speakers-discount-codes/%s/speakers/%s',
            self::$summit->getId(), $promo_code->id, self::$speaker->getId());

        $response = $this->call("POST", $url, [], [], [], $headers);

        $this->assertResponseStatus(201);
        return $promo_code;
    }

    public function testGetDiscountCodeSpeakers(){
        $promo_code = $this->testAddSpeakerToDiscountCode();

        $params = [
            'id'               => self::$summit->getId(),
            'discount_code_id' => $promo_code->id,
            'expand'           => 'speaker',
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitPromoCodesApiController@getDiscountCodeSpeakers",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $speakers = json_decode($content);
        $this->assertTrue(!is_null($speakers));
    }

    public function testRemoveSpeakerFromDiscountCode(){
        $promo_code = $this->testAddSpeakerToDiscountCode();

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $url = sprintf('/api/v1/summits/%s/speakers-discount-codes/%s/speakers/%s',
            self::$summit->getId(), $promo_code->id, self::$speaker->getId());

        $response = $this->call("DELETE", $url, [], [], [], $headers);

        $this->assertResponseStatus(204);
    }
}