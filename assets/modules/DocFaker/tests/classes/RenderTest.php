<?php
require_once '../../src/classes/Render.php';
class RenderTest extends PHPUnit_Framework_TestCase {

    public function testRenderParseTemplate()
    {
        $render = new \DocFaker\Render();
        $params = array(
            'directive' => 'ng-repeat',
            'app' => '<p></p>'
        );
        $app_template = '<div ng-app=\"docfaker\" [+directive+]>[+app+]</div>';
        $app_rendered = '<div ng-app=\"docfaker\" ng-repeat><p></p></div>';
        $filename = '../fixtures/app_template_'.time();
        file_put_contents($filename, $app_template);
        $this->assertEquals($app_rendered, $render->run($filename, $params));
        unlink($filename);;
    }
}
