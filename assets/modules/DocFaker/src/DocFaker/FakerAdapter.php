<?php
namespace DocFaker;

require_once 'FakerInterFace.php';

class FakerAdapter implements FakerInterFace
{

    private
        $documentor,
        $generator;


    function __construct(\Faker\Documentor $documentor, \Faker\Generator $generator)
    {
        $this->documentor = $documentor;
        $this->generator = $generator;
    }

    public function format($formatter, $arguments)
    {
        return $this->generator->format($formatter, $arguments);
    }

    public function getFormatters()
    {
        $formatters = array();
        $providers = array_reverse($this->generator->getProviders());
        $providers[] = new \Faker\Provider\Base($this->generator);
        foreach ($providers as $provider) {
            $providerClass = get_class($provider);
            $formatters[$providerClass] = array();
            $refl = new \ReflectionObject($provider);
            foreach ($refl->getMethods(\ReflectionMethod::IS_PUBLIC) as $reflmethod) {
                if ($reflmethod->getDeclaringClass()->getName() == 'Faker\Provider\Base' && $providerClass != 'Faker\Provider\Base') {
                    continue;
                }
                $methodName = $reflmethod->name;
                if ($reflmethod->isConstructor()) {
                    continue;
                }
                $parameters = array();
                foreach ($reflmethod->getParameters() as $reflparameter) {
                    $parameter = '$' . $reflparameter->getName();
                    if ($reflparameter->isDefaultValueAvailable()) {
                        $parameter .= ' = ' . var_export($reflparameter->getDefaultValue(), true);
                    }
                    $parameters [] = $parameter;
                }
                $example = $this->generator->format($methodName);
                if (is_array($example)) {
                    $example = "array('" . join("', '", $example) . "')";
                } elseif ($example instanceof \DateTime) {
                    $example = "DateTime('" . $example->format('Y-m-d H:i:s') . "')";
                } else {
                    $example = var_export($example, true);
                }
                $formatters[$providerClass][$methodName] = array(
                    'example' => $example,
                    'parameters' => $parameters);
            }
        }

        return $formatters;
    }
}