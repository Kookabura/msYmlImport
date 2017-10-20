var msYmlImport = function (config) {
    config = config || {};
    msYmlImport.superclass.constructor.call(this, config);
};
Ext.extend(msYmlImport, Ext.Component, {
    page: {}, window: {}, grid: {}, tree: {}, panel: {}, combo: {}, config: {}, view: {}, utils: {}
});
Ext.reg('msymlimport', msYmlImport);

msYmlImport = new msYmlImport();