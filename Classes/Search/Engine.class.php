<?php
/**
 * Created by PhpStorm.
 * User: Администратор
 * Date: 14.03.14
 * Time: 13:47
 */

namespace Cpeople\Classes\Search;


class Engine
{
    protected $defaultClassName = '\Cpeople\Classes\Search\Result';
    protected $className = '\Cpeople\Classes\Search\Result';
    protected $modulesList = array();
    protected $tryInvertedLayout = false;
    protected $query = '';
    protected $minLenth = 0;
    protected $minWordLength = 0;
    protected $arNavStartParams = array('nPageSize' => 10, 'iNumPage' => 1);

    /**
     * @return Engine
     */
    static function instance()
    {
        return new self;
    }

    /**
     * @param null $query
     * @param null $offset
     * @param null $limit
     * @return \Cpeople\Classes\Search\Result[];
     */
    public function makeSearch($query = null, $offset = null, $limit = null)
    {
        if($query !== null) $this->query = $query;

        $retval = array();

        $whereSQL = $this->makeSQLWhere($query);

        $limit = intval($limit === null ? $this->arNavStartParams['nPageSize'] : $limit);
        $offset = intval($offset === null ? ($this->arNavStartParams['iNumPage'] - 1) * $limit : $offset);

        $sql = "
            SELECT *
            FROM b_search_content bsc
            LEFT JOIN b_iblock_site bis
                ON bis.IBLOCK_ID = bsc.PARAM2 AND bis.SITE_ID = '" . SITE_ID . "'
            $whereSQL
            LIMIT " . $offset . ", ". $limit . "
        ";

        $res = $this->makeQuery($sql);

        while ($row = $res->Fetch())
        {
            $retval[] = new $this->className($row);
        }

        return $retval;
    }

    public function makeSQLWhere($query = null)
    {
        if($query === null) $query = $this->query;

        $sqlReadyQuery = $this->prepareQuery($query);
        $invertedQuery = $this->makeInvertedLayout($sqlReadyQuery);

        $modulesSQL = $this->makeModulesSQL($this->modulesList);

        $retval =
            "WHERE
                (
                    bsc.BODY LIKE '%$sqlReadyQuery%' OR bsc.TITLE LIKE '%$sqlReadyQuery%'
                    " . ($invertedQuery ? "OR bsc.BODY LIKE '%$invertedQuery%' OR bsc.TITLE LIKE '%$invertedQuery%'" : "") . "
                )
                $modulesSQL
            ";

        return $retval;
    }

    public function prepareQuery($query)
    {
        $query = trim($query);
        $query = str_replace(preg_split('##', '!@#$%^&*()-+=#{}[]~`,./?<>', 0, PREG_SPLIT_NO_EMPTY), '%', $query);
        $query = preg_replace('#%+#', '%', $query);

        if (!empty($this->minWordLength))
        {
            $query = preg_replace('/\b[^\s]{1,' . ($this->minWordLength - 1) . '}\b/u', '', $query);
        }

        $query = preg_replace('#\s+#', '%', $query);

        return $query;
    }

    public function setClassName($className)
    {
        $dummy = new $className;

        if (!is_subclass_of($dummy, $this->defaultClassName))
        {
            throw new SearchException('Class ' . $className . ' is not subclass of ' . $this->defaultClassName);
        }

        $this->className = $className;

        return $this;
    }

    public function addModule($module, $iblockType = null, $iblockId = null)
    {
        if (is_array($iblockId))
        {
            foreach ($iblockId as $id)
            {
                $this->modulesList[$module][$iblockType][$id] = true;
            }
        }
        else
        {
            $this->modulesList[$module][$iblockType][$iblockId] = true;
        }

        return $this;
    }

    public function getFoundRows($query = null)
    {
        $retval = 0;

        $whereSQL = $this->makeSQLWhere($query);

        $sql = "
            SELECT COUNT(*)
            FROM b_search_content bsc
            LEFT JOIN b_iblock_site bis
                ON bis.IBLOCK_ID = bsc.PARAM2 AND bis.SITE_ID = '" . SITE_ID . "'
            $whereSQL
        ";

        $res = $this->makeQuery($sql);

        if ($row = $res->Fetch())
        {
            $retval = $row['COUNT(*)'];
        }

        $this->total = $retval;

        return $retval;
    }

    protected function makeQuery($sql)
    {
        global $DB;
        return $DB->Query($sql, false, __LINE__);
    }

    protected function makeModulesSQL($modulesList)
    {
        $modulesSQL = '';

        /** если есть модули */
        if ($modulesList)
        {
            $modulesSQL .= " AND (";
        }

        foreach ($modulesList as $module => $iblockTypes)
        {
            if ($iblockTypes != reset($modulesList))
            {
                $modulesSQL .= " OR ";
            }
            /** запись модуля */
            $modulesSQL .= "(bsc.MODULE_ID = '$module'";

            /** если есть типы инфоблоков */
            if (array_filter(array_keys($iblockTypes)))
            {
                $modulesSQL .= " AND (";
            }

            /** для модуля main фильтруем по URL */
            if($module === 'main')
            {
                $modulesSQL .= "bsc.URL LIKE '" . implode("' OR bsc.URL LIKE '", array_keys($iblockTypes)) . "'";
            }
            else
            {
                foreach ($iblockTypes as $iblockType => $iblockIds)
                {
                    if ($iblockIds != reset($iblockTypes))
                    {
                        $modulesSQL .= " OR ";
                    }
                    if ($iblockType)
                    {
                        /** запись типа инфоблока */
                        $modulesSQL .= "(bsc.PARAM1 = '$iblockType'";

                        if ($iblocks = implode(",", array_keys($iblockIds)))
                        {
                            /** запись инфоблоков */
                            $modulesSQL .= " AND bsc.PARAM2 IN ($iblocks)";
                        }

                        /** закрываем каждый тип инфоблока */
                        $modulesSQL .= ")";
                    } elseif ($iblocks = implode(",", array_keys($iblockIds)))
                    {
                        /** запись инфоблоков */
                        if (array_filter(array_keys($iblockTypes)))
                        {
                            $modulesSQL .= "bsc.PARAM2 IN ($iblocks)";
                        /** если у нас только инфоблоки без типов */
                        } else
                        {
                            $modulesSQL .= " AND bsc.PARAM2 IN ($iblocks)";
                        }
                    }
                }
            }

            /** если есть типы инфоблоков */
            if (array_filter(array_keys($iblockTypes)))
            {
                $modulesSQL .= ")";
            }

            /** закрываем каждый модуль */
            $modulesSQL .= ")";
        }

        /** если есть модули */
        if ($modulesList)
        {
            $modulesSQL .= ")";
        }

        return $modulesSQL;
    }

    public function setInvertedLayout($bVal)
    {
        $this->tryInvertedLayout = $bVal;

        return $this;
    }

    protected function makeInvertedLayout($query)
    {
        if(!$this->tryInvertedLayout) return '';

        $invertedQuery = '';
        $fromString = 'qwertyuiop[]asdfghjkl;\'\zxcvbnm,./QWERTYUIOP{}ASDFGHJKL:"|ZXCVBNM<>?йцукенгшщзхъфывапролджэ\\ячсмитьбю.ЙЦУКЕНГШЩЗХЪФЫВАПРОЛДЖЭ/ЯЧСМИТЬБЮ,';
        $toString   = 'йцукенгшщзхъфывапролджэ\\ячсмитьбю.ЙЦУКЕНГШЩЗХЪФЫВАПРОЛДЖЭ/ЯЧСМИТЬБЮ,qwertyuiop[]asdfghjkl;\'\zxcvbnm,./QWERTYUIOP{}ASDFGHJKL:"|ZXCVBNM<>?';

        for($i = 0, $c = strlen($query); $i < $c; $i++)
        {
            $curChar = substr($query, $i, 1);
            $pos = strpos($fromString, $curChar);
            $newChar = $pos !== false ? substr($toString, $pos, 1) : $curChar;

            $invertedQuery .= $newChar;
        }

        return $invertedQuery;
    }

    public function setPageSize($size)
    {
        $this->arNavStartParams['nPageSize'] = (int) $size;

        return $this;
    }

    public function setPageNum($pageNum)
    {
        $this->arNavStartParams['iNumPage'] = (int) $pageNum;

        return $this;
    }

    public function paginate($pagingSize, $pageNum)
    {
        $this->setPageSize($pagingSize);
        $this->setPageNum(intval($pageNum) < 1 ? 1 : intval($pageNum));

        return $this;
    }

    /**
     * @param $urlTemplate
     * @return \Cpeople\paging
     */
    public function getPagingObject($urlTemplate)
    {
        if (!isset($this->total))
        {
            $this->total = $this->getFoundRows($this->query);
        }

        $paging = new \Cpeople\paging($this->arNavStartParams['iNumPage'], $this->total, $this->arNavStartParams['nPageSize']);
        $paging->setFormat($urlTemplate);

        return $paging;
    }

    public function setQuery($query)
    {
        $this->query = $query;

        return $this;
    }
} 
