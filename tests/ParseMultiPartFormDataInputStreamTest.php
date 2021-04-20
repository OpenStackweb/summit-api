<?php
/**
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
use Tests\TestCase;

/**
 * Class ParseMultiPartFormDataInputStreamTest
 */
final class ParseMultiPartFormDataInputStreamTest extends TestCase
{
    public function testParse(){
        $input = <<<DATA
      ------WebKitFormBoundaryRlOdocoDcLqHoxXW
Content-Disposition: form-data; name="file"


------WebKitFormBoundaryRlOdocoDcLqHoxXW--
  

DATA;
        $_SERVER['CONTENT_TYPE'] = 'multipart/form-data; boundary=----WebKitFormBoundaryRlOdocoDcLqHoxXW';
        $parser = new \utils\ParseMultiPartFormDataInputStream($input);

        $res = $parser->getInput();


    }

    public function testParseAttributes(){
        $input = <<<DATA
------WebKitFormBoundarySPB0RLYHwOEptxHU
Content-Disposition: form-data; name="file"; filename="clint-eastwood_gettyimages-119202692jpg.jpg"
Content-Type: image/jpeg


------WebKitFormBoundarySPB0RLYHwOEptxHU
Content-Disposition: form-data; name="class_name"

PresentationSlide
------WebKitFormBoundarySPB0RLYHwOEptxHU
Content-Disposition: form-data; name="name"

tets1 update update
------WebKitFormBoundarySPB0RLYHwOEptxHU
Content-Disposition: form-data; name="description"

<p>test1</p>
------WebKitFormBoundarySPB0RLYHwOEptxHU
Content-Disposition: form-data; name="featured"

false
------WebKitFormBoundarySPB0RLYHwOEptxHU
Content-Disposition: form-data; name="display_on_site"

false
------WebKitFormBoundarySPB0RLYHwOEptxHU
Content-Disposition: form-data; name="link"

https://www.google.com
------WebKitFormBoundarySPB0RLYHwOEptxHU--
  

DATA;
        $_SERVER['CONTENT_TYPE'] = 'multipart/form-data; boundary=----WebKitFormBoundarySPB0RLYHwOEptxHU';
        $parser = new \utils\ParseMultiPartFormDataInputStream($input);

        $res = $parser->getInput();


        $this->assertTrue(isset($res['parameters']));
        $this->assertTrue(count($res['parameters']) > 0);

    }

    public function testArray(){
        $input = <<<DATA
------WebKitFormBoundaryt61VjbNKJb4PiKXk
Content-Disposition: form-data; name="name"

ncode
------WebKitFormBoundaryt61VjbNKJb4PiKXk
Content-Disposition: form-data; name="label"

Code
------WebKitFormBoundaryt61VjbNKJb4PiKXk
Content-Disposition: form-data; name="description"

ndndnllll
------WebKitFormBoundaryt61VjbNKJb4PiKXk
Content-Disposition: form-data; name="show_always"

true
------WebKitFormBoundaryt61VjbNKJb4PiKXk
Content-Disposition: form-data; name="file_preview"


------WebKitFormBoundaryt61VjbNKJb4PiKXk
Content-Disposition: form-data; name="summit_id"

6
------WebKitFormBoundaryt61VjbNKJb4PiKXk
Content-Disposition: form-data; name="event_types[]"
56
------WebKitFormBoundaryt61VjbNKJb4PiKXk
Content-Disposition: form-data; name="event_types[]"
59
------WebKitFormBoundaryt61VjbNKJb4PiKXk
Content-Disposition: form-data; name="event_types[]"
58
------WebKitFormBoundaryt61VjbNKJb4PiKXk--

  
DATA;

        $_SERVER['CONTENT_TYPE'] = 'multipart/form-data; boundary=----WebKitFormBoundaryt61VjbNKJb4PiKXk';
        $parser = new \utils\ParseMultiPartFormDataInputStream($input);

        $res = $parser->getInput();

        $this->assertTrue(isset($res['parameters']));
        $parameters = $res['parameters'];
        $this->assertTrue(count($parameters) > 0);
        $this->assertTrue(isset($parameters['event_types']));
        $this->assertTrue(count($parameters['event_types']) == 3);
    }
}