<?php namespace App\Services\Utils;
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

use Illuminate\Support\Facades\Log;
use Iterator;
use models\exceptions\ValidationException;

/**
 * Class CSVReader
 * @package App\Services\Utils
 */
final class CSVReader implements Iterator {

    private $position = 0;
    /**
     * @var array
     */
    private $lines = [];
    /**
     * @var array
     */
    private $header = [];

    /**
     * CSVReader constructor.
     * @param array $header
     * @param array $lines
     */
    private function __construct(array $header, array $lines)
    {
        $this->header = $header;
        $this->lines  = $lines;
        Log::debug(sprintf("CSVReader::__construct header %s lines %s", json_encode($header), json_encode($lines)));
    }

    /**
     * @param string $content
     * @return array
     */
    public static function buildFrom(string $content):CSVReader
    {
        try {
            $data = str_getcsv($content, "\n");
            $header = [];
            $lines = [];
            foreach ($data as $idx => $row) {
                $row = str_getcsv($row, ",");
                if ($idx === 0) {
                    foreach ($row as $idx => $val) {
                        // remove BOM
                        $val = str_replace("\xEF\xBB\xBF", '', $val);
                        // check the encoding of the header values
                        if (mb_detect_encoding($val) == 'UTF-8')
                            $val = iconv('utf-8', 'ascii//TRANSLIT', $val);
                        Log::debug(sprintf("CSVReader::buildFrom adding %s to header", $val));
                        $header[] = $val;
                    }
                    continue;
                }
                $line = [];
                for ($i = 0; $i < count($header); $i++) {
                    $line[$header[$i]] = $row[$i] ?? '';
                }
                $lines[] = $line;

            } //parse the items in rows
            return new CSVReader($header, $lines);
        }
        catch(\Exception $ex){
            Log::error($ex);
            throw new ValidationException("CSV is not valid.");
        }
    }

    /**
     * @param string $colName
     * @return bool
     */
    public function hasColumn(string $colName):bool {
        if(mb_detect_encoding($colName) == 'UTF-8')
            $colName = iconv('utf-8', 'ascii//TRANSLIT', $colName);
        return in_array(trim($colName), $this->header);
    }

    /**
     * Return the current element
     * @link https://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     * @since 5.0.0
     */
    public function current()
    {
        return $this->lines[$this->position];
    }

    /**
     * Move forward to next element
     * @link https://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function next()
    {
        ++$this->position;
    }

    /**
     * Return the key of the current element
     * @link https://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     * @since 5.0.0
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * Checks if current position is valid
     * @link https://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     * @since 5.0.0
     */
    public function valid()
    {
        return isset($this->lines[$this->position]);
    }

    /**
     * Rewind the Iterator to the first element
     * @link https://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function rewind()
    {
        $this->position = 0;
    }

    public function count():int{
        return count($this->lines);
    }
}