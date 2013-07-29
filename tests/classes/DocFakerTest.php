<?php
require_once '../../src/classes/DocFaker.php';

class DocFakerTest extends PHPUnit_Framework_TestCase
{

    private
        $docfaker,
        $faker,
        $fields,
        $modResource;

    public function setUp()
    {
        $this->fields = array(
            'parent' => 15,
            'pagetitle' => array('format' => 'sentence', 'arguments' => array('nbWords' => 4)),
            'template' => '6'
        );
        $this->modResource = $this->getMock('\DocFaker\modResourceInterface', array('create', 'save'));
        $this->faker = $this->getMock('\DocFaker\FakerInterFace', array('format'));
        $this->docfaker = new \DocFaker\DocFaker($this->faker, $this->modResource);
    }

    public function testMustCallFormatWithEachFieldAsArgument()
    {
        $index = 0;
        foreach ($this->fields as $value) {
            $this->faker->expects($this->at($index))->method('format')->with($value);
            $index++;
        }
        $this->docfaker->create($this->fields);
    }

    public function testFillFields()
    {

        $filled_fields = array(
            'parent' => 15,
            'pagetitle' => 'lorem ipsum',
            'template' => '6'
        );
        $this->faker->expects($this->at(0))->method('format')->will($this->returnValue(15));
        $this->faker->expects($this->at(1))->method('format')->will($this->returnValue('lorem ipsum'));
        $this->faker->expects($this->at(2))->method('format')->will($this->returnValue('6'));
        $this->modResource->expects($this->once())->method('create')->with($filled_fields);
        $this->docfaker->create($this->fields);
    }

     public function testCreateReturnDocIdAfterSave()
     {
         $this->modResource->expects($this->once())->method('save')->will($this->returnValue('10'));
         $this->assertEquals(10, $this->docfaker->create($this->fields));
     }

    public function testCreateThrowExeptionIfArgumentNotArray()
    {
        $this->setExpectedException('InvalidArgumentException');
        $this->docfaker->create('foo');
    }

}
