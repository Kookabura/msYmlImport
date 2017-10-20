<?php
if (file_exists(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.core.php')) {
    /** @noinspection PhpIncludeInspection */
    require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/config.core.php';
}
else {
    require_once dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.core.php';
}
/** @noinspection PhpIncludeInspection */
require_once MODX_CORE_PATH . 'config/' . MODX_CONFIG_KEY . '.inc.php';
/** @noinspection PhpIncludeInspection */
require_once MODX_CONNECTORS_PATH . 'index.php';
/** @var msYmlImport $msYmlImport */
$msYmlImport = $modx->getService('msymlimport', 'msYmlImport', $modx->getOption('msymlimport_core_path', null,
        $modx->getOption('core_path') . 'components/msymlimport/') . 'model/msymlimport/'
);
$modx->lexicon->load('msymlimport:default');

// handle request
$corePath = $modx->getOption('msymlimport_core_path', null, $modx->getOption('core_path') . 'components/msymlimport/');
$path = $modx->getOption('processorsPath', $msYmlImport->config, $corePath . 'processors/');
$modx->getRequest();

/** @var modConnectorRequest $request */
$request = $modx->request;
$request->handleRequest(array(
    'processors_path' => $path,
    'location' => '',
));