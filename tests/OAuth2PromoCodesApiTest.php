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
use App\Models\Foundation\Summit\PromoCodes\PromoCodesConstants;
use models\summit\SpeakersRegistrationDiscountCode;
use models\summit\SpeakersSummitRegistrationPromoCode;

/**
 * Class OAuth2PromoCodesApiTest
 */
final class OAuth2PromoCodesApiTest extends ProtectedApiTest
{
    /**
     * @param int $summit_id
     */
    public function testGetPromoCodesDiscount($summit_id=27){
        $params = [

            'id'       => $summit_id,
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

    public function testGetPromoCodesByClassNameSpeakerSummitRegistrationPromoCode(){
        $params = [

            'id'       => 23,
            'page'     => 1,
            'per_page' => 10,
            'filter'   => 'class_name=='.\models\summit\SpeakerSummitRegistrationPromoCode::ClassName,
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

    public function testGetPromoCodesByClassNameSpeakersSummitRegistrationPromoCodeOrSpeakersRegistrationDiscountCode(){
        $params = [
            'id'       => 3607,
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

            'id'       => 25,
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

    public function testGetPromoCodesByClassNameOR($summit_id=27){
        $params = [

            'id'       => $summit_id,
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
            'id'       => 23,
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

    public function testGetPromoCodesFilterByEmailOwner(){
        $params = [
            'id'       => 23,
            'page'     => 1,
            'per_page' => 10,
            'filter'   => [
                'speaker_email=@muroi',
            ],
            'order'    => '+code',
            'expand'   => 'speaker,creator'
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

    public function testGetPromoCodesMetadata(){
        $params = [
            'id'       => 27,
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

    public function testAddGenericDiscountCode( $summit_id = 27){
        $code = str_random(16).'_DISCOUNT_CODE';
        $params = ['rate' => 50.00];
        return $this->testAddPromoCode
        (
            $summit_id,
            $code,
            \models\summit\SummitRegistrationDiscountCode::ClassName,
            $params
        );
    }

    public function testAddMemberDiscountCode( $summit_id = 27){
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
            $summit_id,
            $code,
            \models\summit\MemberSummitRegistrationDiscountCode::ClassName,
            $params
        );
    }

    public function testAddPromoCode(
        $summit_id = 27,
        $code = "PROMOCODE",
        $class_name =\models\summit\SummitRegistrationPromoCode::ClassName,
        array $extra_params = []
    ){
        $params = [
            'id' => $summit_id,
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
        $summit_id = 3609,
        $code = "SPSPROMOCODE",
        $class_name =\models\summit\SpeakersSummitRegistrationPromoCode::ClassName,
        array $extra_params = []
    ){
        $params = [
            'id' => $summit_id,
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
        $summit_id = 3609,
        $code = "SPSPROMOCODE",
        $class_name =\models\summit\SpeakersSummitRegistrationPromoCode::ClassName,
        array $extra_params = []
    ){
        $code       = str_random(16).'_PROMOCODE_TEST';
        $promo_code = $this->testAddSpeakersRegistrationPromoCode($summit_id, $code);

        $params = [
            'id'            => $summit_id,
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

    public function testAddDiscountSpeakerCode($summit_id = 27, $code = ""){
        $params = [
            'id' => $summit_id,
        ];

        if(empty($code))
            $code       = str_random(16).'_PROMOCODE';
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

    public function testAddDiscountSpeakerCodeTicketRule($summit_id = 27){
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

    public function testUpdatePromoCode($summit_id  = 27){

        $code       = str_random(16).'_PROMOCODE_TEST';
        $promo_code = $this->testAddPromoCode($summit_id, $code);
        $params = [
            'id'            => $summit_id,
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

    public function testDeletePromoCode($summit_id  = 23){

        $code       = str_random(16).'_PROMOCODE_TEST';
        $promo_code = $this->testAddPromoCode($summit_id, $code);
        $params = [
            'id'            => $summit_id,
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

    public function testGetPromoCodeById($summit_id  = 23){

        $code       = str_random(16).'_PROMOCODE_TEST';
        $promo_code = $this->testAddPromoCode($summit_id, $code);
        $params = [
            'id'            => $summit_id,
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

    public function testEmailPromoCode($summit_id  = 23){

        $code       = str_random(16).'_PROMOCODE_TEST';
        $promo_code = $this->testAddPromoCode($summit_id, $code);
        $params = [
            'id'            => $summit_id,
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

    public function testEmailPromoCodeSendTwice($summit_id  = 23){

        $code       = str_random(16).'_PROMOCODE_TEST';
        $promo_code = $this->testAddPromoCode($summit_id, $code);
        $params = [
            'id'            => $summit_id,
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

    /**
     * @param int $summit_id
     * @param int $promo_code_id
     * @param int $ticket_type_id
     * @return mixed
     */
    public function testAddDiscountCodeTicketRule($summit_id = 8 , $promo_code_id = 7 , $ticket_type_id = 7){
        $params = [
            'id'            => $summit_id,
            'promo_code_id' => $promo_code_id,
            'ticket_type_id' => $ticket_type_id,
            'expand' => 'ticket_types_rules,ticket_types_rules.discount_code'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
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

    /**
     * @param int $summit_id
     * @param int $promo_code_id
     * @param int $ticket_type_id
     * @return mixed
     */
    public function testDeleteDiscountCodeTicketRule($summit_id = 8 , $promo_code_id = 7 , $ticket_type_id = 7){
        $params = [
            'id'            => $summit_id,
            'promo_code_id' => $promo_code_id,
            'ticket_type_id' => $ticket_type_id
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $data = [

        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitPromoCodesApiController@removeTicketTypeFromPromoCode",
            $params,
            [],
            [],
            [],
            $headers
        //        json_encode($data)
        );
        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $promo_code = json_decode($content);
        $this->assertTrue(!is_null($promo_code));
        return $promo_code;
    }

    public function testAddTagsToPromoCode($summit_id  = 3343, $promo_code_id = 1, $track_id = 39330){

        $params = [
            'id'            => $summit_id,
            'promo_code_id' => $promo_code_id,
            'expand'        => 'creator,tags,allowed_ticket_types,badge_features'
        ];

        $data = [
            'class_name' => \models\summit\SummitRegistrationPromoCode::ClassName,
            'tags'           => ['Artificial Intelligence']
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

    /**
     * @param int $summit_id
     */
    public function testGetPromoCodesByTags($summit_id=3759){
        $params = [
            'id'       => $summit_id,
            'page'     => 1,
            'per_page' => 10,
            //'filter'   => 'tags==tag2',
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
}