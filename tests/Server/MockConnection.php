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

use Ratchet\ConnectionInterface;

/**
 * Mock ConnectionInterface for Testing
 */
class MockConnection implements ConnectionInterface {
    public $resourceId;
    public $sentMessages = [];
    public $closed = false;

    public function __construct($rid)
    {
        $this->resourceId = $rid;
    }

    public function send($data): void 
    {
        $this->sentMessages[] = $data;
    }

    public function close(): void 
    {
        $this->closed = true;
    }

    public function remoteAddress(): string
    {
        return '127.0.0.1:8080';
    }

    public function getSentMessages(): array
    {
        return $this->sentMessages;
    }

    public function getLastMessage(): string
    {
        return end($this->sentMessages) ?: '';
    }
}