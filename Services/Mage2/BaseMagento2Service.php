<?php
namespace SintraPimcoreBundle\Services\Mage2;

use SintraPimcoreBundle\Services\BaseEcommerceService;
use Pimcore\Model\DataObject\Listing;
/**
 * Magento 2 Shop level logic
 * Needs to implement BaseEcommerceService abstract functions
 * Class Magento2Service
 */
abstract class BaseMagento2Service extends BaseEcommerceService {
    
    protected function mapServerMultipleField ($ecommObject, $fieldMap, $fieldsDepth, $language, $dataSource = null, TargetServer $server = null) {
        // End of recursion
        if(count($fieldsDepth) == 1) {
            /** @var Listing $dataSource */
            if ( method_exists($dataSource, 'current') ) {
                $dataSource = $dataSource->getObjects()[0];
            }
            $fieldValue = $this->getObjectField($fieldMap, $language, $dataSource);
            $apiField = $fieldsDepth[0];
            
            return $this->mapServerField($ecommObject, $fieldValue, $apiField);
        }
        
        $parentDepth = array_shift($fieldsDepth);


        /**
         * End of recursion with custom_attributes
         */
        if ($parentDepth == 'custom_attributes') {
            if ( method_exists($dataSource, 'current') ) {
                $dataSource = $dataSource->getObjects()[0];
            }
            $fieldValue = $this->getObjectField($fieldMap, $language, $dataSource);
            $apiField = $fieldsDepth[0];
            
            $customValue = [
                    'attribute_code' => $apiField,
                    'value' => $fieldValue
            ];
            $ecommObject[$parentDepth][] = $customValue;
            return $ecommObject;
        }

        /**
         * Recursion level > 1
         * For now, on magento2 there is no nested field mapping except custom_attributes
         * It should never reach this point with magento2.
         * TODO: image implementation should be developed in the future here for field mapping
         */
        return $this->mapServerMultipleField($ecommObject[$parentDepth], $fieldMap, $fieldsDepth, $language, $dataSource, $server);
    }
}