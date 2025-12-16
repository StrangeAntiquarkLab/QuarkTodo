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

namespace QuarkTodo\Frontend\Render;

class View
{
    private static $templatePath = __DIR__ . '/../../../template/';
    private static $templateExtension = '.template.php';

    private static $componentPath = __DIR__ . '/../../../template/components/';
    private static $componentExtension = '.component.php';

    public static function render($template, $data)
    {
        $templateFile = self::$templatePath . $template . self::$templateExtension;

        if (!file_exists($templateFile)) {
            throw new \Exception('Template not found: ' . $template);
        }

        extract($data);
        ob_start();

        include $templateFile;
        $templateContent = ob_get_clean();

        // Replace Non-component placeholders for variables 
        foreach ($data as $key => $value) {
            $templateContent = str_replace('{{ $' . $key . ' }}', $value, $templateContent);
            $templateContent = str_replace('{{$' . $key . '}}', $value, $templateContent);
            $templateContent = str_replace('{{ ' . $key . ' }}', $value, $templateContent);
            $templateContent = str_replace('{{' . $key . '}}', $value, $templateContent);
        }

        $templateContent = self::processComponents($templateContent, $data);

        return $templateContent;

    }

    private static function processComponents($content, $data)
    {
        // <x-component-name>...</x-component-name>
        $patternNormal = '/<x-([a-zA-Z0-9-]+)(.*?)\/?>(.*?)<\/x-\1>/s';
        // <x-component-name/>
        $patternSelfclosing = '/<x-([a-zA-Z0-9-]+)(.*?)\/>/s';

        // First process self-closing tags
        $content = preg_replace_callback($patternSelfclosing, 
            function ($matches) use ($data) 
            {
                return self::renderComponent($matches[1], $matches[2], '', $data);
            }, 
            $content
        );

        // Then process tags with content in between the tags (Goes to $slotContent Variable inside the Component)
        return preg_replace_callback($patternNormal,
            function ($matches) use ($data) {
                return self::renderComponent($matches[1], $matches[2], $matches[3], $data);
            },
            $content
        );
    }

    private static function renderComponent($cName, $attributesInput, $slotContent, $data)
    {
        $componentFile = self::$componentPath . $cName . self::$componentExtension;
        if (!file_exists($componentFile)) {
            return "<!-- Component {$cName} not found -->";
        }

        // Parse Attributes
        $attributes = [];
        if (!empty(trim($attributesInput))) {
            preg_match_all('/([a-zA-Z0-9-]+)="([^"]*)"|([a-zA-Z0-9-]+)=\'([^\']*)\'|([a-zA-Z0-9-]+)=([^>\s]+)/', $attributesInput, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $key = $match[1] ?? $match[3] ?? $match[5];
                $value = $match[2] ?? $match[4] ?? $match[6];

                // Remove {} from template variables and get Value
                if (preg_match('/^{([a-zA-Z0-9_-]+)}$/', $value, $varMatch)) {
                    $varName = $varMatch[1];
                    $value = $data[$varName] ?? '';
                }

                $attributes[$key] = htmlspecialchars($value);
            }
        }

        // Get attributes to variables
        extract($attributes);

        // Set slot content
        $slotContent = trim($slotContent);

        // Start OB for component
        ob_start();
        include $componentFile;
        return ob_get_clean();
    }
    
    public static function getTemplatePath() {
        return self::$templatePath;
    }
    
    public static function getComponentPath() {
        return self::$componentPath;
    }
}
