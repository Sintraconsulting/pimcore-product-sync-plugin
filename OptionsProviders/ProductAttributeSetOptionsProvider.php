<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace SintraPimcoreBundle\OptionsProviders;

use SintraPimcoreBundle\ApiManager\Mage2\AttributeSetAPIManager;
use Pimcore\Logger;
use Pimcore\Model\DataObject\ClassDefinition\DynamicOptionsProvider\SelectOptionsProviderInterface;

/**
 * Dynamic Options Provider for Product's attribute_set_id field 
 *
 * @author Utente
 */
class ProductAttributeSetOptionsProvider implements SelectOptionsProviderInterface{
    //put your code here
    public function getDefaultValue($context, $fieldDefinition) {
        $apiManager = AttributeSetAPIManager::getInstance();
        $attributeSet = $apiManager->getDefaultAttributeSet()->getItems()[0];
        
        return $attributeSet != null ? $attributeSet->getAttributeSetId() : "";
    }

    public function getOptions($context, $fieldDefinition): array {
        $attribute_set_ids = array();
        
        $apiManager = AttributeSetAPIManager::getInstance();
        $attributeSets = $apiManager->getAllAttributeSet();
        
        foreach ($attributeSets->getItems() as $attributeSet) {
            $attribute_set_ids[] = array("key" => $attributeSet->getAttributeSetName(), "value" => $attributeSet->getAttributeSetId());
        }
        
        return $attribute_set_ids;
    }

    public function hasStaticOptions($context, $fieldDefinition): bool {
        return true;
    }

}
