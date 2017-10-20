msYmlImport.page.Home = function (config) {
    config = config || {};
    Ext.applyIf(config, {
        components: [{
            xtype: 'msymlimport-panel-home',
            renderTo: 'msymlimport-panel-home-div'
        }]
    });
    msYmlImport.page.Home.superclass.constructor.call(this, config);
};
Ext.extend(msYmlImport.page.Home, MODx.Component);
Ext.reg('msymlimport-page-home', msYmlImport.page.Home);