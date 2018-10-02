<?php
namespace SintraPimcoreBundle\Services\Mage2;

use Pimcore\Model\DataObject\Category;
use SintraPimcoreBundle\ApiManager\Mage2\CategoryAPIManager;
use Pimcore\Logger;
use SintraPimcoreBundle\Services\InterfaceService;
use Pimcore\Model\DataObject\TargetServer;
use SintraPimcoreBundle\Utils\GeneralUtils;

class Mage2CategoryService extends BaseMagento2Service implements InterfaceService {
    private $configFile = __DIR__ . '/../config/category.json';

    /**
     * @param $productId
     * @param TargetServer $targetServer
     * @return mixed|void
     */
    public function export ($productId, TargetServer $targetServer) {
        $magento2Category = json_decode(file_get_contents($this->configFile), true)[$targetServer->getKey()];
        
        $dataObjects = $this->getObjectsToExport($productId, "Category");
        
        /** @var Product $dataObject */
        $dataObject = $dataObjects->current();
        
        $objectInfo = GeneralUtils::getServerObjectInfo($dataObject, $targetServer);                
        $magentoId = $objectInfo->getObject_id();
        
        if($magentoId == null || empty($magentoId)){
            $this->toEcomm($magento2Category, $dataObjects, $targetServer, $dataObject->getClassName(), true);
            Logger::debug("MAGENTO CR CATEGORY: ".json_encode($magento2Category));
            
            $result = CategoryAPIManager::createEntity($magento2Category, $targetServer);

        }else{
            //product is new, need to save price
            $this->toEcomm($magento2Category, $dataObjects, $targetServer, $dataObject->getClassName(), true);
            Logger::debug("MAGENTO CR CATEGORY: ".json_encode($magento2Category));
            
            $result = CategoryAPIManager::updateEntity($magentoId, $magento2Category, $targetServer);
        }
        
        Logger::debug("UPDATED CATEGORY: ".$result->__toString());

        $this->setSyncObject($dataObject, $result, $targetServer);
    }

    public function toEcomm (&$ecommObject, $dataObjects, TargetServer $targetServer, $classname, bool $update = false) {
        
        $dataObject = $dataObjects->getObjects()[0];
        
        parent::toEcomm($ecommObject, $dataObjects, $targetServer, $classname, $update);
        
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