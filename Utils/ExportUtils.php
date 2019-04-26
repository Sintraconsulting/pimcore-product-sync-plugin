<?php

namespace SintraPimcoreBundle\Utils;

use Pimcore\Logger;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Image;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Block;
use Pimcore\Model\DataObject\ClassDefinition\Data\BooleanSelect;
use Pimcore\Model\DataObject\ClassDefinition\Data\Country;
use Pimcore\Model\DataObject\ClassDefinition\Data\Countrymultiselect;
use Pimcore\Model\DataObject\ClassDefinition\Data\Language;
use Pimcore\Model\DataObject\ClassDefinition\Data\Languagemultiselect;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\ClassDefinition\Data\Multiselect;
use Pimcore\Model\DataObject\ClassDefinition\Data\Select;
use Pimcore\Model\DataObject\ClassDefinition\Data\TargetGroup;
use Pimcore\Model\DataObject\ClassDefinition\Data\TargetGroupMultiselect;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\BlockElement;
use Pimcore\Model\DataObject\Data\Consent;
use Pimcore\Model\DataObject\Data\ExternalImage;
use Pimcore\Model\DataObject\Data\Geopoint;
use Pimcore\Model\DataObject\Data\Geobounds;
use Pimcore\Model\DataObject\Data\Hotspotimage;
use Pimcore\Model\DataObject\Data\ImageGallery;
use Pimcore\Model\DataObject\Data\Link;
use Pimcore\Model\DataObject\Data\ObjectMetadata;
use Pimcore\Model\DataObject\Data\QuantityValue;
use Pimcore\Model\DataObject\Data\RgbaColor;
use Pimcore\Model\DataObject\Data\StructuredTable;
use Pimcore\Model\DataObject\Data\Video;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData as FieldcollectionAbstractData;
use Pimcore\Model\DataObject\Fieldcollection\Data\ServerObjectInfo;
use Pimcore\Model\DataObject\Localizedfield;
use Pimcore\Model\DataObject\Objectbrick;
use Pimcore\Model\DataObject\Objectbrick\Data\AbstractData as ObjectbrickAbstractData;
use Pimcore\Model\DataObject\Product;
use Pimcore\Model\DataObject\TargetServer;
use Pimcore\Model\Tool\Targeting;
use Pimcore\Tool;
use SintraPimcoreBundle\Resources\Ecommerce\BaseEcommerceConfig;


/**
 * Export utils
 *
 * @author Sintra Consulting
 */
class ExportUtils {

    private static $simplePhpTypes = array("string", "int", "float", "double", "boolean");
    
    private static $simplePimcoreTypes = array("input", "textarea", "numeric", "slider", "checkbox");

    public static function getSimplePhpTypes() {
        return self::$simplePhpTypes;
    }
    
    public static function getSimplePimcoreTypes() {
        return self::$simplePimcoreTypes;
    }

    public static function exportProduct(&$response, Product $product) {

        $productId = $product->getId();
        
        $productExport = array(
            "id" => $productId,
            "created at" => date("Y-m-d H:i:s", $product->getCreationDate()),
            "modified at" => date("Y-m-d H:i:s", $product->getModificationDate())
        );

        $productClassDefinition = ClassDefinition::getByName("Product");

        $productFields = $productClassDefinition->getFieldDefinitions();

        foreach ($productFields as $fieldDefinition) {
            self::exportObjectField($productId, $product, $fieldDefinition, $productExport);
        }
        
        $variants = $product->getChildren(array(AbstractObject::OBJECT_TYPE_VARIANT));
        foreach ($variants as $variant) {
            self::exportProduct($productExport["variants"], $variant);
        }

        $response[] = $productExport;
    }

    /**
     * 
     * @param int $productId
     * @param Concrete|FieldcollectionAbstractData|ObjectbrickAbstractData|BlockElement $object
     * @param Data $fieldDefinition
     * @param array $objectExport
     */
    private static function exportObjectField(int $productId, $object, Data $fieldDefinition, array &$objectExport, int $level = 0) {
        $objectReflection = new \ReflectionObject($object);
        
        $fieldName = $fieldDefinition->getName();

        $getterMethod = (!($object instanceof BlockElement)) ? $objectReflection->getMethod("get" . ucfirst($fieldName)) : $objectReflection->getMethod("getData");
        $fieldValue = $getterMethod->invoke($object);

        $fieldType = $fieldDefinition->getFieldtype();

        $objectExport[$fieldName] = self::exportFieldValue($productId, $fieldDefinition, $fieldType, $fieldValue, $level);
    }
    
    /**
     * 
     * @param int $productId
     * @param Data|array $fieldDefinition
     * @param type $fieldType
     * @param type $fieldValue
     * @param int $level
     * @return type
     */
    private static function exportFieldValue(int $productId, $fieldDefinition, $fieldType, $fieldValue, int $level){
        $value = null;
        
        switch ($fieldType) {
            case "wysiwyg":
                $value = htmlentities($fieldValue);
                break;
            
            case "quantityValue":
            case "inputQuantityValue":
                $value = self::exportQuantityValueField($fieldValue);
                break;

            case "date":
                $value = date("Y-m-d", strtotime($fieldValue));
                break;
            
            case "datetime":
                $value = date("Y-m-d H:i:s", strtotime($fieldValue));
                break;
            
            case "link":
                $value = self::exportLinkField($fieldValue);
                break;
            
            case "rgbaColor":
                $value = self::exportRgbaColorField($fieldValue);
                break;

            /**
             * SELECT AND MULTISELECT FIELDS
             */
            case "booleanSelect":
            case "select":
            case "multiselect":
            case "country":
            case "countrymultiselect":
            case "language":
            case "languagemultiselect":
                $value = self::exportSelectField($fieldValue, $fieldDefinition);
                break;
            
            /**
             * CRM SPECIAL FIELDS
             */
            case "targetGroup":
            case "targetGroupMultiselect":
                $value = self::exportTargetGroupSelectField($fieldValue, $fieldDefinition);
                break;
            
            case "consent":
                $value = self::exportConsentField($fieldValue);
                break;

            /**
             * OBJECTS RELATION FIELDS
             */
            case "manyToOneRelation":
                $value = self::exportRelationField($productId, $fieldValue, $level);
                break;

            case "manyToManyObjectRelation":
            case "manyToManyRelation":
                $value = self::exportMultipleRelationsField($productId, $fieldValue, $level);
                break;
            
            case "advancedManyToManyObjectRelation":
            case "advancedManyToManyRelation":
                $value = self::exportAdvancedMultipleRelationsField($productId, $fieldValue, $level);
                break;
            
            /**
             * GEOGRAPHICAL FIELDS
             */
            case "geopoint":
                $value = self::exportGeopointField($fieldValue);
                break;
            
            case "geobounds":
                $value = self::exportGeoboundsField($fieldValue);
                break;
            
            case "geopolygon":
                $value = self::exportGeopolygonField($fieldValue);
                break;
            
            /**
             * STRUCTURED FIELDS
             */
            
            case "block":
                $value = self::exportBlock($productId, $fieldDefinition, $level, $fieldValue);
                break;
            
            case "localizedfields":
                $value = self::exportLocalizedField($fieldValue, $fieldDefinition);
                break;

            case "fieldcollections":
                $value = self::exportFieldcollection($productId, $level, $fieldValue);
                break;
            
            case "objectbricks":
                $value = self::exportObjectBrick($productId, $level, $fieldValue);
                break;
            
            case "table":
                $value = $fieldValue;
                break;
            
            case "structuredTable":
                $value = self::exportStructuredTable($fieldValue);
                break;
            
            case "classificationstore":
                $value = self::exportClassificationStore($productId, $fieldValue, $fieldDefinition, $level);
                break;
            
            /**
             * ASSETS FIELDS
             */
            case "image":
                $value = self::exportImageField($fieldValue);
                break;
            
            case "externalImage":
                $value = self::exportExternalImageField($fieldValue);
                break;
            
            case "hotspotimage":
                $value = self::exportHotspotImageField($fieldValue);
                break;
            
            case "imageGallery":
                $value = self::exportImageGalleryField($fieldValue);
                break;
            
            case "video":
                $value = self::exportVideoField($fieldValue);
                break;

            default:
                $realType = ($fieldDefinition instanceof Data) ? $fieldDefinition->getPhpdocType() : $fieldDefinition["fieldtype"];

                if (in_array($realType, array_merge(self::getSimplePhpTypes(),self::getSimplePimcoreTypes()))) {
                    $value = $fieldValue;
                } else {
                    Logger::warn("WARNING - exportFieldValue - Field type '$fieldType' not supported for export");
                }

                break;
        }
        
        return $value;
    }

    private static function exportQuantityValueField(QuantityValue $fieldValue){
        return array(
            "value" => $fieldValue->getValue(),
            "unit" => $fieldValue->getUnit()->getAbbreviation()
        );
    }
    
    private static function exportLinkField(Link $fieldValue){
        return array(
            "text" => $fieldValue->getText(),
            "title" => $fieldValue->getTitle(),
            "path" => $fieldValue->getPath(),
            "parameters" => $fieldValue->getParameters(),
            "anchor" => $fieldValue->getAnchor(),
            "href" => $fieldValue->getHref(),
            "target" => $fieldValue->getTarget(),
        );
    }

    private static function exportRgbaColorField(RgbaColor $fieldValue){
        return array(
            "rgb" => $fieldValue->getRgb(),
            "rgba" => $fieldValue->getRgba(),
            "hex" => $fieldValue->getHex(false, true),
            "hexa" => $fieldValue->getHex(true, true),
        );
    }
    
    //SELECT AND MULTISELECT FIELDS
    
    /**
     * 
     * @param type $fieldValue
     * @param Data|array $fieldDefinition
     * @return type
     * @throws \Exception
     */
    private static function exportSelectField($fieldValue, $fieldDefinition){
        if(!(is_array($fieldDefinition)) && !($fieldDefinition instanceof Select || $fieldDefinition instanceof Multiselect || $fieldDefinition instanceof BooleanSelect
                || $fieldDefinition instanceof Country || $fieldDefinition instanceof Countrymultiselect 
                || $fieldDefinition instanceof Language || $fieldDefinition instanceof Languagemultiselect)){
            throw new \Exception("ERROR - exportSelectField - Invalid type '".$fieldDefinition->getFieldtype().
                    "'. Expected one between 'booleanSelect','select', 'multiselect', 'country', 'countrymultiselect', 'language' and 'languagemultiselect'");
        }
        
        if(is_array($fieldDefinition)){
            $fieldName = $fieldDefinition["name"];
            
            if(array_key_exists("options", $fieldDefinition) && sizeof($fieldDefinition["options"]) > 0){
                $options = $fieldDefinition["options"];
            }else{
                $options = self::getSelectOptionsForClassificationStore($fieldDefinition);
            }
        }else{
            $fieldName = $fieldDefinition->getName();
            $options = $fieldDefinition->getOptions();
        }
        
        if(is_array($fieldValue)){
            $values = array();
            
            foreach ($fieldValue as $value) {
                $option = array_search($value, array_column($options, "value"));
                if($option !== FALSE){
                    $values[] = $options[$option];
                }else{
                    Logger::warn("WARNING - exportSelectField - Invalid field value '$value' for '".$fieldName."'");
                }
            }
        }else{
            $option = array_search($fieldValue, array_column($options, "value"));
            if($option !== FALSE){
                $values = $options[$option];
            }else{
                Logger::warn("WARNING - exportSelectField - Invalid field value '$fieldValue' for '".$fieldName."'");
            }
        }
        
        return $values;
    }
    
    private static function getSelectOptionsForClassificationStore(array $fieldDefinition){
        $options = array();
        
        $fieldType = $fieldDefinition["fieldtype"];
        
        if(in_array($fieldType, array('country', 'countrymultiselect'))){
            $countries = \Pimcore::getContainer()->get('pimcore.locale')->getDisplayRegions();
            asort($countries);

            foreach ($countries as $short => $translation) {
                if (strlen($short) == 2) {
                    $options[] = [
                        'key' => $translation,
                        'value' => $short
                    ];
                }
            }
        }
        
        if(in_array($fieldType, array('language', 'languagemultiselect'))){
            $validLanguages = (array) Tool::getValidLanguages();
            $locales = Tool::getSupportedLocales();

            foreach ($locales as $short => $translation) {
                if ($fieldDefinition["onlySystemLanguages"]) {
                    if (!in_array($short, $validLanguages)) {
                        continue;
                    }
                }

                $options[] = [
                    'key' => $translation,
                    'value' => $short
                ];
            }
        }
        
        if($fieldType === 'booleanSelect'){
            $options = array(
                [
                    'key' => $fieldDefinition["emptyLabel"],
                    'value' => 0
                ],
                [
                    'key' => $fieldDefinition["yesLabel"],
                    'value' => 1
                ],
                [
                    'key' => $fieldDefinition["noLabel"],
                    'value' => -1
                ]
            );
        }
        
        return $options;
    }


    //CRM SPECIAL FIELDS
    
    private static function exportTargetGroupSelectField($fieldValue, Data $fieldDefinition){
        if(!($fieldDefinition instanceof TargetGroup || $fieldDefinition instanceof TargetGroupMultiselect)){
            throw new \Exception("ERROR - exportSelectField - Invalid type '".$fieldDefinition->getFieldtype().
                    "'. Expected one between 'targetGroup' and 'targetGroupMultiselect'");
        }
        
        $values = self::exportSelectField($fieldValue, $fieldDefinition);
        
        if(is_array($fieldValue)){
            foreach ($values as $key => $value) {
                $targetGroup = Targeting\TargetGroup::getById($value["value"]);
                $values[$key]["threshold"] = $targetGroup->getThreshold();
                $values[$key]["description"] = $targetGroup->getDescription();
            }
        }else{
            $targetGroup = Targeting\TargetGroup::getById($values["value"]);
            $values["threshold"] = $targetGroup->getThreshold();
            $values["description"] = $targetGroup->getDescription();
        }
        
        return $values;
    }
    
    private static function exportConsentField(Consent $fieldValue){
        return array(
            "consent" => $fieldValue->getConsent(),
            "note" => $fieldValue->getNote()
        );
    }

    
    //OBECTS RELATION FIELDS
    
    /**
     * 
     * @param int $productId
     * @param Concrete[] $fieldValue
     * @return array
     */
    private static function exportMultipleRelationsField(int $productId, $fieldValue, int $level){
        $relatedObjects = array();
        
        foreach ($fieldValue as $value) {
            $relatedObjects[] = self::exportRelationField($productId, $value, $level);
        }
        
        return $relatedObjects;
    }
    
    /**
     * 
     * @param int $productId
     * @param ObjectMetadata[] $fieldValue
     * @return array
     */
    private static function exportAdvancedMultipleRelationsField(int $productId, $fieldValue, int $level){
        $relatedObjects = array();
        
        foreach ($fieldValue as $value) {
            $relatedObject = self::exportRelationField($productId, $value->getElement(), $level);
            $relatedObject["metadata"] = $value->getData();
            
            $relatedObjects[] = $relatedObject;
        }
        
        return $relatedObjects;
    }
    
    private static function exportRelationField(int $productId, $fieldValue, int $level){
        if($fieldValue instanceof Concrete && !($fieldValue instanceof TargetServer)){
            $relatedObject = array(
                "id" => $fieldValue->getId(),
                "class" => $fieldValue->getClassName(),
                "created at" => date("Y-m-d H:i:s", $fieldValue->getCreationDate()),
                "modified at" => date("Y-m-d H:i:s", $fieldValue->getModificationDate())
            );

            /**
             * Avoid circular dependency loop
             */
            if($level < 10 && ($fieldValue->getId() != $productId || $fieldValue->getClassName() != "Product")){
                $classDefinition = $fieldValue->getClass();

                $fieldDefinitions = $classDefinition->getFieldDefinitions();

                foreach ($fieldDefinitions as $fieldDefinition) {
                    self::exportObjectField($productId, $fieldValue, $fieldDefinition, $relatedObject, $level +1);
                }
            }
        }else{
            Logger::warn("WARNING - exportRelationField - The export is defined only for objects");
        }
        
        return $relatedObject;
    }
    
    //GEOGRAPHICAL FIELDS
    
    private static function exportGeopointField(Geopoint $fieldValue){
        return array(
            "latitude" => $fieldValue->getLatitude(),
            "longitude" => $fieldValue->getLongitude()
        );
    }
    
    private static function exportGeoboundsField(Geobounds $fieldValue){
        return array(
            "northeast" => self::exportGeopointField($fieldValue->getNorthEast()),
            "soutwest" => self::exportGeopointField($fieldValue->getSouthWest())
        );
    }
    
    /**
     * 
     * @param Geopoint[] $fieldValue
     */
    private static function exportGeopolygonField($fieldValue){
        $points = array();
        
        foreach ($fieldValue as $value) {
            $points[] = self::exportGeopointField($value);
        }
        
        return $points;
    }


    //STRUCTURED FIELDS
    
    /**
     * 
     * @param int $productId
     * @param Data $fieldDefinition
     * @param int $level
     * @param BlockElement[][] $fieldValue
     * @return array
     */
    private static function exportBlock(int $productId, Data $fieldDefinition, int $level, $fieldValue){
        if(!($fieldDefinition instanceof Block)){
            throw new \Exception("ERROR - exportBlockField - Invalid type '".$fieldDefinition->getFieldtype()."'. Expected 'block'");
        }
        
        $blockElements = array();
        
        foreach ($fieldValue as $blockElement) {
            
            $block = array();

            foreach ($blockElement as $blockField) {
                self::exportBlockField($productId, $blockField, $fieldDefinition, $block, $level + 1);
            }

            $blockElements[] = $block;
        }
        
        return $blockElements;
    }
    
    private static function exportBlockField(int $productId, BlockElement $blockField, Block $fieldDefinition, array &$block, $level){
        $blockFields = $fieldDefinition->getFieldDefinitions();
        
        foreach ($blockFields as $field) {
            if($field->getName() == $blockField->getName()){
                self::exportObjectField($productId, $blockField, $field, $block, $level);
            }
        }
    }
    
    private static function exportLocalizedField(Localizedfield $fieldValue, Data $fieldDefinition){
        if(!($fieldDefinition instanceof Localizedfields)){
            throw new \Exception("ERROR - exportLocalizedField - Invalid type '".$fieldDefinition->getFieldtype()."'. Expected 'localizedfields'");
        }
        
        $fields = $fieldDefinition->getChildren();
        
        $localizedValues = array();
        
        $config = \Pimcore\Config::getSystemConfig();
        $validLanguages = explode(",",$config->general->validLanguages);
        
        foreach ($fields as $field) {
            $fieldName = $field->getName();
            
            foreach ($validLanguages as $lang) {
                $localizedValues[$fieldName][$lang] = $fieldValue->getLocalizedValue($fieldName, $lang);
            }
        }
        
        return $localizedValues;
    }
    
    private static function exportFieldcollection(int $productId, $level, Fieldcollection $fieldValue = null){
        $items = $fieldValue != null ? $fieldValue->getItems() : array();
        
        $fieldCollections = array();
        
        foreach ($items as $item) {
            /**
             * Avoid circular dependency loop
             */
            if($level < 10 && $item instanceof FieldcollectionAbstractData && !($item instanceof ServerObjectInfo)){
                $fieldCollection = array();
                
                $definition = $item->getDefinition();
                foreach ($definition->getFieldDefinitions() as $fieldDefinition) {
                    self::exportObjectField($productId, $item, $fieldDefinition, $fieldCollection, $level + 1);
                }
                
                $fieldCollections[] = $fieldCollection;
            }
        }
        
        return $fieldCollections;
    }
    
     private static function exportObjectbrick(int $productId, $level, Objectbrick $fieldValue = null){
        $items = $fieldValue != null ? $fieldValue->getItems() : array();
        
        $objectBricks = array();
        
        foreach ($items as $item) {
            /**
             * Avoid circular dependency loop
             */
            if($level < 10 && $item instanceof ObjectbrickAbstractData ){
                $objectBrick = array();
                
                $definition = $item->getDefinition();
                foreach ($definition->getFieldDefinitions() as $fieldDefinition) {
                    self::exportObjectField($productId, $item, $fieldDefinition, $objectBrick, $level + 1);
                }
                
                $objectBricks[] = $objectBrick;
            }
        }
        
        return $objectBricks;
    }
    
    private static function exportStructuredTable(StructuredTable $fieldValue){
        return $fieldValue->getData();
    }
    
    private static function exportClassificationStore($productId, Classificationstore $fieldValue, Data\Classificationstore $fieldDefinition, $level){
        $classificationStore = array();
        
        $items = $fieldValue->getItems();
            
        foreach ($items as $groupId => $group) {
            $groupConfig = Classificationstore\GroupConfig::getById($groupId);

            $groupname = $groupConfig->getName();

            $classificationStore[$groupname] = array();

            foreach ($group as $keyId => $key) {
                $keyConfig = Classificationstore\KeyConfig::getById($keyId);
                
                if($keyConfig->getEnabled()){
                    $keyName = $keyConfig->getName();

                    Logger::info("KEY DEFINITION: ".$keyConfig->getDefinition());

                    $classificationStore[$groupname][$keyName] = array();

                    foreach ($key as $lang => $value) {
                        $classificationStore[$groupname][$keyName][$lang] = self::exportFieldValue($productId, json_decode($keyConfig->getDefinition(),true), $keyConfig->getType(), $value, $level);
                    }
                }
            }
        }
        
        return $classificationStore;
    }

    //ASSETS FIELDS
    
    private static function exportImageField(Image $fieldValue){
        return array(
            "url" => BaseEcommerceConfig::getBaseUrl().urlencode_ignore_slash($fieldValue->getRelativeFileSystemPath())
        );
    }
    
    private static function exportExternalImageField(ExternalImage $fieldValue){
        return array(
            "url" => $fieldValue->getUrl()
        );
    }
    
    public static function exportHotspotImageField(Hotspotimage $fieldValue){
        $image = $fieldValue->getImage();
        
        return array(
            "url" => BaseEcommerceConfig::getBaseUrl().urlencode_ignore_slash($image->getRelativeFileSystemPath()),
            "crop" => $fieldValue->getCrop(),
            "hotspots" => $fieldValue->getHotspots(),
            "marker" => $fieldValue->getMarker()
        );
    }
    
    public static function exportImageGalleryField(ImageGallery $fieldValue){
        $images = array();
        
        foreach ($fieldValue->getItems() as $image) {
            $images[] = self::exportHotspotImageField($image);
        }
        
        return $images;
    }
    
    public static function exportVideoField(Video $fieldValue){
        $type = $fieldValue->getType();
        
        $data = $fieldValue->getData();
        
        $video = array(
            "type" => $type
        );
        
        if($data instanceof Asset){ 
            $video["url"] = BaseEcommerceConfig::getBaseUrl()."/var/assets".$data->getFullPath();
            $video["title"] = $fieldValue->getTitle();
            $video["description"] = $fieldValue->getDescription();
        }else{
            $video["id"] = $data;
        }
        
        return $video;
    }
}
