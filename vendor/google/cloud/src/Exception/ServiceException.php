<?php
/**
 * Copyright 2016 Google Inc. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Google\Cloud\Exception;

use Exception;

/**
 * Exception thrown when a request fails.
 */
class ServiceException extends GoogleException
{
    /**
     * @var Exception
     */
    private $serviceException;

    /**
     * Handle previous exceptions differently here.
     *
     * @param  string    $message
     * @param  int       $code
     * @param  Exception $serviceException
     */
    public function __construct($message, $code = null, Exception $serviceException = null)
    {
        $this->serviceException = $serviceException;

        parent::__construct($message, $code);
    }

    /**
     * If $serviceException is set, return true.
     *
     * @return bool
     */
    public function hasServiceException()
    {
        return (bool) $this->serviceException;
    }

    /**
     * Return the service exception object.
     *
     * @return Exception
     */
    public function getServiceException()
    {
        return $this->serviceException;
    }
}
