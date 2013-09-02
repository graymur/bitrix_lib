<?php

namespace Cpeople\Classes\Section;

class Getter
{
    const HYDRATION_MODE_ARRAY = 0;
    const HYDRATION_MODE_OBJECTS_ARRAY = 1;
    const HYDRATION_MODE_OBJECTS_COLLECTION = 1;

    private $hydrationMode = self::HYDRATION_MODE_OBJECTS_ARRAY;
    private $arOrder = array('SORT' => 'asc');
    private $arFilter = null;
    private $arNavStartParams = null;
    private $arSelectFields = null;
    private $callbacks = array();
    private $resultSetCallback = null;
    private $bIncCnt = false;
    private $byId = false;

    private function __construct() {}

    /**
     * @static
     * @return Getter
     */
    static function instance()
    {
        return new self;
    }

    /**
     * @return Getter
     */
    public function fetchById($value)
    {
        $this->byId = (bool) $value;
        return $this;
    }

    /**
     * @return Getter
     */
    public function fetchUserFields($value)
    {
        $this->fetchUserFields = (bool) $value;
        return $this;
    }

    /**
     * @return Getter
     */
    public function setOrder($arOrder)
    {
        $this->arOrder = $arOrder;
        return $this;
    }

    /**
     * @return Getter
     */
    public function setFilter($arFilter)
    {
        $this->arFilter = $arFilter;
        return $this;
    }

    /**
     * @return Getter
     */
    public function addFilter()
    {
        $args = func_get_args();

        if (!is_array($this->arFilter))
        {
            $this->arFilter = array();
        }

        if (count($args) == 1 && is_array($args[0]))
        {
            foreach ($args[0] as $k => $v)
            {
                $this->arFilter[$k] = $v;
            }
        }
        else if (count($args) == 2)
        {
            $this->arFilter[$args[0]] = $args[1];
        }
        else
        {
            throw new \Exception('Wrong arguments count or type for ' . __CLASS__ . '::' . __METHOD__);
        }

        return $this;
    }

    /**
     * @return Getter
     */
    public function setNavStartParams($arNavStartParams)
    {
        $this->arNavStartParams = $arNavStartParams;
        return $this;
    }

    /**
     * @return Getter
     */
    public function setSelectFields($arSelectFields)
    {
        $this->arSelectFields = $arSelectFields;
        return $this;
    }

    /**
     * @return Getter
     */
    public function setIncludeCount($value)
    {
        $this->bIncCnt = (bool) $value;
        return $this;
    }

    /**
     * @return Getter
     */
    public function checkPermissions($value)
    {
        $this->addFilter('CHECK_PERMISSIONS', ($value ? 'Y' : 'N'));
        return $this;
    }

    /**
     * @return Getter
     */
    public function addCallback($callback)
    {
        if (!is_callable($callback))
        {
            throw new \Exception('Passed callback is not callable, ' . __CLASS__ . '::' . __METHOD__);
        }

        $this->callbacks[] = $callback;
        return $this;
    }

    /**
     * @return Getter
     */
    public function setResultSetCallback($callback)
    {
        if (!is_callable($callback))
        {
            throw new \Exception('Passed callback is not callable, ' . __CLASS__ . '::' . __METHOD__);
        }

        $this->resultSetCallback = $callback;
        return $this;
    }

    /**
     * @return Getter
     */
    public function setHydrationMode($mode)
    {
        $this->hydrationMode = $mode;
        return $this;
    }

    public function get()
    {
        $retval = array();

        $resultSet = \CIBlockSection::GetList($this->arOrder, $this->arFilter, $this->bIncCnt, $this->arSelectFields, $this->arNavStartParams);

        if (isset($this->resultSetCallback))
        {
            $resultSet = call_user_func($this->resultSetCallback, $resultSet);
        }

        while ($section = $resultSet->GetNext())
        {
            foreach ((array) $this->callbacks as $callback)
            {
                if ($callbackResult = call_user_func($callback, $section))
                {
                    $section = $callbackResult;
                }
            }

            $key = $this->byId ? $section['ID'] : count($retval);

            switch ($this->hydrationMode)
            {
                case self::HYDRATION_MODE_OBJECTS_ARRAY:
                    $retval[$key] = new Object($section);
                break;


                default:
                    $retval[$key] = $section;
                break;
            }
        }

        return $retval;
    }

    public function getOne()
    {
        $retval = $this->get();
        return empty($retval) ? false : $retval[0];
    }

    /**
     * @return ISectionObject
     */
    public function getById($id)
    {
        return $this->setHydrationMode(self::HYDRATION_MODE_OBJECTS_ARRAY)->addFilter('ID', $id)->getOne();
    }

    public function getByCode($code, $iblockId = null)
    {
        $this->setHydrationMode(self::HYDRATION_MODE_OBJECTS_ARRAY)->addFilter('CODE', $code);

        if ($iblockId)
        {
            $this->addFilter('IBLOCK_ID', $iblockId);
        }

        return $this->getOne();
    }
}
