<?php

namespace SintraPimcoreBundle\OptionsProviders;

use Pimcore\Model\DataObject\ClassDefinition\DynamicOptionsProvider\MultiSelectOptionsProviderInterface;

/**
 * Dynamic Options Provider that display valid languages for a TargetServer.
 * Languages are taken from Pimcore configuration
 *
 * @author Sintra Consulting
 */
class SystemLanguagesOptionProvider implements MultiSelectOptionsProviderInterface{

    public function hasStaticOptions($context, $fieldDefinition): bool {
        return true;
    }
    
    public function getOptions($context, $fieldDefinition): array {
        $fields = array();
        
        $config = \Pimcore\Config::getSystemConfig();
        $language = $config->general->language;
        $validLanguages = explode(",",$config->general->validLanguages);
        
        foreach ($validLanguages as $lang) {
            $fields[] = array(
                "key" => locale_get_display_name($lang,$language),
                "value" => $lang
            );
        }
        
        return $fields;
    }

}
