<?php namespace Tests;
/*
 * Copyright 2022 OpenStack Foundation
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

use App\Services\Utils\CSVReader;


/**
 * Class CSVReaderTest
 * @package Tests
 */
final class CSVReaderTest extends TestCase
{
    public function testOneColCSV()
    {
        $data = <<<CSV
email
sadan@sna.com
sansan@san.com
sansan2@san.com

CSV;

        $reader = CSVReader::buildFrom($data);

        $this->assertTrue($reader->hasColumn("email"));

        foreach ($reader as $row) {
            $this->assertTrue(!empty($row['email']));
        }
    }

    public function testRowHasMoreColsThanHeaderCSV()
    {
        $data = <<<CSV
code,class_name,quantity_available,sponsor_id,contact_email
TEST_SANTI_SPONS_DIS,SPONSOR_DISCOUNT_CODE,10,,,
TEST_SANTI_SPONS_PROMO,SPONSOR_PROMO_CODE,10,252,,
SANTI_SPONS_PROMO1,SPONSOR_PROMO_CODE,10,,santipalenque@gmail.com
SANTI_SPONS_PROMO2,SPONSOR_PROMO_CODE,10
CSV;

        $reader = CSVReader::buildFrom($data);
        $this->assertTrue($reader->hasColumn("contact_email"));
        $this->assertTrue($reader->count() == 4);
    }
    public function test3ColCSV()
    {
        $data = <<<CSV
email,name,gender
sadan@sna.com,jorge,M
sansan@san.com,susan,F
sansan2@san.com,arthur,M

CSV;

        $reader = CSVReader::buildFrom($data);

        $this->assertTrue($reader->hasColumn("email"));
        $this->assertTrue($reader->hasColumn("name"));
        $this->assertTrue($reader->hasColumn("gender"));

    }

}