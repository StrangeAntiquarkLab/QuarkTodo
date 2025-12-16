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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Quark ToDo' ?></title>
    <?php if(isset($css) && $css): ?>
    <link rel="stylesheet" href="/style/<?= htmlspecialchars($css) ?>">
    <?php endif; ?>
    <?php if(!empty($slotContent)): ?>
    <meta name="description" content="<?= htmlspecialchars($slotContent) ?>">
    <?php endif; ?>
</head>