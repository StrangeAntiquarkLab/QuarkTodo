<?php
/** 
 * QuarkTodo - PHP Websocket application for simple collaborative todo-lists
 * 
 * @link https://github.com/StrangeAntiquarkLab/QuarkTodo
 * @author Strange Antiquark Dev (https://strangelab.dev | https://github.com/StrangeAntiquarkLab)
 * 
 * Copyright (c) 2025 Strange Antiquark Dev
 * Licensed under the "Fuck the GPL License" -> License.md in the main directory
 * @license: https://github.com/StrangeAntiquarkLab/QuarkTodo/blob/master/LICENSE.txt
 */

namespace QuarkTodo\Tests\Server;

use PHPUnit\Framework\TestCase;
use QuarkTodo\Server\TodoClient;
use QuarkTodo\Tests\Server\MockConnection;

/**
 * TodoClient Test Cases
 */
class TodoClientTest extends TestCase
{
    /**
     * Clear static clients every time we run a new test
     */
    protected function setUp(): void
    {
        $reflection = new \ReflectionClass(TodoClient::class);
        $property = $reflection->getProperty('clients');
        $property->setValue(null, []);
    }

    /** 
     * Test if TodoClient::Create returns a TodoClient instance
    */
    public function testCreateReturnsTodoClientInstance(): void
    {
        $conn = new MockConnection('1');
        $client = TodoClient::create($conn);

        $this->assertInstanceOf(TodoClient::class, $client, 'TodoClient::create() should return a TodoClient instance.');
    }

    /**
     * Test if TodoClient::create() stores the client in the static clients variable, and does so correctly
     */
    public function testCreateStoresClientInStaticClientsVariable(): void
    {
        $i = 1;
        $var = '';

        while ($i < 10) {
            $var = 'client' . (string) $i;

            $conn = new MockConnection((string) $i);
            $$var = TodoClient::create($conn);
            $i++;
        }

        $reflection = new \ReflectionClass(TodoClient::class);
        $property = $reflection->getProperty('clients');
        $clients = $property->getValue();

        $this->assertCount(9, $clients, 'TodoClient::create() should store 9 clients in the static clients variable, when it is called 9 times with different connections.');

        $j = 1;
        $var = '';

        while ($j < 10) {
            $var = 'client' . (string) $j;
            $this->assertArrayHasKey($j, $clients, 'TodoClient::create() should store the client in the static clients variable under the correct key.');
            $this->assertSame($$var, $clients[(string) $j], 'The client returned by TodoClient::create() should be stored in the static clients variable under the correct key. (failed key: ' . $j . ')');
            $j++;
        }
    }

    /**
     * Test if TodoClient::create() returns existing client, if a new connection is created but it already exists
     */
    public function testCreateReturnsExistingClientForSameConnection(): void
    {
        $conn = new MockConnection('1');
        $client1 = TodoClient::create($conn);
        $client2 = TodoClient::create($conn);

        $this->assertSame($client1, $client2, 'TodoClient::create() should return an existing TodoClient instance if a new connection is created but the ID already exists instead of creating a new one.');
    }

    /**
     * Test that TodoClient::remove() removes the client from the static variable $clients
     */
    public function testRemoveDeletesClientFromStaticClientsVariable(): void
    {
        $conn = new MockConnection('1');
        $client = TodoClient::create($conn);

        // Check that it actually exists
        $reflection = new \ReflectionClass(TodoClient::class);
        $property = $reflection->getProperty('clients');
        $clients = $property->getValue();

        $this->assertArrayHasKey('1', $clients, 'TodoClient::create() should store the client in the static clients variable under the correct key.');

        // Remove the client
        TodoClient::remove($conn);

        // Check that it doesn't exist anymore
        $clients = $property->getValue();
        $this->assertArrayNotHasKey('1', $clients, 'TodoClient::remove() should remove the client from the static clients variable.');
    }

    /**
     * Test that TodoClient::remove() handles all cases of a client not existing correctly and without any errors.
     */
    public function testRemoveHandlesNonExistingClient(): void
    {
        $conn = new MockConnection('1');
        
        // Remove non-existent client (should not throw a error)
        TodoClient::remove($conn);

        // Create and remove normally to see if that still works
        TodoClient::create($conn);
        TodoClient::remove($conn);

        // Now remove a client that already existed again (should also not error)
        TodoClient::remove($conn);

        $this->assertTrue(true); // Just checks that no exceptions were thrown
    }

    /**
     * Test that TodoClient::broadcast() sends the message to all clients except the one that sent the msg and that the message is correct
     */
    public function testBroadcastSendsMessageToAllClientsExceptSender(): void
    {
        // Create 3 Clients
        $conn1 = new MockConnection('1');
        $conn2 = new MockConnection('2');
        $conn3 = new MockConnection('3');

        $client1 = TodoClient::create($conn1);
        $client2 = TodoClient::create($conn2);
        $client3 = TodoClient::create($conn3);

        // Broadcast from connection 1
        TodoClient::broadcast('Hello', $conn1);

        // Verify connections 2&3 recieved the message, but not connection 1
        $this->assertCount(0, $conn1->getSentMessages(), 'The sender of TodoClient::broadcast() should not receive any messages from the same broadcast he triggered.');
        $this->assertCount(1, $conn2->getSentMessages(), 'The recipients of TodoClient::broadcast() should receive 1 message. Recipient 1 (conn 2) did not.');
        $this->assertCount(1, $conn3->getSentMessages(), 'The recipients of TodoClient::broadcast() should receive 1 message. Recipient 2 (conn 3) did not.');

        // Verify the message is correct
        $this->assertSame('Hello', $conn2->getSentMessages()[0], 'TodoClient::broadcast() should have sent the correct message. Recipient 1 (conn 2) did not receive the correct one.');
        $this->assertSame('Hello', $conn3->getSentMessages()[0], 'TodoClient::broadcast() should have sent the correct message. Recipient 2 (conn 3) did not receive the correct one.');
    }

    /**
     * Test that TodoClient::broadcast() works with empty client list
     */
    public function testBroadcastSendsMessageToEmptyClientList(): void
    {
        TodoClient::broadcast('Hello', new MockConnection('1'));

        // Just checks that no exceptions were thrown
        $this->assertTrue(true);
    }

    /**
     * Test that TodoClient::send() sends message to connection
     */
    public function testSendMethodSendsMessageToConnection(): void
    {
        $conn = new MockConnection('1');
        $client = TodoClient::create($conn);

        $msg = ['Hello, World!', 'Another fun message!'];

        $client->send($msg[0]);
        $client->send($msg[1]);

        $this->assertCount(2, $conn->getSentMessages(), 'When TodoClient::send() sends 2 messages to a connection, it should have been sent 2 messages.');
        $this->assertEquals($msg[0], $conn->getSentMessages()[0], 'TodoClient::send() should have sent the correct messages.');
        $this->assertEquals($msg[1], $conn->getSentMessages()[1], 'TodoClient::send() should have sent the correct messages.');
    }

    /**
     * Test that the private constructor prevents direct instantiation, but exists
     */
    public function testConstructorIsPrivate(): void
    {
        $reflection = new \ReflectionClass(TodoClient::class);
        $constructor = $reflection->getConstructor();

        $this->assertNotNull($constructor, 'The constructor of TodoClient should exist.');
        $this->assertTrue($constructor->isPrivate(), 'The constructor of TodoClient should be private.');

        $this->expectException(\Error::class);
        new TodoClient('1', new MockConnection('1'));
    }

    /**
     * Test that multiple broadcasts work correctly
     */
    public function testMultipleBroadcastsWorkCorrectly(): void
    {
        $conn1 = new MockConnection('1');
        $conn2 = new MockConnection('2');

        TodoClient::create($conn1);
        TodoClient::create($conn2);

        // First broadcast
        TodoClient::broadcast('Hello', $conn1);
        $this->assertEquals('Hello', $conn2->getLastMessage(), 'TodoClient::broadcast() should have sent the correct message. Recipient (conn 2) did not receive the correct one.');
        $this->assertCount(0, $conn1->getSentMessages(), 'The sender of TodoClient::broadcast() should not receive any messages from the same broadcast he triggered.');

        // Clear conn2 messages
        $conn2->sentMessages = [];

        // Second broadcast
        TodoClient::broadcast('ByeBye', $conn2);
        $this->assertEquals('ByeBye', $conn1->getLastMessage(), 'TodoClient::broadcast() should have sent the correct message. Recipient (conn 1) did not receive the correct one.');
        $this->assertCount(0, $conn2->getSentMessages(), 'The sender of TodoClient::broadcast() should not receive any messages from the same broadcast he triggered.');
    }

    /**
     * Test that client removal doesn't affect broadcasting to other clients
     */
    public function testClientRemovalDoesNotAffectBroadcasting(): void
    {
        $conn1 = new MockConnection('1');
        $conn2 = new MockConnection('2');
        $conn3 = new MockConnection('3');

        TodoClient::create($conn1);
        TodoClient::create($conn2);
        TodoClient::create($conn3);

        // Remove a client
        TodoClient::remove($conn2);

        // Broadcast from connection 1
        TodoClient::broadcast('Hello', $conn1);

        // Only client 3 should receive
        $this->assertCount(0, $conn1->getSentMessages(), 'The sender of TodoClient::broadcast() should not receive any messages from the same broadcast he triggered.');
        $this->assertCount(0, $conn2->getSentMessages(), 'A removed Client from the TodoClient Array should not receive any messages from the broadcast method.');
        $this->assertCount(1, $conn3->getSentMessages(), 'The recipients of TodoClient::broadcast() should receive 1 message. Recipient (conn 3) did not.');
        $this->assertSame('Hello', $conn3->getSentMessages()[0], 'TodoClient::broadcast() should have sent the correct message. Recipient (conn 3) did not receive the correct one.');
    }

}