msYmlImport.panel.Home = function (config) {
    config = config || {};
    Ext.apply(config, {
        baseCls: 'modx-formpanel',
        layout: 'anchor',
        /*
         stateful: true,
         stateId: 'msymlimport-panel-home',
         stateEvents: ['tabchange'],
         getState:function() {return {activeTab:this.items.indexOf(this.getActiveTab())};},
         */
        hideMode: 'offsets',
        items: [{
            html: '<h2>' + _('msymlimport') + '</h2>',
            cls: '',
            style: {margin: '15px 0'}
        }, {
            xtype: 'modx-tabs',
            defaults: {border: false, autoHeight: true},
            border: true,
            hideMode: 'offsets',
            items: [{
                title: _('msymlimport_items'),
                layout: 'anchor',
                items: [{
                    html: _('msymlimport_intro_msg'),
                    cls: 'panel-desc',
                }, {
                    xtype: 'msymlimport-grid-items',
                    cls: 'main-wrapper',
                }]
            }]
        }]
    });
    msYmlImport.panel.Home.superclass.constructor.call(this, config);
};
Ext.extend(msYmlImport.panel.Home, MODx.Panel);
Ext.reg('msymlimport-panel-home', msYmlImport.panel.Home);
