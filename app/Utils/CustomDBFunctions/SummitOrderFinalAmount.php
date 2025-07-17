<?php namespace App\Utils\CustomDBFunctions;
/*
 * Copyright 2024 OpenStack Foundation
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

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

/**
 * Class SummitOrderFinalAmount
 * @package App\Utils\CustomDBFunctions
 */
class SummitOrderFinalAmount extends FunctionNode
{
    public $orderId;
    public function getSql(SqlWalker $sqlWalker): string
    {
        return sprintf(
            'SUMMIT_ORDER_FINAL_AMOUNT(%s)',
            $sqlWalker->walkArithmeticExpression($this->orderId),
        );
    }

    /**
     * @inheritdoc
     */
    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);
        $this->orderId = $parser->ArithmeticExpression();
        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }
}