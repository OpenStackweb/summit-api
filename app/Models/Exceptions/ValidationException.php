<?php namespace models\exceptions;
/**
 * Copyright 2015 OpenStack Foundation
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

use Exception;

/**
 * Class ValidationException
 * @package models\exceptions
 */
class ValidationException extends Exception
{
    /**
     * @var array
     */
    private $messages;

    /**
     * ValidationException constructor.
     * @param string $message
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct($message = '', $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        if(!is_array($message)){
            $message = [$message];
        }
        $this->messages = $message;
    }

    /**
     * @param array $messages
     * @return $this
     */
    public function setMessages(array $messages)
    {
        $this->messages = $messages;
        return $this;
    }

    public function getMessages():array
    {
        if(is_null($this->messages))
        {
            $this->messages = [$this->getMessage()];
        }
        return $this->messages;
    }
}