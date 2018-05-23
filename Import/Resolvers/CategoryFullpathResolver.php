<?php

namespace SintraPimcoreBundle\Import\Resolvers;

use Pimcore\DataObject\Import\Resolver\AbstractResolver;
use Pimcore\Logger;
use Pimcore\Model\DataObject\Service;
use Pimcore\Model\DataObject\Category;
use Transliterator;

/**
 * Resolve product by Sku
 *
 * @author Marco Guiducci
 */
class CategoryFullpathResolver extends AbstractResolver{

    public function resolve(\stdClass $config, int $parentId, array $rowData){
        $transliterator = Transliterator::createFromRules(
            ':: Any-Latin; :: Latin-ASCII; :: NFD; :: [:Nonspacing Mark:] Remove; :: NFC;', 
            Transliterator::FORWARD
        );
        
        $createOnDemand = $config->resolverSettings->createOnDemand;
        $createParents  = $config->resolverSettings->createParents;

        $fullpath = $rowData[$this->getIdColumn($config)];
        
        $keyParts  = explode('>', $fullpath);
        foreach ($keyParts as $i => $keyPart) {
            $keyParts[$i] = $transliterator->transliterate($keyPart);
        }
        
        $objectKey = array_pop($keyParts);
        $parentPath = implode('/', $keyParts);
        
        if(strpos($parentPath, '/') !== 0){
            $parentPath = '/'.$parentPath;
        }
        
        $category = $this->getCategory($parentPath.'/'.$objectKey);
        
        if(!$category && $createOnDemand){
            $parent = $this->getCategory($parentPath);            
            if (!$parent && $createParents) {
                $parent = Service::createFolderByPath($parentPath);
            }
            
            $category = new Category();
            $category->setKey($objectKey);
            $category->setParent($parent);
            $category->setPublished(1);
            
        }else{
            $parent = $category->getParent();
        }
        
        if (!$parent->isAllowed('create')) {
            throw new \Exception('not allowed to import into folder ' . $parent->getFullPath());
        }

        if (!$category) {
            throw new \Exception('failed to resolve object ' . $fullpath);
        }

        return $category;
        
    }
    
    private function getCategory($path){
        $categories = new Category\Listing();
        $categories->setCondition("CONCAT(o_path,o_key) = ?", $path);
        $categories->setLimit(1);
        
        $categories = $categories->load();

        if($categories){
            $parent = $categories[0];
        }else{
            $parent = null;
        }
        
        return $parent;
    }

}
