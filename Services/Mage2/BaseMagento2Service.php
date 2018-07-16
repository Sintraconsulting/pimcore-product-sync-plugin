<?php
namespace SintraPimcoreBundle\Services\Mage2;

use SintraPimcoreBundle\Services\BaseEcommerceService;
use Pimcore\Model\DataObject\Listing;
use Pimcore\Model\DataObject\TargetServer;
/**
 * Magento 2 Shop level logic
 * Needs to implement BaseEcommerceService abstract functions
 * Class Magento2Service
 */
abstract class BaseMagento2Service extends BaseEcommerceService {
    
    /**
     * 
     * @param $dataObject
     * @param $results
     * @param TargetServer $targetServer
     */
    protected function setSyncProducts ($dataObject, $results, TargetServer $targetServer) {
        $serverObjectInfo = $this->getServerObjectInfo($dataObject, $targetServer);
        $serverObjectInfo->setSync(true);
        $serverObjectInfo->setSync_at($results["updatedAt"]);
        $serverObjectInfo->setObject_id($results["id"]);
        $dataObject->update(true);
    }
    
    /**
     * Get the mapping of field to export from the server definition.
     * For localized fields, the first valid language will be used.
     *
     * @param $ecommObject
     * @param Listing $dataObjects
     * @param TargetServer $targetServer
     * @param $classname
     * @param bool $updateProductPrices
     */
    protected function toEcomm (&$ecommObject, $dataObjects, TargetServer $targetServer, $classname, bool $updateProductPrices = false) {
        $fieldsMap = TargetServerUtils::getClassFieldMap($targetServer, $classname);
        $languages = $targetServer->getLanguages();
        
        /** @var FieldMapping $fieldMap */
        foreach ($fieldsMap as $fieldMap) {

            //get the value of each object field
            $apiField = $fieldMap->getServerField();

            $fieldsDepth = explode('.', $apiField);
            $ecommObject = $this->mapServerMultipleField($ecommObject, $fieldMap, $fieldsDepth, $languages[0], $dataObjects, $targetServer);

        }

    }
    
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