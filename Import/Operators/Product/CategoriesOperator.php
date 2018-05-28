<?php

namespace SintraPimcoreBundle\Import\Operators\Product;

use Pimcore\Model\DataObject\Category;
use SintraPimcoreBundle\Import\Operators\TransliterateOperator;
/**
 * Convert category path in comma-separated list of categories magento ids
 *
 * @author Marco Guiducci
 */
class CategoriesOperator extends TransliterateOperator{
    
    public function process($element, &$target, array &$rowData, $colIndex, array &$context = array()) {
        
        $category_ids = array();

        $categories = $rowData[$colIndex];
        
        $keyParts  = explode('>', $categories);
        foreach ($keyParts as $i => $keyPart) {
            $keyParts[$i] = $this->transliterate($keyPart);
        }
       
        $path = implode('/', $keyParts);
        
        $fullpath = '/categories/Default Category/'.$path;
        $category = $this->getCategory($fullpath);
        
        $level = $category->getLevel();
        while($level > 0){
            $category_ids[] = $category->getMagentoid();
            
            $parentId = $category->getParentId();
            $category = Category::getById($parentId, true);
            
            $level = $category->getLevel();
        }

        $target->setCategory_ids($category_ids);
    }
    
    private function getCategory($path){
        $categories = new Category\Listing();
        $categories->setCondition("CONCAT(o_path,o_key) = ?", $path);
        $categories->setLimit(1);
        
        $categories = $categories->load();

        if($categories){
            $category = $categories[0];
        }else{
            $category = null;
        }
        
        return $category;
    }

}
