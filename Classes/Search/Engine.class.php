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
        if (!class_exists($this->className))
        {
            throw new SearchException('Class ' . $this->className . ' does not exist');
        }

        $dummy = new $this->className;

        if (!is_subclass_of($dummy, $this->defaultClassName))
        {
            throw new SearchException('Class ' . $this->className . ' is not subclass of ' . $this->defaultClassName);
        }

        $sql = "
            SELECT *
            FROM b_search_content
            WHERE
        ";
    }

    public function addModule($module, $iblockType = null, $iblockId = null)
    {
        $this->modulesList[$module][$iblockType][$iblockId] = true;
    }
} 