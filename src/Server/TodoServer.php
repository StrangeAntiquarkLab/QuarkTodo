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
use Ratchet\MessageComponentInterface;

use QuarkTodo\Server\TodoClient;

class TodoServer implements MessageComponentInterface
{
    // TODO: Support multiple Todo-Lists
    private string $memory = '';

    /**
     * Initializes the TodoServer instance.
     *
     * @return static
     */
    public function __construct()
    {
        return $this;
    }

    public function onOpen(ConnectionInterface $conn): void
    {
        // TODO: Auth
        $client = TodoClient::create($conn);
        $client->send($this->memory);
    }

    public function onMessage(ConnectionInterface $from, $msg): void
    {
        // TODO: Validation
        $this->memory = $msg;
        TodoClient::broadcast($this->memory, $from);
    }

    /**
     * Called when a connection is closed.
     *
     * Removes the connection from the list of TodoClient instances.
     *
     * @param ConnectionInterface $conn The connection that was closed.
     */
    public function onClose(ConnectionInterface $conn): void
    {
        TodoClient::remove($conn);
    }

    /**
     * Called when an error occurs on a connection.
     *
     * Closes the connection.
     *
     * @param ConnectionInterface $conn The connection on which the error occurred.
     * @param \Exception $e The exception that was thrown.
     */
    public function onError(ConnectionInterface $conn, \Exception $e): void 
    {
        TodoClient::remove($conn);
        $conn->close();
        // TODO: Log
    }
}