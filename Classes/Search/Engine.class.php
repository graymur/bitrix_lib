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

    public function makeSearch($query, $offset = 0, $limit = 0)
    {
        $retval = array();

        $sql = "
            SELECT *
            FROM b_search_content bsc
            LEFT JOIN b_iblock_site bis
                ON bis.IBLOCK_ID = bsc.PARAM2 AND bis.SITE_ID = '" . SITE_ID . "'
            WHERE
                (bsc.BODY LIKE '%$query%' OR bsc.TITLE LIKE '%$query%')
            LIMIT " . (int) $offset . ", " . (int) $limit . "
        ";

        $res = $this->makeQuery($sql);

        while ($row = $res->Fetch())
        {
            $retval[] = new $this->className($row);
        }

        dv($retval);

        return $retval;
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
        $this->modulesList[$module][$iblockType][$iblockId] = true;
    }

    protected function makeQuery($sql)
    {
        global $DB;
        return $DB->Query($sql, false, __LINE__);
    }
} 