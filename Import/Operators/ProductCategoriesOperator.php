<?php

namespace SintraPimcoreBundle\Import\Operators;

use Pimcore\Logger;
use Pimcore\Model\DataObject\Category;
use Pimcore\DataObject\Import\ColumnConfig\Operator\AbstractOperator;
/**
 * Convert category path in comma-separated list of categories magento ids
 *
 * @author Marco Guiducci
 */
class ProductCategoriesOperator extends AbstractOperator{
    
    public function process($element, &$target, array &$rowData, $colIndex, array &$context = array()) {
        $category_ids = array();
        
        $fullpath = $rowData[$colIndex];
        $category = Category::getByPath($fullpath, true);
        
        $level = $category->getLevel();
        while($level > 0){
            $category_ids[] = $category->getMagentoid();
            
            $parentId = $category->getParentId();
            $category = Category::getById($parentId, true);
            
            $level = $category->getLevel();
        }

        $target->setCategory_ids($category_ids);
    }

}
