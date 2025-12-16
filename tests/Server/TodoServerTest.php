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
use QuarkTodo\Server\TodoServer;
use QuarkTodo\Server\TodoClient;
use QuarkTodo\Tests\Server\MockConnection;

class TodoServerTest extends TestCase
{
    /**
     * @var TodoServer
     */
    private $server;

    /**
     * Setup Testenvironment
     */
    public function setUp(): void
    {
        $this->server = new TodoServer();

        // Reset the static clients variable each test!
        $reflection = new \ReflectionClass(TodoClient::class);
        $property = $reflection->getProperty('clients');
        $property->setValue(null, []);
    }

    /**
     * Test constructor returns instance
     */
    public function testConstructorReturnsInstance(): void
    {
        $this->assertInstanceOf(TodoServer::class, $this->server, 'TodoServer constructor should return a TodoServer instance.');
    }

    /**
     * Test onOpen creates TodoClient and sends current memory
     */
    public function testOnOpenCreatesTodoClientAndSendsMemory(): void
    {
        $conn = new MockConnection('1');
        
        // Set initial memory
        $reflection = new \ReflectionClass($this->server);
        $property = $reflection->getProperty('memory');
        $property->setValue($this->server, 'Initial Todo Data, I am a Test!');

        // Trigger onOpen
        $this->server->onOpen($conn);

        // Check if TodoClient was created and sent the correct data
        $clientReflection = new \ReflectionClass(TodoClient::class);
        $clientProperty = $clientReflection->getProperty('clients');
        $clients = $clientProperty->getValue();

        $this->assertArrayHasKey('1', $clients, 'TodoServer::onOpen() should create a TodoClient instance under the correct key in TodoClient::$clients variable.');
        $this->assertCount(1, $conn->getSentMessages(), 'TodoServer::onOpen() should send 1 message to the client on initialization (onOpen). It did not.');
        $this->assertEquals('Initial Todo Data, I am a Test!', $conn->getLastMessage(), 'TodoServer::onOpen() should send the correct data to the client on initialization (onOpen). It did not.');
    }

    /**
     * Test onOpen with multiple connections
     */
    public function testOnOpenWithMultipleConnections(): void {
        $conn1 = new MockConnection('1');
        $conn2 = new MockConnection('2');
        $conn3 = new MockConnection('3');
        
        // Set the starting memory
        $reflection = new \ReflectionClass($this->server);
        $property = $reflection->getProperty('memory');
        $property->setValue($this->server, 'Shared todo list for testing purposes!');
        
        // Open connections
        $this->server->onOpen($conn1);
        $this->server->onOpen($conn2);
        $this->server->onOpen($conn3);
        
        // Verify all clients were created
        $clientReflection = new \ReflectionClass(TodoClient::class);
        $clientProperty = $clientReflection->getProperty('clients');
        $clients = $clientProperty->getValue();
        
        $this->assertCount(3, $clients, 'Should create client for each connection');
        $this->assertArrayHasKey('1', $clients);
        $this->assertArrayHasKey('2', $clients);
        $this->assertArrayHasKey('3', $clients);
        
        // Verify each connection received initial memory
        $this->assertEquals('Shared todo list for testing purposes!', $conn1->getLastMessage());
        $this->assertEquals('Shared todo list for testing purposes!', $conn2->getLastMessage());
        $this->assertEquals('Shared todo list for testing purposes!', $conn3->getLastMessage());
    }

    /**
     * Test onMessage updates the memory and broadcasts it to all the clients
     */
    public function testOnMessageUpdatesMemoryAndBroadcasts(): void {
        $conn1 = new MockConnection('1');
        $conn2 = new MockConnection('2');
        $conn3 = new MockConnection('3');
        
        // Open connections
        $this->server->onOpen($conn1);
        $this->server->onOpen($conn2);
        $this->server->onOpen($conn3);
        
        // Clear initial messages
        $conn1->sentMessages = [];
        $conn2->sentMessages = [];
        $conn3->sentMessages = [];
        
        // Send message from connection 1
        $message = 'New todo item';
        $this->server->onMessage($conn1, $message);
        
        // Verify memory was updated
        $reflection = new \ReflectionClass($this->server);
        $property = $reflection->getProperty('memory');
        $memory = $property->getValue($this->server);
        
        $this->assertEquals($message, $memory, 'Server memory should be updated');
        
        // Verify message was broadcast to connections 2 and 3, but not 1
        $this->assertCount(0, $conn1->getSentMessages(), 'Sender should not receive own message');
        $this->assertCount(1, $conn2->getSentMessages(), 'Other client (2) should receive broadcast');
        $this->assertCount(1, $conn3->getSentMessages(), 'Other client (3) should receive broadcast');
        
        $this->assertEquals($message, $conn2->getLastMessage(), 'TodoClient::broadcast() should have sent the correct message. Recipient (conn 2) did not receive the correct one.');
        $this->assertEquals($message, $conn3->getLastMessage(), 'TodoClient::broadcast() should have sent the correct message. Recipient (conn 3) did not receive the correct one.');
    }

    /**
     * Test onMessage with empty memory
     */
    public function testOnMessageWithEmptyMemory(): void 
    {
        $conn = new MockConnection('1');
        $this->server->onOpen($conn);
        
        // Clear initial message
        $conn->sentMessages = [];
        
        // Initial memory should be empty string
        $reflection = new \ReflectionClass($this->server);
        $property = $reflection->getProperty('memory');
        $initialMemory = $property->getValue($this->server);
        
        $this->assertEquals('', $initialMemory, 'Initial memory should be empty string');
        
        // Send message
        $this->server->onMessage($conn, 'First todo');
        
        $updatedMemory = $property->getValue($this->server);
        $this->assertEquals('First todo', $updatedMemory, 'Memory should be updated');
        
    }

    /**
     * Test onMessage overwrites previous memory
     */
    public function testOnMessageOverwritesPreviousMemory(): void {
        $conn = new MockConnection('1');
        $this->server->onOpen($conn);
        
        // Set initial memory via reflection
        $reflection = new \ReflectionClass($this->server);
        $property = $reflection->getProperty('memory');
        $property->setValue($this->server, 'Old todo list');
        
        // Clear connection messages
        $conn->sentMessages = [];
        
        // Send new message
        $this->server->onMessage($conn, 'New todo list');
        
        // Verify memory was overwritten
        $memory = $property->getValue($this->server);
        $this->assertEquals('New todo list', $memory, 'Memory should be overwritten');
    }

    /**
     * Test onMessage with multiple sequential messages
     */
    public function testOnMessageWithMultipleSequentialMessages(): void {
        $conn1 = new MockConnection('1');
        $conn2 = new MockConnection('2');
        
        $this->server->onOpen($conn1);
        $this->server->onOpen($conn2);
        
        // Clear initial messages
        $conn1->sentMessages = [];
        $conn2->sentMessages = [];
        
        // Send first message
        $this->server->onMessage($conn1, 'Message 1');
        $this->assertEquals('Message 1', $conn2->getLastMessage());
        
        // Clear for second message
        $conn2->sentMessages = [];
        
        // Send second message
        $this->server->onMessage($conn2, 'Message 2');
        $this->assertEquals('Message 2', $conn1->getLastMessage());
        
        // Verify final memory
        $reflection = new \ReflectionClass($this->server);
        $property = $reflection->getProperty('memory');
        $memory = $property->getValue($this->server);
        
        $this->assertEquals('Message 2', $memory, 'Memory should reflect last message');
    }

    /**
     * Test onClose removes TodoClient
     */
    public function testOnCloseRemovesTodoClient(): void {
        $conn1 = new MockConnection('1');
        $conn2 = new MockConnection('2');
        
        // Open connections
        $this->server->onOpen($conn1);
        $this->server->onOpen($conn2);
        
        // Verify clients exist
        $clientReflection = new \ReflectionClass(TodoClient::class);
        $clientProperty = $clientReflection->getProperty('clients');
        
        $clients = $clientProperty->getValue();
        $this->assertCount(2, $clients);
        
        // Close connection 1
        $this->server->onClose($conn1);
        
        // Verify client 1 was removed
        $clients = $clientProperty->getValue();
        $this->assertCount(1, $clients);
        $this->assertArrayNotHasKey('1', $clients);
        $this->assertArrayHasKey('2', $clients);
        
        // Close connection 2
        $this->server->onClose($conn2);
        
        // Verify all clients removed
        $clients = $clientProperty->getValue();
        $this->assertCount(0, $clients);
    }
    
    /**
     * Test onClose with non-existent client
     */
    public function testOnCloseWithNonExistentClient(): void {
        $conn = new MockConnection('1');
        
        // Try to close non-existent client (should not error)
        $this->server->onClose($conn);
        
        $this->assertTrue(true); // Just verifying no exception
    }
    
    /**
     * Test onError closes connection
     */
    public function testOnErrorClosesConnection(): void {
        $conn = new MockConnection('1');
        
        // Initially not closed
        $this->assertFalse($conn->closed, 'Connection should not be closed initially');
        
        // Trigger error
        $exception = new \Exception('Test error');
        $this->server->onError($conn, $exception);
        
        $this->assertTrue($conn->closed, 'Connection should be closed on error');
    }
    
    /**
     * Test onError with multiple connections only closes affected connection
     */
    public function testOnErrorOnlyClosesAffectedConnection(): void {
        $conn1 = new MockConnection('1');
        $conn2 = new MockConnection('2');
        
        // Open connections
        $this->server->onOpen($conn1);
        $this->server->onOpen($conn2);
        
        // Trigger error on connection 1 only
        $this->server->onError($conn1, new \Exception('Error on conn1'));
        
        $this->assertTrue($conn1->closed, 'Connection 1 should be closed');
        $this->assertFalse($conn2->closed, 'Connection 2 should remain open');
        
        // Verify client 2 still exists
        $clientReflection = new \ReflectionClass(TodoClient::class);
        $clientProperty = $clientReflection->getProperty('clients');
        $clients = $clientProperty->getValue();
        
        $this->assertArrayNotHasKey('1', $clients, 'Client 1 should be removed');
        $this->assertArrayHasKey('2', $clients, 'Client 2 should still exist');
    }
    
    /**
     * Test integration: full client lifecycle
     */
    public function testFullClientLifecycle(): void {
        $conn1 = new MockConnection('1');
        $conn2 = new MockConnection('2');
        
        // Open connections
        $this->server->onOpen($conn1);
        $this->server->onOpen($conn2);
        
        // Verify clients created
        $clientReflection = new \ReflectionClass(TodoClient::class);
        $clientProperty = $clientReflection->getProperty('clients');
        
        $clients = $clientProperty->getValue();
        $this->assertCount(2, $clients);
        
        // Send message from client 1
        $conn1->sentMessages = [];
        $conn2->sentMessages = [];
        
        $this->server->onMessage($conn1, 'Todo from client 1');
        $this->assertEquals('Todo from client 1', $conn2->getLastMessage());
        $this->assertCount(0, $conn1->getSentMessages());
        
        // Send message from client 2
        $conn1->sentMessages = [];
        $conn2->sentMessages = [];
        
        $this->server->onMessage($conn2, 'Todo from client 2');
        $this->assertEquals('Todo from client 2', $conn1->getLastMessage());
        $this->assertCount(0, $conn2->getSentMessages());
        
        // Close client 1
        $this->server->onClose($conn1);
        
        $clients = $clientProperty->getValue();
        $this->assertCount(1, $clients);
        $this->assertArrayNotHasKey('1', $clients);
        $this->assertArrayHasKey('2', $clients);
        
        // Send message from remaining client 2 (no one to receive)
        $this->server->onMessage($conn2, 'Last message');
        
        // Verify memory updated
        $reflection = new \ReflectionClass($this->server);
        $property = $reflection->getProperty('memory');
        $memory = $property->getValue($this->server);
        
        $this->assertEquals('Last message', $memory);
    }
    
    /**
     * Test that TodoClient assignment works correctly
     */
    public function testTodoClientAssignment(): void {
        $conn1 = new MockConnection('1');
        $conn2 = new MockConnection('2');
        
        // Open first connection
        $this->server->onOpen($conn1);
        
        // Get TodoClient for connection 1
        $clientReflection = new \ReflectionClass(TodoClient::class);
        $clientProperty = $clientReflection->getProperty('clients');
        $clients = $clientProperty->getValue();
        
        $client1 = $clients[1];
        
        // Verify client1 properties
        $idProperty = $clientReflection->getProperty('id');
        $connProperty = $clientReflection->getProperty('conn');
        
        $this->assertEquals(1, $idProperty->getValue($client1));
        $this->assertSame($conn1, $connProperty->getValue($client1));
        
        // Open second connection
        $this->server->onOpen($conn2);
        $clients = $clientProperty->getValue();
        $client2 = $clients[2];
        
        $this->assertEquals(2, $idProperty->getValue($client2));
        $this->assertSame($conn2, $connProperty->getValue($client2));
        
        // Verify clients are different instances
        $this->assertNotSame($client1, $client2);
    }
    
    /**
     * Test memory persistence across connections
     */
    public function testMemoryPersistenceAcrossConnections(): void {
        $conn1 = new MockConnection('1');
        $conn2 = new MockConnection('2');
        
        // Open connection 1 and send message
        $this->server->onOpen($conn1);
        $conn1->sentMessages = []; // Clear initial message
        
        $this->server->onMessage($conn1, 'Persistent data');
        
        // Close connection 1
        $this->server->onClose($conn1);
        
        // Open connection 2 - should receive persisted memory
        $this->server->onOpen($conn2);
        
        $this->assertEquals('Persistent data', $conn2->getLastMessage(), 
            'New connection should receive persisted memory');
    }


}