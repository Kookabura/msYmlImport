<?php

/**
 * The home manager controller for msYmlImport.
 *
 */
class msYmlImportHomeManagerController extends modExtraManagerController
{
    /** @var msYmlImport $msYmlImport */
    public $msYmlImport;


    /**
     *
     */
    public function initialize()
    {
        $path = $this->modx->getOption('msymlimport_core_path', null,
                $this->modx->getOption('core_path') . 'components/msymlimport/') . 'model/msymlimport/';
        $this->msYmlImport = $this->modx->getService('msymlimport', 'msYmlImport', $path);
        parent::initialize();
    }


    /**
     * @return array
     */
    public function getLanguageTopics()
    {
        return array('msymlimport:default');
    }


    /**
     * @return bool
     */
    public function checkPermissions()
    {
        return true;
    }


    /**
     * @return null|string
     */
    public function getPageTitle()
    {
        return $this->modx->lexicon('msymlimport');
    }


    /**
     * @return void
     */
    public function loadCustomCssJs()
    {
        $this->addCss($this->msYmlImport->config['cssUrl'] . 'mgr/main.css');
        $this->addCss($this->msYmlImport->config['cssUrl'] . 'mgr/bootstrap.buttons.css');
        $this->addJavascript($this->msYmlImport->config['jsUrl'] . 'mgr/msymlimport.js');
        $this->addJavascript($this->msYmlImport->config['jsUrl'] . 'mgr/misc/utils.js');
        $this->addJavascript($this->msYmlImport->config['jsUrl'] . 'mgr/misc/combo.js');
        $this->addJavascript($this->msYmlImport->config['jsUrl'] . 'mgr/widgets/items.grid.js');
        $this->addJavascript($this->msYmlImport->config['jsUrl'] . 'mgr/widgets/items.windows.js');
        $this->addJavascript($this->msYmlImport->config['jsUrl'] . 'mgr/widgets/home.panel.js');
        $this->addJavascript($this->msYmlImport->config['jsUrl'] . 'mgr/sections/home.js');

        $this->addHtml('<script type="text/javascript">
        msYmlImport.config = ' . json_encode($this->msYmlImport->config) . ';
        msYmlImport.config.connector_url = "' . $this->msYmlImport->config['connectorUrl'] . '";
        Ext.onReady(function() {
            MODx.load({ xtype: "msymlimport-page-home"});
        });
        </script>
        ');
    }


    /**
     * @return string
     */
    public function getTemplateFile()
    {
        return $this->msYmlImport->config['templatesPath'] . 'home.tpl';
    }
}