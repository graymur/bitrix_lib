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
    protected $minLenth = 0;
    protected $minWordLength = 0;

    public function makeSearch($query, $offset = 0, $limit = 10)
    {
        $retval = array();

        $modulesSQL = $this->makeModulesSQL($this->modulesList);

        $sqlReadyQuery = $this->prepareQuery($query);

        $sql = "
            SELECT *
            FROM b_search_content bsc
            LEFT JOIN b_iblock_site bis
                ON bis.IBLOCK_ID = bsc.PARAM2 AND bis.SITE_ID = '" . SITE_ID . "'
            WHERE
                (bsc.BODY LIKE '%$sqlReadyQuery%' OR bsc.TITLE LIKE '%$sqlReadyQuery%')
                $modulesSQL
            LIMIT " . (int) $offset . ", " . (int) $limit . "
        ";

        $res = $this->makeQuery($sql);

        while ($row = $res->Fetch())
        {
            $retval[] = new $this->className($row);
        }

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
    }

    public function addModule($module, $iblockType = null, $iblockId = null)
    {
        if (is_array($iblockId))
        {
            foreach ($iblockId as $id)
            {
                $this->modulesList[$module][$iblockType][$id] = true;
            }
        } else
        {
            $this->modulesList[$module][$iblockType][$iblockId] = true;
        }
    }

    public function getFoundRows($query)
    {
        $retval = 0;

        $modulesSQL = $this->makeModulesSQL($this->modulesList);
        $query = str_replace(' ', '%', $query);

        $sql = "
            SELECT COUNT(*)
            FROM b_search_content bsc
            LEFT JOIN b_iblock_site bis
                ON bis.IBLOCK_ID = bsc.PARAM2 AND bis.SITE_ID = '" . SITE_ID . "'
            WHERE
                (bsc.BODY LIKE '%$query%' OR bsc.TITLE LIKE '%$query%')
                $modulesSQL
        ";

        $res = $this->makeQuery($sql);

        if ($row = $res->Fetch())
        {
            $retval = $row['COUNT(*)'];
        }

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
} 