<?php
/** @var modX $modx */
/** @var array $sources */

$settings = array();

$tmp = array(
    'msyi.file_url' => array(
        'xtype' => 'textfield',
        'value' => '',
        'area' => 'msymlimport_main',
    ),
    'msyi.default_parent_category' => array(
        'xtype' => 'textfield',
        'value' => '',
        'area' => 'msymlimport_main',
    ),
    'msyi.import_categories' => array(
        'xtype' => 'combo-boolean',
        'value' => true,
        'area' => 'msymlimport_main',
    ),
    'msyi.import_products' => array(
        'xtype' => 'combo-boolean',
        'value' => true,
        'area' => 'msymlimport_main',
    ),
    'msyi.product_template' => array(
        'xtype' => 'textfield',
        'value' => '',
        'area' => 'msymlimport_main',
    ),
    'msyi.category_template' => array(
        'xtype' => 'textfield',
        'value' => '',
        'area' => 'msymlimport_main',
    ),
);

foreach ($tmp as $k => $v) {
    /** @var modSystemSetting $setting */
    $setting = $modx->newObject('modSystemSetting');
    $setting->fromArray(array_merge(
        array(
            'key' => 'msymlimport_' . $k,
            'namespace' => PKG_NAME_LOWER,
        ), $v
    ), '', true, true);

    $settings[] = $setting;
}
unset($tmp);

return $settings;
