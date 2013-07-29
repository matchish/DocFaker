<?php
namespace DocFaker;


class Render
{
    public function run($filename, $params = array())
    {
        $tpl = file_get_contents($filename);
        foreach ($params as $key => $value) {
            $tpl = str_replace('[+'.$key.'+]', $value, $tpl);
        }

        return $tpl;
    }
}