<?php namespace App\Http\Utils;
use Illuminate\Support\Facades\Log;

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

/**
 * Class CSVExporter
 * @package App\Http\Utils
 */
final class CSVExporter
{
    /**
     * @var CSVExporter
     */
    private static $instance;

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    /**
     * @return CSVExporter
     */
    public static function getInstance()
    {
        if (!is_object(self::$instance)) {
            self::$instance = new CSVExporter();
        }
        return self::$instance;
    }

    /**
     * @param array $items
     * @param $field_separator
     * @param array $formatters
     * @return string
     */
    public function export(array $items, string $field_separator = ",", array $formatters = []):string{
        Log::debug(sprintf("CSVExporter::export items %s", json_encode($items)));
        $output         = '';
        $header         = [];
        $originalHeader = [];

        // get header ( need to iterate over the entire set bc the rows could have distinct keys and we need all)
        foreach ($items as $row) {
            $currentKeys = array_keys($row);
            $tempHeader = $currentKeys;
            array_walk($tempHeader, array($this, 'cleanData'));
            $header = array_unique(array_merge($header, $tempHeader));
            $originalHeader = array_unique(array_merge($originalHeader, $currentKeys));
        }
        Log::debug(sprintf("CSVExporter::export header %s", json_encode($header)));

        foreach ($items as $row){
            array_walk($row, array($this, 'cleanData'));
            $values = [];
            foreach ($originalHeader as $key){
                $val = $row[$key] ?? '';
                if(isset($formatters[$key]))
                    $val = $formatters[$key]->format($val);
                if(is_array($val)) $val = '';
                $values[] = $val;
            }
            $output .= implode($field_separator, $values) . PHP_EOL;
        }

        $csv_header = implode($field_separator, $header);

        if (empty($csv_header) && empty($output)) {
            return '';
        }

        return $csv_header . PHP_EOL . $output;
    }

    function cleanData(&$str)
    {
        if (is_null($str)) {$str = ''; return;};
        if (is_array($str)) {return;};
        $str = preg_replace("/\t/", "\\t", $str);
        $str = preg_replace("/\r?\n/", "\\n", $str);
        $str = preg_replace("/,/", "-", $str);
        if (strstr($str, '"')) $str = '"' . str_replace('"', '""', $str) . '"';
    }
}