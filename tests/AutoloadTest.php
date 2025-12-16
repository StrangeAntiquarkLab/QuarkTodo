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

namespace QuarkTodo\Tests;

use PHPUnit\Framework\TestCase;

class AutoloadTest extends TestCase {
    public function testMainNamespaceAutoloads(): void {
        // Test that we can reference classes from main namespace
        $this->assertTrue(class_exists('QuarkTodo\Server\TodoServer') || 
                        interface_exists('QuarkTodo\Server\TodoServer'));
    }
    
    public function testTestsNamespaceAutoloads(): void {
        // This test class itself should be autoloadable
        $this->assertTrue(class_exists(__CLASS__));
    }
}