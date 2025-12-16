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

namespace QuarkTodo\Frontend;

use QuarkTodo\Frontend\Render\View;

class MainPage {
    public static function output($data = []) {
        if (!isset($data['title'])) $data['title'] = 'Quark ToDo';
        if (!isset($data['description'])) $data['description'] = 'A collaborative todo-list made with PHP, WebSockets and Ratchet';
        if (!isset($data['style'])) $data['style'] = false;
        return View::render('MainPage', $data);
    }
}