<?php namespace Tests;
/*
 * Copyright 2023 OpenStack Foundation
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


use App\Http\Utils\CSVExporter;

final class CSVExporterTest extends TestCase
{

    public function testDistinctRecords(){
        $items = [
            ['field1'=> 1],
            ['field1'=> 2, 'field2'=> 3],
        ];

        $res = CSVExporter::getInstance()->export($items);

        $this->assertTrue(!empty($res));
        $this->assertTrue(str_contains($res,'field1,field2'));
    }

    public function testSameRecords(){
        $items = [
            ['field1'=> 2, 'field2'=> 4],
            ['field1'=> 2, 'field2'=> 3],
        ];

        $res = CSVExporter::getInstance()->export($items);

        $this->assertTrue(!empty($res));
        $this->assertTrue(str_contains($res,'field1,field2'));
    }

    public function testSameRecordsDistinctOrder(){
        $items = [
            ['field2'=> 2, 'field1'=> 4],
            ['field1'=> 2, 'field2'=> 3],
        ];

        $res = CSVExporter::getInstance()->export($items);

        $this->assertTrue(!empty($res));
        $this->assertTrue(str_contains($res,'field2,field1'));
    }

    public function testDistcintRecordsDistinctOrder2(){
        $items = [
            ['field2'=> 2, 'field1'=> 4,'field3'=>4],
            ['field1'=> 2, 'field2'=> 3, 'field5'=> 5],
        ];

        $res = CSVExporter::getInstance()->export($items);

        $this->assertTrue(!empty($res));
        $this->assertTrue(str_contains($res,'field2,field1,field3,field5'));
    }

    public function testDistinctRecords2(){
        $items = [
            ['field1'=> 1],
            ['field2'=> 3],
        ];

        $res = CSVExporter::getInstance()->export($items);

        $this->assertTrue(!empty($res));
        $this->assertTrue(str_contains($res,'field1,field2'));
    }
}