pimcore.registerNS("pimcore.plugin.SintraPimcoreBundle");

pimcore.plugin.SintraPimcoreBundle = Class.create(pimcore.plugin.admin, {
    getClassName: function () {
        return "pimcore.plugin.SintraPimcoreBundle";
    },

    initialize: function () {
        pimcore.plugin.broker.registerPlugin(this);
    },

    pimcoreReady: function (params, broker) {
        // alert("SintraPimcoreBundle ready!");
    }
});

var SintraPimcoreBundlePlugin = new pimcore.plugin.SintraPimcoreBundle();
