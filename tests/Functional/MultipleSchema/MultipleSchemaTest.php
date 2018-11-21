<?php

namespace Overblog\GraphQLBundle\Tests\Functional\MultipleSchema;

use GraphQL\Error\InvariantViolation;
use Overblog\GraphQLBundle\Tests\Functional\TestCase;

class MultipleSchemaTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();

        static::bootKernel(['test_case' => 'multipleSchema']);
    }

    public function testPublicSchema()
    {
        $result = $this->executeGraphQLRequest('{foo}', [], 'public');
        $this->assertSame('foo', $result['data']['foo']);
        $this->assertSchemaQueryTypeName('PublicQuery');

        $result = $this->executeGraphQLRequest('{users{edges{node{username}}}}', [], 'public');
        $this->assertSame([['node' => ['username' => 'user1']]], $result['data']['users']['edges']);

        $query = <<<'EOF'
mutation M {
  addUser(input: {username: "user1"}) {
    user {
      username
    }
  }
}
EOF;
        $expectedData = [
            'addUser' => [
                'user' => ['username' => 'user1'],
            ],
        ];

        $this->assertGraphQL($query, $expectedData, null, [], 'public');
    }

    public function testInternalSchema()
    {
        $result = $this->executeGraphQLRequest('{bar foo}', [], 'internal');
        $this->assertSame('bar', $result['data']['bar']);
        $this->assertSame('foo', $result['data']['foo']);
        $this->assertSchemaQueryTypeName('InternalQuery');

        $result = $this->executeGraphQLRequest('{users{edges{node{username email}}}}', [], 'internal');
        $this->assertSame([['node' => ['username' => 'user1', 'email' => 'topsecret']]], $result['data']['users']['edges']);

        $query = <<<'EOF'
mutation M {
  addUser(input: {username: "user1"}) {
    user {
      username
      email
    }
  }
}
EOF;
        $expectedData = [
            'addUser' => [
                'user' => ['username' => 'user1', 'email' => 'email1'],
            ],
        ];

        $this->assertGraphQL($query, $expectedData, null, [], 'internal');
    }

    public function testUnknownTypeShouldNotInfinityLoop()
    {
        $schema = $this->getContainer()->get('overblog_graphql.request_executor')->getSchema('public');
        $this->expectException(InvariantViolation::class);
        $this->expectExceptionMessage('Type loader is expected to return valid type "unknown", but it returned null');
        $schema->getType('unknown');
    }

    private function assertSchemaQueryTypeName($typeName)
    {
        $query = $this->getContainer()->get('overblog_graphql.type_resolver')->resolve($typeName);
        $this->assertSame('Query', $query->name);
    }
}
