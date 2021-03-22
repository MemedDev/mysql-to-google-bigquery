<?php
/**
 * Copyright 2016 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Google\Cloud\Tests\Iam;

use Google\Cloud\Iam\PolicyBuilder;

/**
 * @group iam
 */
class PolicyBuilderTest extends \PHPUnit_Framework_TestCase
{
    public function testBuilder()
    {
        $role = 'test';
        $members = [
            'user:test@test.com',
            'serviceAccount:serviceAccount@test.com',
            'group:group@test.com',
            'domain:test.com',
            'allUsers',
            'allAuthenticatedUsers'
        ];

        $etag = 'foo';

        $builder = new PolicyBuilder;
        $builder->setEtag($etag);
        $builder->setVersion(2);
        $builder->addBinding($role, $members);

        $result = $builder->result();

        $policy = [
            'bindings' => [
                [
                    'role' => $role,
                    'members' => $members
                ]
            ],
            'etag' => $etag,
            'version' => 2
        ];

        $this->assertEquals($policy, $result);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidPolicy()
    {
        $policy = ['foo' => 'bar'];
        $builder = new PolicyBuilder($policy);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidMember()
    {
        $builder = new PolicyBuilder;
        $builder->addBinding('test', [
            'test@test.com'
        ]);
    }

    public function testSetBindings()
    {
        $role = 'test';
        $members = [
            'user:test@test.com'
        ];

        $builder = new PolicyBuilder;
        $builder->addBinding($role, $members);

        $result = $builder->result();

        $policy = [
            'bindings' => [
                [
                    'role' => $role,
                    'members' => $members
                ]
            ]
        ];

        $this->assertEquals($policy, $result);

        $newMembers = [
            'group:group@test.com'
        ];

        $builder->setBindings([
            [
                'role' => $role,
                'members' => $newMembers
            ]
        ]);

        $newResult = $builder->result();

        $newPolicy = [
            'bindings' => [
                [
                    'role' => $role,
                    'members' => $newMembers
                ]
            ]
        ];

        $this->assertEquals($newPolicy, $newResult);
    }

    public function testConstructWithExistingPolicy()
    {
        $policy = [
            'bindings' => [
                [
                    'role' => 'test',
                    'members' => [
                        'user:test@test.com'
                    ]
                ]
            ],
            'etag' => 'foo',
            'version' => 2
        ];

        $builder = new PolicyBuilder($policy);
        $result = $builder->result();

        $this->assertEquals($policy, $result);
    }
}
