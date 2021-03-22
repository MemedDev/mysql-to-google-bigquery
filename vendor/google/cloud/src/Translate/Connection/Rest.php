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

namespace Google\Cloud\Translate\Connection;

use Google\Cloud\RequestBuilder;
use Google\Cloud\RequestWrapper;
use Google\Cloud\RestTrait;
use Google\Cloud\UriTrait;

/**
 * Implementation of the
 * [Google Translate REST API](https://cloud.google.com/translate/docs/how-to).
 */
class Rest implements ConnectionInterface
{
    use RestTrait;
    use UriTrait;

    const BASE_URI = 'https://www.googleapis.com/language/translate/';

    /**
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->setRequestWrapper(new RequestWrapper($config));
        $this->setRequestBuilder(new RequestBuilder(
            __DIR__ . '/ServiceDefinition/translate-v2.json',
            self::BASE_URI
        ));
    }

    /**
     * @param array $args
     * @return array
     */
    public function listDetections(array $args = [])
    {
        return $this->send('detections', 'list', $args);
    }

    /**
     * @param array $args
     * @return array
     */
    public function listLanguages(array $args = [])
    {
        return $this->send('languages', 'list', $args);
    }

    /**
     * @param array $args
     * @return array
     */
    public function listTranslations(array $args = [])
    {
        return $this->send('translations', 'list', $args);
    }
}
