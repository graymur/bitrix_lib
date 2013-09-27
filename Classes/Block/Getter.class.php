<?php

namespace Cpeople\Classes\Block;


class Getter
{
    const FETCH_MODE_ALL = 0;
    const FETCH_MODE_FIELDS = 1;
    const FETCH_MODE_PROPERTIES = 2;
    const FETCH_MODE_FETCH = 3;

    const HYDRATION_MODE_ARRAY = 0;
    const HYDRATION_MODE_OBJECTS_ARRAY = 1;
    const HYDRATION_MODE_OBJECTS_COLLECTION = 2;

    private $fetchMode = self::FETCH_MODE_ALL;
    private $hydrationMode = self::HYDRATION_MODE_OBJECTS_ARRAY;
    private $arOrder = array('SORT' => 'asc');
    private $arFilter = null;
    private $arGroupBy = null;
    private $arNavStartParams = null;
    private $arSelectFields = null;
    private $callbacks = array();
    private $resultSetCallback = null;
    private $className = '\Cpeople\Classes\Block\Object';

    private $total;

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
    public function setFetchMode($mode)
    {
        $this->fetchMode = (int) $mode;
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
            throw new \Exception('Wrong arguments count or type for ' . __METHOD__);
        }

        return $this;
    }

    /**
     * @return Getter
     */
    public function setGroupBy($arGroupBy)
    {
        $this->arGroupBy = $arGroupBy;
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
    public function setPageSize($size)
    {
        $this->arNavStartParams['nPageSize'] = (int) $size;
        return $this;
    }

    /**
     * @param $pageNum
     * @return Getter
     */
    public function setPageNum($pageNum)
    {
        $this->arNavStartParams['iNumPage'] = (int) $pageNum;
        return $this;
    }

    /**
     * @param $pagingSize
     * @param $pageNum
     * @return Getter
     */
    public function paginate($pagingSize, $pageNum)
    {
        $this->setPageSize($pagingSize);
        $this->setPageNum(intval($pageNum) < 1 ? 1 : intval($pageNum));
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
    public function addCallback($callback)
    {
        if (!is_callable($callback))
        {
            throw new \Exception('Passed callback is not callable, ' . __METHOD__);
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
            throw new \Exception('Passed callback is not callable, ' . __METHOD__);
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

    /**
     * @return Getter
     */
    public function setClassName($className)
    {
        if (!class_exists($className))
        {
            throw new \Exception("Class $className doest not exist, " . __METHOD__);
        }

//        if (is_subclass_of($className, '\Cpeople\Block\Object'))
//        {
//            throw new \Exception("Class $className does not extend \Cpeople\Block\Object");
//        }

        $this->className = $className;
        return $this;
    }

    /**
     * @return \CDBResult|\CIBlockResult|mixed|string
     */
    public function getResult()
    {
        return \CIBlockElement::GetList(
            $this->arOrder,
            $this->arFilter,
            empty($this->arGroupBy) ? null : $this->arGroupBy,
            empty($this->arNavStartParams) ? null : $this->arNavStartParams,
            $this->arSelectFields
        );
    }

    public function get()
    {
        $retval = array();

        $resultSet = $this->getResult();

        if (isset($this->resultSetCallback))
        {
            $resultSet = call_user_func($this->resultSetCallback, $resultSet);
        }

        while ($obRes = $resultSet->GetNextElement())
        {
            switch ($this->fetchMode)
            {
                case self::FETCH_MODE_FIELDS:
                    $element = $obRes->GetFields();
                    break;

                case self::FETCH_MODE_PROPERTIES:
                    $element = $obRes->getProperties();
                    break;

                default:
                    $element = array_merge($obRes->GetFields(), $obRes->getProperties());
                    break;
            }

            foreach ((array) $this->callbacks as $callback)
            {
                if ($callbackResult = call_user_func($callback, $element))
                {
                    $element = $callbackResult;
                }
            }

            switch ($this->hydrationMode)
            {
                case self::HYDRATION_MODE_OBJECTS_ARRAY:
                case self::HYDRATION_MODE_OBJECTS_COLLECTION:
                    $className = $this->className;
                    $retval[] = new $className($element);
                break;


                default:
                    $retval[] = $element;
                break;
            }
        }

        if ($this->hydrationMode == self::HYDRATION_MODE_OBJECTS_COLLECTION)
        {
            $retval = new Collection($retval);
        }

        return $retval;
    }

    /**
     * @return Object
     */
    public function getOne()
    {
        $retval = $this->get();
        return empty($retval) ? false : $retval[0];
    }

    /**
     * @return Object
     */
    public function getById($id)
    {
        return $this->setHydrationMode(self::HYDRATION_MODE_OBJECTS_ARRAY)->addFilter('ID', $id)->getOne();
    }

    public function getArrayById($id)
    {
        return $this->setHydrationMode(self::HYDRATION_MODE_ARRAY)->addFilter('ID', $id)->getOne();
    }

    /**
     * @return Object
     */
    public function getByCode($code, $iblockId = null)
    {
        $this->setHydrationMode(self::HYDRATION_MODE_OBJECTS_ARRAY)->addFilter('CODE', $code);

        if ($iblockId)
        {
            $this->addFilter('IBLOCK_ID', $iblockId);
        }

        return $this->getOne();
    }

    public function getFoundRows()
    {
        $getter = clone $this;

        // в фильтре есть сложная логика, простая группировка не даст нужного
        // результата, получаем все элементы и считаем из
        if (true || array_key_exists(0, $this->arFilter))
        {
            $getter->setNavStartParams(array());
            $this->total = (int) count(
                $getter
                    ->setNavStartParams(array())
                    ->setFetchMode(self::FETCH_MODE_FIELDS)
                    ->setHydrationMode(self::HYDRATION_MODE_ARRAY)
                    ->get()
            );
        }
        else
        {
            $res = $getter
                ->setNavStartParams(null)
                ->setGroupBy(array('IBLOCK_ID'))
                    ->getResult()
                        ->Fetch();

            $this->total = empty($res) ? false : $res['CNT'];
        }

        return $this->total;
    }

    /**
     * @return \paging
     */
    public function getPagingObject($urlTemplate)
    {
        if (!isset($this->total))
        {
            $this->total = $this->getFoundRows();
        }

        $paging = new \Cpeople\paging($this->arNavStartParams['iNumPage'], $this->total, $this->arNavStartParams['nPageSize']);
        $paging->setFormat($urlTemplate);

        return $paging;
    }
}
