<?php
namespace SintraPimcoreBundle\Services\Mage2;

use Pimcore\Model\DataObject\Category;
use SintraPimcoreBundle\ApiManager\CategoryAPIManager;
use Pimcore\Logger;
use SintraPimcoreBundle\Services\InterfaceService;

class Mage2CategoryService extends BaseMagento2Service implements InterfaceService {
    private $configFile = __DIR__ . '/../config/category.json';

    public function export ($dataObject) {
        $apiManager = CategoryAPIManager::getInstance();

        $magento2Category = $this->toEcomm($dataObject);

        Logger::debug("MAGENTO CATEGORY: ".json_encode($magento2Category));

        $magentoId = $dataObject->getMagentoid();
        if($magentoId == null || empty($magentoId)){
            $result = $apiManager->createEntity($magento2Category);
            $dataObject->setMagentoid($result["id"]);

        }else{
            $result = $apiManager->updateEntity($magentoId,$magento2Category);
        }

        Logger::debug("UPDATED CATEGORY: ".$result->__toString());

        $dataObject->setMagento_sync(true);
        $dataObject->setMagento_sync_at($result["updatedAt"]);

        try{
            $dataObject->update(true);
        }
        catch (Exception $e){
            Logger::notice($e->getMessage() . PHP_EOL . $e->getTraceAsString());
        }
    }

    public function toEcomm ($dataObject, bool $update = false) {
        $parentCategory = Category::getById($dataObject->getParentId(),true);

        $magento2Category = json_decode(file_get_contents($this->configFile), true)['magento2'];

        $magentoId = $dataObject->magentoid;
        if($magentoId != null && !empty($magentoId)){
            $magento2Category["id"] = $magentoId;
        }else{
            unset($magento2Category["id"]);
        }

        $parentMagentoId = $parentCategory->magentoid;
        $magento2Category["parent_id"] = ($parentMagentoId != null && !empty($parentMagentoId)) ? $parentMagentoId : "1";

        $fieldDefinitions = $dataObject->getClass()->getFieldDefinitions();
        foreach ($fieldDefinitions as $fieldDefinition) {
            $fieldName = $fieldDefinition->getName();

            if($fieldName != "magentoid"){
                $fieldType = $fieldDefinition->getFieldtype();
                $fieldValue = $dataObject->getValueForFieldName($fieldName);

                $this->mapField($magento2Category, $fieldName, $fieldType, $fieldValue, $dataObject->getClassId());
            }

        }

        return $magento2Category;
    }
}