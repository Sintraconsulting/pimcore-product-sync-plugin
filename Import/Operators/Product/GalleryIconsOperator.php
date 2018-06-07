<?php

namespace SintraPimcoreBundle\Import\Operators\Product;

use SintraPimcoreBundle\Import\Operators\PicklistOperator;
use Pimcore\Logger;
/**
 * Get Gallery Icons select Values by display names.
 *
 * @author Marco Guiducci
 */
class GalleryIconsOperator extends PicklistOperator{
    
    public function process($element, &$target, array &$rowData, $colIndex, array &$context = array()) {
        $galleryIcons = explode(",",$rowData[$colIndex]);
        
        $values = array();
        foreach ($galleryIcons as $icon) {
            $value = $this->getValueByDisplayName($target->getClass(), "gallery_icons", $icon);
            $values[] = $value;
        }
        
        $target->setGallery_icons($values);
    }

}
