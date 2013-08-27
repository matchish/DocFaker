<?php
require_once '../../src/classes/FakerAdapter.php';

class FakerAdapterTest extends PHPUnit_Framework_TestCase {


    private
        $documentor,
        $generator,
        $faker;

    public function SetUp()
    {
        $this->documentor = $documentor = $this->getMock('\Faker\Documentor');
        $this->generator = $generator = $this->getMock('\Faker\Generator', array('format'));
        $this->faker = $faker = new \DocFaker\FakerAdapter($this->documentor, $this->generator);
    }

    public function testFormat_generateValueByFormatter()
    {
        $formatter = 'sentence';
        $arguments = array('words' => 10);
        $this->generator->expects($this->once())->method('format')->with($formatter,$arguments);
        $this->faker->format($formatter, $arguments);
    }

    public function testFormat_ReturnGeneratedValue()
    {
        $generated_value = 'lorem ipsum';
        $this->generator->expects($this->once())->method('format')->will($this->returnValue($generated_value));
        $this->assertEquals($generated_value, $this->faker->format('foo','bar'));
    }


}
