<?php

namespace SintraPimcoreBundle\OptionsProviders;

use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\DynamicOptionsProvider\MultiSelectOptionsProviderInterface;

/**
 * Dynamic Options Provider for Product's category_ids field 
 *
 * @author Marco Guiducci
 */
class ProductCategoryOptionsProvider implements MultiSelectOptionsProviderInterface{

    public function getOptions($context, $fieldDefinition): array {
        $category_ids = array();
        
        $categories = new DataObject\Category\Listing();
        $categories->setOrderKey("CONCAT(o_path, o_key)", false);
        
        $moreOption = $categories->count() > 0;
        while($moreOption){
            $category = $categories->current();
            
            $magentoId = $category->getMagentoid();
            $name = $category->getName();
            
            $level = $category->getLevel();
            for ($i = 0; $i < $level; $i++) {
                $name = "&nbsp;&nbsp;&nbsp;&nbsp; ".$name;
            }
            
            $category_ids[] = array("key" => $name, "value" => $magentoId);
            
            $moreOption = $categories->next();
        }
        
        return $category_ids;
    }

    public function hasStaticOptions($context, $fieldDefinition): bool {
        return true;
    }

}
