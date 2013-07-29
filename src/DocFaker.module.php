<?php
$module_path = MODX_BASE_PATH . 'assets/modules/DocFaker/';
require_once $module_path.'libs/resourse-MODxAPI/modResource.php';
require_once $module_path.'libs/Faker/src/autoload.php';
require_once 'autoload.php';

$modResource = new \DocFaker\modResource($modx);
$generator = \Faker\Factory::create('ru_RU');
$documentor = new Faker\Documentor($generator);
$fakerAdapter = new \DocFaker\FakerAdapter($documentor, $generator);
$render = new \DocFaker\Render();
$templates_config = new \DocFaker\TemplatesConfig($modx);
$docfaker = new \DocFaker\DocFaker($fakerAdapter, $modResource);
$app_tpl = $module_path.'js/app/index.html';
$action = (isset($_REQUEST['action'])) ? $_REQUEST['action'] : '';
switch ($action) {
    case 'get_config':
        header('Content-type: application/json');
        $params['templates'] = $templates_config->load();
        $params['formatters'] = $fakerAdapter->getFormatters();
        $output = json_encode($params);
        break;
    case 'save_config':
        $config = file_get_contents('php://input');
        $output = $templates_config->save($config) ? '' : header('HTTP/1.1 500 Internal Server Error');
        break;
    case 'create_node':
        header('Content-type: application/json');
        $doc = file_get_contents('php://input');
        $doc = json_decode($doc, true);
        $amount = (int)$doc['amount'];
        $fields = $doc['fields'];
        try{
        if ($doc['amount'] > 0) {
            $parents = array();
            for ($i = 0; $i < (int)$amount; $i++) {

                if (!($parent = $docfaker->create($fields))){
                    throw new Exception('cant\' create doc');
                }
                $parents[] = $parent;
            }
        };
        $output = json_encode($parents);
        }
        catch (Exception $e){
            header('HTTP/1.1 500 Internal Server Error');
            $output = $e->getMessage();
        }
        break;
    default:
        $params = array();
        $output = $render->run($app_tpl, $params);
        break;
}
echo $output;
?>