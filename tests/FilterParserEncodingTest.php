<?php namespace Tests;
/**
 * Copyright 2026 OpenStack Foundation
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

use utils\FilterParser;
use utils\FilterParserException;

/**
 * Class FilterParserEncodingTest
 *
 * FilterParser urldecodes each value a second time on purpose (a literal + travels as %2B,
 * which the HTTP client encodes again as %252B). The expressions below are written the way
 * PHP hands them over after its own decode of the query string: title=@%25DA on the wire
 * arrives as title=@%DA, and filter=%DA==x arrives as the raw byte 0xDA followed by ==x.
 * Input that is not valid UTF-8, or that has characters outside the BMP, can never be compared
 * against our utf8mb3/latin1 columns (MySQL 1267), so it must be rejected before any query runs.
 */
final class FilterParserEncodingTest extends TestCase
{
    private const RULES = [
        'title'      => ['=@', '=='],
        'first_name' => ['=@', '=='],
    ];

    private static function parseValue(string $expression): string
    {
        $filter = FilterParser::parse($expression, self::RULES);
        $elements = $filter->getFilter('title');
        return $elements[0]->getValue();
    }

    public function testEncodedPlusIsKept(): void
    {
        $this->assertSame('C++', self::parseValue('title==C%2B%2B'));
    }

    public function testNonAsciiBmpTextIsKept(): void
    {
        $this->assertSame('José', self::parseValue('title==Jos%C3%A9'));
        $this->assertSame('€', self::parseValue('title==%E2%82%AC'));
        $this->assertSame('日本', self::parseValue('title==%E6%97%A5%E6%9C%AC'));
    }

    public function testInvalidUtf8ValueIsRejected(): void
    {
        $this->expectException(FilterParserException::class);
        FilterParser::parse('title=@%DA', self::RULES);
    }

    public function testInvalidUtf8ValueIsRejectedWithEqualOperator(): void
    {
        $this->expectException(FilterParserException::class);
        FilterParser::parse('title==%CA', self::RULES);
    }

    public function testEmojiValueIsRejected(): void
    {
        $this->expectException(FilterParserException::class);
        FilterParser::parse('title=@' . "\u{1F600}", self::RULES);
    }

    public function testEncodedEmojiValueIsRejected(): void
    {
        $this->expectException(FilterParserException::class);
        FilterParser::parse('title=@%F0%9F%98%80', self::RULES);
    }

    public function testInvalidUtf8FieldIsRejectedWithSafeMessage(): void
    {
        try {
            FilterParser::parse("\xDA==x", self::RULES);
            $this->fail('FilterParserException expected');
        } catch (FilterParserException $ex) {
            $this->assertNotFalse(json_encode(['message' => $ex->getMessage()]));
        }
    }

    public function testInvalidUtf8ExpressionWithoutOperatorIsRejectedWithSafeMessage(): void
    {
        try {
            FilterParser::parse("\xDA", self::RULES);
            $this->fail('FilterParserException expected');
        } catch (FilterParserException $ex) {
            $this->assertNotFalse(json_encode(['message' => $ex->getMessage()]));
        }
    }
}
