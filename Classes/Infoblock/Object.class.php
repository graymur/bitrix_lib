<?php

namespace Cpeople\Classes\Infoblock;

class Object {

    protected $data;
    protected $properties;
    protected $fields;

    public function __construct($data = array())
    {
        if (!is_array($data))
        {
            throw new \Exception('Argument should be an array to ' . __METHOD__);
        }

        $this->data = $data;
    }

    public function __get($name)
    {
        if (isset($this->data[strtoupper($name)]))
        {
            return $this->data[strtoupper($name)];
        }

        $trace = debug_backtrace();
        trigger_error(
            'Undefined property in __get(): ' . $name .
            ' in file ' . $trace[0]['file'] .
            ' line ' . $trace[0]['line'], E_USER_NOTICE
        );
    }

    public function getProperties()
    {
        if (!isset($this->properties))
        {
            $this->properties = array();

            $rs = \CIBlockProperty::GetList(array('sort' => 'asc'), array('IBLOCK_ID' => $this->id));

            while($ar = $rs->Fetch())
            {
                $this->properties[$ar['CODE']] = new Property($ar);
            }
        }

        return $this->properties;
    }

    public function getFields()
    {
        if (!isset($this->fields))
        {
            $this->fields = array();

            $res = \CIBlock::GetFields($this->id);

            foreach ($res as $k => $element)
            {
                $this->fields[$k] = new Field($k, $element);
            }
        }

        return $this->fields;
    }
}
