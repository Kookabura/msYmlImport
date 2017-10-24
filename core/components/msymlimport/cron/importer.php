<?php
require_once('lib/SimpleDOM.php');

define('MODX_API_MODE', true);
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/config/config.inc.php';
require_once MODX_BASE_PATH . 'index.php';

if (XPDO_CLI_MODE) {
	$file = @$argv[1];
	$update = (bool) !empty($argv[2]);
	$key = @$argv[3];
	$is_debug = (bool) !empty($argv[4]);
	//$delimeter = @$argv[6];
}
else {
	$file = @$_REQUEST['file'];
	$fields = @$_REQUEST['fields'];
	$update = (bool) !empty($_REQUEST['update']);
	$key = @$_REQUEST['key'];
	$is_debug = (bool) !empty($_REQUEST['debug']);
	$delimeter = @$_REQUEST['delimeter'];
}

// Натсройки

$key = 'externalKey';
$is_debug = 0;
$importCats = 1;
$importOffers = 1;
$update = 1;
$archives_num = 3;
$url = 'http://fandeco.ru/media/xml_export/for_slice_products.xml';

// Карта цветов
//include('colors_map.php');
$default_color = 'Неизвестно';
if (!$colors_map) $colors_map = array();


// Загрузка файла с данными
$file = file_get_contents($url);
if ($file === false) {
    $modx->log(modX::LOG_LEVEL_ERROR, "Can't load file from url.");
    exit;
}
//file_put_contents('data/export_'.date('dmY_His').'.xml', $file);

// Очистка архива
$archive = array();
foreach (glob("data/*.xml") as $filename) {
    $archive[filemtime($filename)] = $filename;
}

if (count($archive) > $archives_num) {
    krsort($archive);
    foreach(array_slice($archive, $archives_num) as $time => $path) {
        unlink($path);
    }
}

// Load main services
$modx->setLogTarget(XPDO_CLI_MODE ? 'ECHO' : 'HTML');
$modx->setLogLevel($is_debug ? modX::LOG_LEVEL_INFO : modX::LOG_LEVEL_ERROR);
$modx->getService('error','error.modError');
$modx->getService('msymlimport', 'msymlimport', MODX_CORE_PATH . 'components/msymlimport/model/msymlimport/');
$modx->lexicon->load('minishop2:default');
$modx->lexicon->load('minishop2:manager');

// Time limit
set_time_limit(60000);
$tmp = 'Trying to set time limit = 60000 sec: ';
$tmp .= ini_get('max_execution_time') == 60000 ? 'done' : 'error';
$modx->log(modX::LOG_LEVEL_INFO,  $tmp);

// Check required options
if (empty($key)) {
	$modx->log(modX::LOG_LEVEL_ERROR, 'You must specify the parameter "key". It needed for check for duplicates.');
	exit;
}

// Check file
/*if (empty($file)) {
	$error = 'You must specify an file in the ';
	$error .= XPDO_CLI_MODE ? 'first parameter of console call' : '$_GET["file"] parameter';
	$error .= '!';
	$modx->log(modX::LOG_LEVEL_ERROR, $error);
	exit;
}
elseif (!preg_match('/\.xml$/i', $file)) {
	$modx->log(modX::LOG_LEVEL_ERROR, 'Wrong file extension. File must be an *.xml.');
	exit;
}*/

//$file = str_replace('//', '/', MODX_BASE_PATH . $file);

/*if (!file_exists($file)) {
	$modx->log(modX::LOG_LEVEL_ERROR, 'File not found at '.$file.'.');
	exit;
}*/

$xml = simpledom_load_string($file);
if ($xml === false) {
    $modx->log(modX::LOG_LEVEL_ERROR, 'Wrong file content. There should be an xml.');
    exit;
}
$created_categories = $updated_categories = $created_products = $updated_products =  0;
if($importCats == 1){
	$categories = $xml->sortedXPath('/*/shop/categories/category', '@id');
	foreach ($categories as $category) {
		$modx->error->reset();
		$catId = (int)$category['id'];
        $data = array();
        $parentCategory = $category['parentId'] ? (int)$category['parentId'] : 0;
        if($parentCategory && $parent_link = $modx->getObject('msYmlImportLink', array('externalkey' => $parentCategory))){
            $parent = $parent_link->get('docid');
        } else {
            $parent = 9;
        }

        $data = array(
            'class_key' => 'msCategory',
            'pagetitle' => (string)$category,
            'parent'    => $parent,
            'context_key' => 'web',
            'published' => 1,
			'template' => 3,
			'externalkey' => $catId,
			'alias' => $catId . '-' . (string)$category
        );

		$q = $modx->newQuery('modResource', 'Resource');
		$q->select('modResource.id');
		$q->innerJoin('msYmlImportLink', 'ymlLink', "ymlLink.docid = modResource.id AND ymlLink.externalkey = " . $catId);
		//$q->prepare();
		//$modx->log(1, $q->toSql());
		if ($doc = $modx->getObject('modResource', $q)) {
			$data['id'] = $doc->id;
		}
        $action = array_key_exists('id', $data) ? 'update' : 'create';
        $response = $modx->runProcessor('resource/'.$action, $data);
        if ($response->isError()) {
			$errors = $response->getAllErrors();
			$errors['data'] = $data;
            $modx->log(modX::LOG_LEVEL_ERROR, "Error on $action: \n". print_r($errors, 1));
        }else{
			if ($action == 'update') {$updated_categories ++;}
            else {$created_categories ++;}
            $resource = $response->getObject();
			if ($action == 'create') {
	            $cat_link = $modx->newObject('msYmlImportLink', array(
	                'docid' => $resource['id'],
	                'externalkey' => $catId
	            ));
	            $cat_link->save();
			}
        }
	}
}

if($importOffers == 1){
    $i = 0;
    foreach($xml->shop->offers->offer as $offer){
        $modx->error->reset();
        $data = array();
		$catId = (int)$offer->categoryId;

        if ($parent_link = $modx->getObject('msYmlImportLink', array('externalkey' => $catId))) {
            $data['parent'] = $parent_link->get('docid');
        }
        else {
			$modx->log(modX::LOG_LEVEL_ERROR, 'No categoryId for product ' . $offer['id'] . ' This product is skipped');
            continue;
        }

		if (!$vendor = $modx->getObject('msVendor', array('name' => (string)$offer->vendor))) {
			$vendor = $modx->newObject('msVendor', array(
				'name' => (string)$offer->vendor
			));
			$vendor->save();
		}

        $i++;

        $data['class_key'] = 'msProduct';
        $data['context_key'] = 'web';
        $data['made_in'] = (string)$offer->country_of_origin;
        $data['pagetitle'] = (string)$offer->name;
        $data['image'] = (string)$offer->picture;
        $data['price'] = (float)$offer->price;
        $data['vendor'] = $vendor->get('id');
        $data['published'] = 1;
		$data['template'] = 4;
        $data['content'] = (string)$offer->description;
		$data['in_stock'] = (boolean)$offer['available'];
		$data['article'] = (string)$offer->vendorCode;
		$data['alias'] = $offer['id'] . '_' . (string)$offer->name;

        foreach ($offer->param as $param) {
            switch ($val = (string)$param['name']) {
                case "Длина":
                    $data['length'] = (float)$param;
                    break;
                case "Ширина":
                    $data['width'] = (float)$param;
                    break;
                case "Высота":
                    $data['height'] = (float)$param;
                    break;
                case "Цвет арматуры":
                    $data['armature_color'] = explode(',',(string)$param);
                    break;
                case "Материал арматуры":
                    $data['armature_material'] = explode(',',(string)$param);
                    break;
                case "Световой поток":
                    $data['light_flow'] = (float)$param;
                    break;
				case "Место монтажа":
					$data['installation_place'] = (string)$param;
					break;
				case "Тип лампы":
					$data['bulb_type'] = (string)$param;
					break;
				case "Патрон":
					$data['cartridge'] = (string)$param;
					break;
				case "Высота коробки":
					$data['box_height'] = (float)$param;
					break;
				case "IP":
					$data['ip'] = (float)$param;
					break;
				case "Цвет плафона":
					$data['plafond_color'] = explode(',', (string)$param);
					break;
				case "Диаметр врезки":
					$data['mortise_diameter'] = (float)$param;
					break;
				case "Материал плафона / декора":
					$data['plafond_material'] = (string)$param;
					break;
				case "Цвет":
					$data['color'] = explode(',',(string)$param);
					break;
				case "Длина коробки":
					$data['box_length'] = (float)$param;
					break;
				case "Минимальное количество в упаковке":
					$data['min_quantity_in_pack'] = (int)$param;
					break;
				case "Группа товара АВС":
					$data['abc_product_group'] = (string)$param;
					break;
				case "Мощность":
					$data['power'] = (float)$param;
					break;
				case "Страна происхождения бренда":
					$data['brand_country'] = (string)$param;
					break;
				case "Возможные типы ламп":
					$data['allowed_bulb_types'] = (string)$param;
					break;
				case "Длина шнура":
					$data['cord_length'] = (float)$param;
					break;
				case "Напряжение":
					$data['voltage'] = (float)$param;
					break;
				case "Выключатель":
					$data['switcher'] = (string)$param;
					break;
				case "Колекция":
					$data['collection'] = (string)$param;
					break;
				case "Форма":
					$data['form'] = (string)$param;
					break;
				case "Количество ламп":
					$data['bulb_amount'] = (int)$param;
					break;
				case "Количество патронов":
					$data['cartridge_amount'] = (int)$param;
					break;
				case "Крепеж":
					$data['fasten'] = (string)$param;
					break;
				case "Требует Сертификации":
					$data['cert_required'] = (boolean)$param;
					break;
				case "Стиль Светильника":
					$data['style'] = (string)$param;
					break;
				case "Ширина коробки":
					$data['box_width'] = (float)$param;
					break;
				case "Площадь освещения":
					$data['lightening_square'] = (float)$param;
					break;
				case "Интерьер по комнате":
					$data['interior'] = (string)$param;
					break;
				case "Диаметр":
					$data['diameter'] = (float)$param;
					break;
            }
        }

        $modx->log(modX::LOG_LEVEL_INFO, "Array with importing data: \n" . print_r($data, 1));

        /** @var modResource $exists */
		$q = $modx->newQuery('modResource', 'Resource');
		$q->select('modResource.id');
		$q->innerJoin('msYmlImportLink', 'ymlLink', 'ymlLink.docid = modResource.id AND ymlLink.externalkey = ' . $offer['id']);
		//$q->prepare();
		//$modx->log(1, $q->toSql());
		if ($doc = $modx->getObject('modResource', $q)) {
			$data['id'] = $doc->id;
		}
        $action = array_key_exists('id', $data) ? 'update' : 'create';

        // Create or update resource(string)
        /** @var modProcessorResponse $response */
        $response = $modx->runProcessor('resource/'.$action, $data);
        if ($response->isError()) {
			$errors = $response->getAllErrors();
			$errors['data'] = $data;
            $modx->log(modX::LOG_LEVEL_ERROR, "Error on $action: \n". print_r($errors, 1));
        }
        else {
            if ($action == 'update') {$updated_products ++;}
            else {$created_products ++;}

            $resource = $response->getObject();
			if ($action == 'create') {
				$cat_link = $modx->newObject('msYmlImportLink', array(
	                'docid' => $resource['id'],
	                'externalkey' => $offer['id']
	            ));
	            $cat_link->save();
			}
            $modx->log(modX::LOG_LEVEL_INFO, "Successful $action: \n". print_r($resource, 1));

            // Process gallery images, if exists
            if($data['image']){
                $modx->error->reset();
                $img = file_get_contents($data['image']);
                $path = $modx->msymlimport->config['assetsUrl'] . "cache/image.jpg";
                $fp = fopen(MODX_BASE_PATH.$path, "w");
                fwrite($fp, $img);
                fclose($fp);
                $gallery = array($path);
                if (!empty($gallery)) {
                    $modx->log(modX::LOG_LEVEL_INFO, "Importing images: \n". print_r($gallery, 1));
                    foreach ($gallery as $v) {
                        if (empty($v)) {continue;}
                        $image = str_replace('//', '/', MODX_BASE_PATH . $v);
                        if (!file_exists($image)) {
                            $modx->log(modX::LOG_LEVEL_ERROR, "Could not import image \"$v\" to gallery. File \"$image\" not found on server.");
                        }
                        else {
                            $response = $modx->runProcessor('gallery/upload',
                                array('id' => $resource['id'], 'name' => $v, 'file' => $image),
                                array('processors_path' => MODX_CORE_PATH.'components/minishop2/processors/mgr/')
                            );
                            if ($response->isError()) {
                                $modx->log(modX::LOG_LEVEL_ERROR, "Error on upload \"$v\": \n". print_r($response->getAllErrors(), 1));
                            }
                            else {
                                $modx->log(modX::LOG_LEVEL_INFO, "Successful upload  \"$v\": \n". print_r($response->getObject(), 1));
                            }
                            unlink($image);
                        }
                    }
                }
            }

        }

        if ($is_debug && $i == 20) {
            $modx->log(modX::LOG_LEVEL_INFO, 'You in debug mode, so we process only 20 offers. Time: '.number_format(microtime(true) - $modx->startTime, 7) . " s");
            break;
        }
    }
}


if (!XPDO_CLI_MODE) {echo '<pre>';}
echo "\nImport complete in ".number_format(microtime(true) - $modx->startTime, 7) . " s\n";
//echo "\nTotal rows:	$rows\n";
echo "Created categories:	$created_categories\n";
echo "Updated categories:	$updated_categories\n";
echo "Created products:	$created_products\n";
echo "Updated products:	$updated_products\n";
if (!XPDO_CLI_MODE) {echo '</pre>';}
