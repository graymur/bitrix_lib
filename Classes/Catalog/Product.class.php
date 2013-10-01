<?php
/**
 * User: graymur
 * Date: 01.10.13
 * Time: 16:38
 */

namespace Cpeople\Classes\Catalog;

class Product extends \Cpeople\Classes\Block\Object
{
    function getOffers()
    {
        $retval = array();

        $arInfo = \CCatalogSKU::GetInfoByProductIBlock($this->iblock_id);

        if (is_array($arInfo))
        {
            $retval = \Cpeople\Classes\Block\Getter::instance()->setFilter(array(
                    'IBLOCK_ID' => $arInfo['IBLOCK_ID'],
                    'PROPERTY_' . $arInfo['SKU_PROPERTY_ID'] => $this->id
            ))
                ->setClassName('\Cpeople\Classes\Catalog\Offer')
                ->get();
        }

        return empty_array($retval) ? false : $retval;
    }

    function getBasketId()
    {
        return $this->id;
    }

    function getOfferBasketId()
    {
        $offers = $this->getOffers();
        return $offers ? $offers[0]->id : $this->id;
    }
}
