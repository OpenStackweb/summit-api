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

use App\Models\Foundation\Main\IGroup;
use App\Models\Foundation\Summit\PromoCodes\PromoCodesConstants;
use models\summit\SpeakersRegistrationDiscountCode;
use models\summit\SpeakersSummitRegistrationPromoCode;

/**
 * Class OAuth2PromoCodesApiTest
 */
final class OAuth2PromoCodesApiTest extends ProtectedApiTest
{
    use InsertSummitTestData;

    use InsertMemberTestData;

    static $summit_id = 3769;

    protected function setUp():void
    {
        parent::setUp();
        self::insertSummitTestData();
        self::insertMemberTestData(IGroup::TrackChairs);
        self::$summit_permission_group->addMember(self::$member);
        self::$em->persist(self::$summit);
        self::$em->persist(self::$summit_permission_group);
        self::$em->flush();

        self::$summit_id = self::$summit->getId();
    }

    protected function tearDown():void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    public function testGetPromoCodesDiscount(){
        $params = [
            'id'       => self::$summit_id,
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
            'id'       => self::$summit_id,
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

            'id'       => self::$summit_id,
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

            'id'       => self::$summit_id,
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
            'id'       => self::$summit_id,
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
            'id'       => self::$summit_id,
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
            'id' => self::$summit_id,
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
            'id' => self::$summit_id,
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
            'id' => self::$summit_id,
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
            'id'            => self::$summit_id,
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
            'id' => self::$summit_id,
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
        $summit_id = self::$summit_id;
        $promo_code = $this->testAddDiscountSpeakerCode($summit_id);
        $params = [
            'id' => $summit_id,
            'promo_code_id' => $promo_code->id,
            'ticket_type_id' => 105
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
            'id'            => self::$summit_id,
            'promo_code_id' => $promo_code->id
        ];

        $data = [
            'code'       => $code.'_UPDATE',
            'class_name' => \models\summit\MemberSummitRegistrationPromoCode::ClassName,
            'first_name' => 'Sebastian update',
            'last_name'  => 'Marcet update',
            'email'      => 'test@test.com',
            'type'       => PromoCodesConstants::MemberSummitRegistrationPromoCodeTypes[2]
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
            'id'            => self::$summit_id,
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
            'id'            => self::$summit_id,
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

        $code       = str_random(16).'_PROMOCODE_TEST';
        $promo_code = $this->testAddPromoCode($code);
        $params = [
            'id'            => self::$summit_id,
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

        $code       = str_random(16).'_PROMOCODE_TEST';
        $promo_code = $this->testAddPromoCode($code);
        $params = [
            'id'            => self::$summit_id,
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
        $this->assertResponseStatus(412);
    }

    public function testAddDiscountCodeTicketRule($promo_code_id = 7 , $ticket_type_id = 7){
        $params = [
            'id'             => self::$summit_id,
            'promo_code_id'  => $promo_code_id,
            'ticket_type_id' => $ticket_type_id,
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

    public function testDeleteDiscountCodeTicketRule($promo_code_id = 7 , $ticket_type_id = 7){
        $params = [
            'id'             => self::$summit_id,
            'promo_code_id'  => $promo_code_id,
            'ticket_type_id' => $ticket_type_id
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

    public function testAddTagsToPromoCode($promo_code_id = 1){

        $params = [
            'id'            => self::$summit_id,
            'promo_code_id' => $promo_code_id,
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
}