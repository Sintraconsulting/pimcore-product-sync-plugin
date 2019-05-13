

This project is an PIMCore bundle that enable product synchronization on your community edition. 
The plugin support multiple destination (Shopify,Magento2) and is fully extensible and customizable without write code. 


# PIMcore Product Syncronization Plugin
Pimcore plugin created to perform automatic objects synchronization from Pimcore to E-Commerce platforms such as "Magento 2" and "Shopify".
This plugin is originally designed to be used for the Pimcore "Product" class, but it's almost immediate to extend it potentially for any Pimcore class.
The plugin is implemented as a [Pimcore Bundle](https://pimcore.com/docs/5.x/Development_Documentation/Extending_Pimcore/Bundle_Developers_Guide/index.html). The following guide will help you to easily install the plugin in your Pimcore environment. For further details and tips check the [Project Wiki](https://github.com/Sintraconsulting/pimcore-product-sync-plugin/wiki)


## How to Install SintraPimcoreBundle

Once you have [Installed Pimcore](https://github.com/Sintraconsulting/pimcore-product-sync-plugin/wiki/Pimcore-Installation-Best-Practices), add the following dependencies to yours Pimcore _composer.json_ file 
``` json
"require": {
     "...": "...",
     "phpclassic/php-shopify": "v1.0.2",
     "springimport/swagger-magento2-client": "*@dev"
},
```
In order to properly install the **SintraPimcoreBundle**, follow these steps. The execution order is crucial to get a correct installation.

1. Clone the repository in the _src/SintraPimcoreBundle_ directory in your Pimcore installation

1. Bulk import the configuration file that you will find inside the _Exports/_ folder in order to create Classes, FieldCollections and ObjectBricks needed  by the **SintraPimcoreBundle**.<br><br>
Pimcore < v5.6.0 ----> structure_configuration.json<br>
Pimcore >= v5.6.0 ---> structure_configuration_v5.6.json<br><br>
CAUTION:<br>In case that you already have defined a Pimcore class named "Product", export the class configuration in order to re-upload that later. This is necessary because bulk import replace classes definitions if the class name match. <br>
You can achieve this point directly on Pimcore interface selecting:
Settings -> Data Objects -> Bulk Import 

1. Duplicate the _Resources/Ecommerce/BaseEcommerceConfig.php.example_ file and remove the _.example_ extension.
Edit the created _BaseEcommerceConfig.php_ file and set your own information.
In order to override some **SintraPimcoreBundle** functionalities, properly set the custom bundle namespace.
Check how to [Create a New Bundle](https://github.com/Sintraconsulting/pimcore-product-sync-plugin/wiki/Create-a-New-Bundle-(Plugin)) and [How to Customize SintraPimcoreBundle](https://github.com/Sintraconsulting/pimcore-product-sync-plugin/wiki/How-to-Customize-SintraPimcoreBundle).

1. Enable and Install the **SintraPimcoreBundle** by the Pimcore Extensions Manager.
You can achieve this point directly on Pimcore interface selecting:
Tools -> Extensions

<br>Now that you have installed the **SintraPimcoreBundle**, you are free to customize the "Product" class as you like. Keep in mind that the "**sku**" field **is required** by some bundle's functionalities in order to identify a product using a unique key.

Once you have structured your class, you can [Configure a TargetServer for Objects Synchronization](https://github.com/Sintraconsulting/pimcore-product-sync-plugin/wiki/Configure-a-TargetServer-for-Objects-Synchronization).
<br><br>
# NEW FEATURE!!! Export Products Catalogue

We provide an export service that allow you to get a complete json representation of your products catalogue.<br>
This service has been created as a function inside a [Pimcore Controller](https://pimcore.com/docs/5.x/Development_Documentation/MVC/Controller.html); you can find the export signature in the [export_signature.json](https://github.com/Sintraconsulting/pimcore-product-sync-plugin/blob/master/export_signature.json) file.

## Request Input Parameters

The service can be invoked through the route _/sintra_pimcore/api/export_.<br>
The service is paginated, and the following input parameters are managed:

* **timestamp**: it only takes those products that have been modified after the given timestamp
* **offset**: the number of skipped products (default is 0)
* **limit**: the size of the export (default is 100)
* **exportAll**: if 1, export the whole catalogue (ignore offset and limit)

## Output Response

As already thought, the export will produce a physical json file.<br>
There will be also a service response that help developers to manage future calls for the service. Response parameters are:

* **timestamp**: the input timestamp parameter (if given)
* **offset**: the input offset parameter (or the default one)
* **limit**: the input limit parameter (or the default one)
* **productsNumber**: the number of exported products
* **nextPageExists**: tells if another page can be exported (with respect to offset and limit)
* **filename**: name of the physical file that contains the exported products

## NOTES
### Not Managed Field Types (2019-05-13)

Almost all types of Pimcore field are managed in this export service. The following types are the only ones that are not supported:

* Password
* Reverse Many-To-Many Object Relation
* Encrypted Field
* Calculated Value

