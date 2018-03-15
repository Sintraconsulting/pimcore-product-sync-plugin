pimcore.registerNS("pimcore.plugin.Magento2PimcoreBundle");

pimcore.plugin.Magento2PimcoreBundle = Class.create(pimcore.plugin.admin, {
    getClassName: function () {
        return "pimcore.plugin.Magento2PimcoreBundle";
    },

    initialize: function () {
        pimcore.plugin.broker.registerPlugin(this);
    },

    pimcoreReady: function (params, broker) {
        // alert("Magento2PimcoreBundle ready!");
    }
});

var Magento2PimcoreBundlePlugin = new pimcore.plugin.Magento2PimcoreBundle();
