<?php
namespace DocFaker;


class TemplatesConfig
{

    private $modx;
    private $config_path;

    function __construct(\DocumentParser &$modx)
    {
        $this->modx = $modx;
        $this->config_path = $this->modx->config['base_path'] . 'assets/modules/DocFaker/src/config/templates_config.json';
    }

    public function load()
    {
        $config = array();
        $templates_table = $this->modx->getFullTableName('site_templates');
        $tvtemplate_table = $this->modx->getFullTableName('site_tmplvar_templates');
        $tvs_table = $this->modx->getFullTableName('site_tmplvars');
        $sql = 'SELECT t.id,t.templatename,tvt.tmplvarid,tvs.caption,tvs.name
                FROM ' . $templates_table . ' AS t
                LEFT JOIN ' . $tvtemplate_table . ' AS tvt
                ON t.id = tvt.templateid
                LEFT JOIN ' . $tvs_table . ' AS tvs
                ON tvt.tmplvarid = tvs.id';
        $result = $this->modx->db->query($sql);
        if ($result) {
            $current_config = $this->getCurrentConfig();
            $rows = $this->modx->db->makeArray($result);

            $config = $this->makeConfig($rows);
            $config = $this->updateConfig($current_config, $config);
        }


        return count($config) > 0 ? $config : false;
    }

    public function save($config)
    {
        return (file_put_contents($this->config_path, $config)) ? true : false;
    }

    private function getCurrentConfig()
    {
        $config = array();
        if (file_exists($this->config_path)) {
            $config = file_get_contents($this->config_path);
            $config = json_decode($config, true);
        }
        return is_array($config) ? $config : array();
    }

    private function updateConfig($current_config, $config)
    {
        $result = array();
        foreach ($config as $template) {
            foreach ($current_config as $current_template) {
                if ($template['id'] == $current_template['id']) {
                    $fields = array();
                    foreach ($template['fields'] as $field) {
                        foreach ($current_template['fields'] as $current_field) {
                            if ($field['name'] == $current_field['name']) {
                                $field['formatter'] = $current_field['formatter'];
                            }
                        }
                        $fields[] = $field;
                    }
                    $template['fields'] = $fields;
                }
            }
            $result[] = $template;
        }
        return $result;
    }

    private function makeConfig($rows)
    {
        $config = array();
        foreach ($rows as $row) {
            $config[$row['id']]['id'] = $row['id'];
            $config[$row['id']]['templatename'] = $row['templatename'];
            if ($row['tmplvarid']) {
                $config[$row['id']]['fields'][$row['tmplvarid']] = array(
                    'id' => $row['tmplvarid'],
                    'name' => $row['name'],
                    'caption' => $row['caption'],
                    'formatter' => null
                );

            }
        }

        $config = $this->addStandartFields($config);

        return $config;
    }

    private function addStandartFields($config)
    {
        $fields = array(
            'pagetitle' => 'Заголовок',
            'introtext' => 'Аннотация',
            'content' => 'Содержимое'
        );
        foreach ($config as $key => $template) {
            foreach ($fields as $name => $caption) {
                $config[$key]['fields'][] = array(
                    'name' => $name,
                    'caption' => $caption,
                    'formatter' => null
                );
            }
        }
        return $config;
    }

}