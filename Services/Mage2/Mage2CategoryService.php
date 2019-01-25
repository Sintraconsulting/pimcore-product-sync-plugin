<?php
namespace SintraPimcoreBundle\Services\Mage2;

use Pimcore\Model\DataObject\Category;
use SintraPimcoreBundle\ApiManager\Mage2\CategoryAPIManager;
use Pimcore\Logger;
use SintraPimcoreBundle\Services\InterfaceService;
use Pimcore\Model\DataObject\TargetServer;
use SintraPimcoreBundle\Utils\GeneralUtils;

/**
 * Implement methods for categories synchronization on Magento2 servers
 * 
 * @author Sintra Consulting
 */
class Mage2CategoryService extends BaseMagento2Service implements InterfaceService {

    /**
     * @param $productId
     * @param TargetServer $targetServer
     * @return mixed|void
     */
    public function export ($productId, TargetServer $targetServer) {
        
        $dataObjects = $this->getObjectsToExport($productId, "Category", $targetServer);
        $dataObject = $dataObjects->current();
        
        if($dataObject instanceof Category){
            $result = $this->createOrUpdateCategory($dataObject, $targetServer);
            Logger::debug("UPDATED CATEGORY: ".$result->__toString());

            $this->setSyncObject($dataObject, $result, $targetServer);
        }
    }
    
    private function createOrUpdateCategory(Category $dataObject, TargetServer $targetServer) {
        $ecommObject = array();
        
        $objectInfo = GeneralUtils::getServerObjectInfo($dataObject, $targetServer);                
        $magentoId = $objectInfo->getObject_id();
        
        if($magentoId == null || empty($magentoId)){
            $this->toEcomm($ecommObject, $dataObject, $targetServer, $dataObject->getClassName(), true);
            Logger::debug("MAGENTO CREATE CATEGORY: ".json_encode($ecommObject));
            
            $result = CategoryAPIManager::createEntity($ecommObject, $targetServer);

        }else{
            //product is new, need to save price
            $this->toEcomm($ecommObject, $dataObject, $targetServer, $dataObject->getClassName());
            Logger::debug("MAGENTO UPDATE CATEGORY: ".json_encode($ecommObject));
            
            $result = CategoryAPIManager::updateEntity($magentoId, $ecommObject, $targetServer);
        }
        
        return $result;
    }

    public function toEcomm (&$ecommObject, $dataObject, TargetServer $targetServer, $classname, bool $isNew = false) {
        
        parent::toEcomm($ecommObject, $dataObject, $targetServer, $classname, $isNew);
        
        $objectInfo = GeneralUtils::getServerObjectInfo($dataObject, $targetServer);  
        $magentoId = $objectInfo->getObject_id();
        
        if($magentoId != null && !empty($magentoId)){
            $ecommObject["id"] = $magentoId;
        }

        $parentCategory = Category::getById($dataObject->getParentId(),true);
        
        if($parentCategory != null){
            $parentObjectInfo = GeneralUtils::getServerObjectInfo($parentCategory, $targetServer);        
            $parentMagentoId = $parentObjectInfo->getObject_id();

            $ecommObject["parent_id"] = ($parentMagentoId != null && !empty($parentMagentoId)) ? $parentMagentoId : "1";
        }

    }
}