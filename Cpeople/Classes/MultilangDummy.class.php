<?php

namespace Cpeople\Classes;

class MultilangDummy extends \Cpeople\Classes\Block\Object
{
    use \MultilangFields;

    public function getLangTitle()
    {
        return coalesce($this->getLangPropText('TITLE'), $this->name);
    }
}
