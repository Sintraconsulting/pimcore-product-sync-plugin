<?php

namespace SintraPimcoreBundle\Services\Mage2;

use SintraPimcoreBundle\Services\BaseEcommerceService;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\TargetServer;
use SintraPimcoreBundle\Utils\GeneralUtils;
use SintraPimcoreBundle\Utils\TargetServerUtils;
use Pimcore\Model\DataObject\AbstractObject;

/**
 * Magento 2 Shop level logic
 * Needs to implement BaseEcommerceService abstract functions
 * Class Magento2Service
 */
abstract class BaseMagento2Service extends BaseEcommerceService {

    /**
     * 
     * @param Concrete $dataObject
     * @param $result
     * @param TargetServer $targetServer
     * @param $parentId
     */
    protected function setSyncObject($dataObject, $result, TargetServer $targetServer, $parentId = '') {
        $serverObjectInfo = GeneralUtils::getServerObjectInfo($dataObject, $targetServer);
        $serverObjectInfo->setSync(true);
        $serverObjectInfo->setSync_at($result["updatedAt"]);
        
        if($dataObject->getType() === AbstractObject::OBJECT_TYPE_OBJECT){
            $serverObjectInfo->setObject_id($result["id"]);
        }else{
            $serverObjectInfo->setObject_id($parentId);
            $serverObjectInfo->setVariant_id($result["id"]);
        }
        
        $dataObject->update(true);
    }

    /**
     * Get the mapping of field to export from the server definition.
     * For localized fields, the first valid language will be used.
     *
     * @param $ecommObject
     * @param Concrete $dataObject
     * @param TargetServer $targetServer
     * @param $classname
     * @param bool $isNew
     */
    public function toEcomm(&$ecommObject, $dataObject, TargetServer $targetServer, $classname, bool $isNew = false) {
        /**
         * In a general approach, API calls will be referred to the main website
         */
        $ecommObject["extension_attributes"]["website_ids"][] = 1;

        $fieldsMap = TargetServerUtils::getClassFieldMap($targetServer, $classname);
        $languages = $targetServer->getLanguages();

        /** @var FieldMapping $fieldMap */
        foreach ($fieldsMap as $fieldMap) {

            //get the value of each object field
            $apiField = $fieldMap->getServerField();

            $fieldsDepth = explode('.', $apiField);
            $ecommObject = $this->mapServerMultipleField($ecommObject, $fieldMap, $fieldsDepth, $languages[0], $dataObject, $targetServer);
        }
    }

    protected function mapServerMultipleField($ecommObject, $fieldMap, $fieldsDepth, $language, $dataSource = null, TargetServer $server = null) {

        $fieldValue = $this->getObjectField($fieldMap, $language, $dataSource);

        // End of recursion
        if (count($fieldsDepth) == 1) {
            return $this->mapServerField($ecommObject, $fieldValue, $fieldsDepth[0]);
        }

        $parentDepth = array_shift($fieldsDepth);
        $apiField = $fieldsDepth[0];

        /**
         * End of recursion with custom_attributes
         */
        if ($parentDepth == 'custom_attributes') {
            $this->extractCustomAttribute($ecommObject, $apiField, $fieldValue);
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

    protected function extractCustomAttribute(&$ecommObject, $apiField, $fieldValue) {
        $customValue = [
            'attribute_code' => $apiField,
            'value' => $fieldValue
        ];

        $ecommObject["custom_attributes"][] = $customValue;
    }

}
