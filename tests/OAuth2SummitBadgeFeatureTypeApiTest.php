<?php
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


final class OAuth2SummitBadgeFeatureTypeApiTest extends ProtectedApiTest
{

    /**
     * @param int $summit_id
     * @return mixed
     */
    public function testAddBadgeFeatureType($summit_id = 27){
        $params = [
            'id' => $summit_id,
        ];

        $name        = str_random(16).'_feature_type';
        $template = <<<HTML
<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<svg
   xmlns:dc="http://purl.org/dc/elements/1.1/"
   xmlns:cc="http://creativecommons.org/ns#"
   xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
   xmlns:svg="http://www.w3.org/2000/svg"
   xmlns="http://www.w3.org/2000/svg"
   version="1.0"
   width="340pt"
   height="362pt"
   viewBox="0 0 340 362"
   id="svg3120">
  <defs
     id="defs3130" />
  <metadata
     id="metadata3122">
<rdf:RDF>
  <cc:Work
     rdf:about="">
    <dc:format>image/svg+xml</dc:format>
    <dc:type
       rdf:resource="http://purl.org/dc/dcmitype/StillImage" />
    <dc:title></dc:title>
  </cc:Work>
</rdf:RDF>
</metadata>
  <g
     transform="matrix(0.1,0,0,-0.1,0,362)"
     id="g3124"
     style="fill:#000000;stroke:none">
    <path
       d="m 3190,3550 c -80,-21 -249,-59 -375,-84 -321,-63 -372,-82 -515,-188 -203,-151 -345,-443 -344,-708 1,-101 16,-173 33,-154 4,5 18,34 30,64 61,147 238,371 389,492 77,62 232,123 232,91 0,-11 -53,-77 -116,-143 -59,-63 -79,-89 -190,-250 -115,-169 -265,-471 -366,-740 -98,-261 -170,-469 -218,-625 -81,-267 -154,-478 -167,-482 -16,-6 -60,137 -152,492 -143,556 -204,747 -350,1095 -100,237 -232,427 -500,718 -147,161 -356,315 -482,357 -82,28 -89,14 -27,-55 292,-329 461,-573 640,-920 151,-295 255,-588 444,-1260 48,-172 154,-610 188,-780 38,-189 80,-374 91,-403 4,-9 19,-21 34,-25 34,-9 198,-9 233,0 52,15 62,56 134,528 48,319 89,510 171,795 139,486 287,842 377,900 13,8 77,24 144,35 260,43 469,158 617,341 99,122 125,212 150,512 13,159 21,204 51,299 19,62 32,118 30,125 -7,18 -23,16 -186,-27 z"
       id="path3126"
       style="fill:#017f00;fill-opacity:1" />
  </g>
</svg>
HTML;

        $data = [
            'name'             => $name,
            'description'      => "this is a description",
            'template_content' => $template,
            'tag_name'         => "vegan",
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitBadgeFeatureTypeApiController@add",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $feature = json_decode($content);
        $this->assertTrue(!is_null($feature));
        $this->assertTrue($feature->name == $name);
        return $feature;
    }

    public function testUpdateBadgeFeatureType($summit_id = 27){

        $feature_old = $this->testAddBadgeFeatureType();
        $params = [
            'id' => $summit_id,
            "feature_id" => $feature_old->id
        ];

        $data = [
            'description'      => "this is a description update",
            'is_default'       => false,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitBadgeFeatureTypeApiController@update",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $feature = json_decode($content);
        $this->assertTrue(!is_null($feature));
        $this->assertTrue($feature->name == $feature_old->name);
        return $feature;
    }


    public function testGetAllBySummit($summit_id=27){
        $params = [
            'id' => $summit_id,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitBadgeFeatureTypeApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $data = json_decode($content);
        $this->assertTrue(!is_null($data));
        return $data;
    }

    /**
     * @param int $summit_id
     */
    public function testDeleteAccessLevel($summit_id=27){
        $feature_old = $this->testAddBadgeFeatureType();
        $params = [
            'id' => $summit_id,
            "feature_id" => $feature_old->id
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitBadgeFeatureTypeApiController@delete",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(204);
    }
}