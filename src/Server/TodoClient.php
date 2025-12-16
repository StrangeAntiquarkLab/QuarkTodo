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

namespace QuarkTodo\Server;

use Ratchet\ConnectionInterface;

class TodoClient {
    // Static
    private static $clients = [];
    
    // Dynamic
    private $id;
    private $conn;

    
    /**
     * Create a new TodoClient instance, or return an existing one.
     *
     * If a TodoClient instance with the given ID already exists, it will be returned.
     * Otherwise, a new TodoClient instance will be created and stored under the given ID.
     *
     * @param string $id The ID of the TodoClient to create or retrieve.
     * @param ConnectionInterface $conn The connection to associate with the TodoClient.
     *
     * @return TodoClient The created or retrieved TodoClient instance.
     */
    public static function create(ConnectionInterface $conn): TodoClient
    {
        $id = $conn->resourceId;
        if (isset(self::$clients[$id])) {
            // TODO: Log
            return self::$clients[$id];
        }

        self::$clients[$id] = new TodoClient($id, $conn);
        // TODO: Log
        return self::$clients[$id];
    }

    /**
     * Remove a TodoClient instance from the cache.
     *
     * @param ConnectionInterface $conn The connection associated with the TodoClient to remove.
     */
    public static function remove(ConnectionInterface $conn): void
    {
        $id = $conn->resourceId;
        if (isset(self::$clients[$id])) {
            unset(self::$clients[$id]);
        }
    }

    /**
     * Broadcast a message to all connected clients except the one that sent the message.
     *
     * @param string $content The content of the message to broadcast.
     * @param ConnectionInterface $from The connection that sent the message to broadcast.
     */
    public static function broadcast($content, $from): void
    {
        foreach (self::$clients as $client) {
            if ($client->id == $from->resourceId) {
                continue;
            }
            $client->send($content);
        }
    }

    /**
     * Private constructor to create a new TodoClient instance.
     *
     * @param string $id The ID of the TodoClient to create.
     * @param ConnectionInterface $conn The connection to associate with the TodoClient.
     */
    private function __construct($id, $conn)
    {
        $this->id = $id;
        $this->conn = $conn;
    }

    /**
     * Send a message to the client associated with this TodoClient instance.
     *
     * @param string $content The content of the message to send.
     */
    public function send($content) {
        $this->conn->send($content);
    }
}