<?php

namespace SintraPimcoreBundle\Services\Mage2;

use SintraPimcoreBundle\Services\BaseEcommerceService;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\TargetServer;
use SintraPimcoreBundle\Utils\GeneralUtils;
use SintraPimcoreBundle\Utils\TargetServerUtils;
use Pimcore\Model\DataObject\AbstractObject;

/**
 * Provide methods and utils for objects synchronization for Magento2 servers
 * common for all object classes.
 * Must be extended for specific needs.
 * 
 * @author Sintra Consulting
 */
abstract class BaseMagento2Service extends BaseEcommerceService {

    /**
     * Update synchronization info for an object
     * 
     * @param Concrete $dataObject the object to update
     * @param $result the result of the API call
     * @param TargetServer $targetServer the server in which the product was synchronized
     * @param $parentId (optional) the partent id in case of object variant
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
     * In order to perform the recursion, the fieldname is expressed
     * dividing the sublevels of the API object by the "." (dot) character.
     * 
     * e.g. custom_attributes.description
     *
     * @param $ecommObject
     * @param Concrete $dataObject
     * @param TargetServer $targetServer
     * @param $classname
     * @param bool $isNew
     */
    public function toEcomm(&$ecommObject, $dataObject, TargetServer $targetServer, $classname, bool $isNew = false) {

        $fieldsMap = TargetServerUtils::getClassFieldMap($targetServer, $classname);
        $languages = $targetServer->getLanguages();

        /** @var FieldMapping $fieldMap */
        foreach ($fieldsMap as $fieldMap) {

            //get the value of each object field
            $apiField = $fieldMap->getServerField();

            //get the object tree exploding field name by the level separator
            $fieldsDepth = explode('.', $apiField);
            $ecommObject = $this->mapServerMultipleField($ecommObject, $fieldMap, $fieldsDepth, $languages[0], $dataObject, $targetServer);
        }
    }

    protected function mapServerMultipleField($ecommObject, $fieldMap, $fieldsDepth, $language, $dataSource = null, TargetServer $server = null) {

        $fieldValue = $this->getObjectField($fieldMap, $language, $dataSource);

        /**
         *  End of recursion
         */
        if (count($fieldsDepth) == 1) {
            return $this->mapServerField($ecommObject, $fieldValue, $fieldsDepth[0]);
        }

        /**
         * Start recursion for a sublevel
         */
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
         */
        return $this->mapServerMultipleField($ecommObject[$parentDepth], $fieldMap, $fieldsDepth, $language, $dataSource, $server);
    }

    /**
     * Add a custom attribute to the API object
     * 
     * @param array $ecommObject the API object
     * @param type $apiField the field name
     * @param type $fieldValue the field value
     */
    protected function extractCustomAttribute(&$ecommObject, $apiField, $fieldValue) {
        $customValue = [
            'attribute_code' => $apiField,
            'value' => $fieldValue
        ];

        $ecommObject["custom_attributes"][] = $customValue;
    }

}
