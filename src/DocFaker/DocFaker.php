<?php
namespace DocFaker;

class DocFaker
{
    private $faker, $modResource;

    function __construct(FakerInterFace $faker, modResourceInterface $modResource)
    {
        $this->faker = $faker;
        $this->modResource = $modResource;
    }

    private function fill($fields = array())
    {
        foreach ($fields as $key => $value) {
            if (isset($value) && !is_numeric($value)) {
                $fields[$key] = $this->faker->format($value, array());
            }
        }

        return $fields;
    }

    public function create($fields)
    {
        if (!is_array($fields)) {
            throw new \InvalidArgumentException('Argument must be array and array can\'t be empty');
        }
        $fields = is_array($fields) ? $fields : array();
        $fields = $this->fill($fields);
        $this->modResource->create($fields);
        $docId = $this->modResource->save();
        return $docId;
    }
}