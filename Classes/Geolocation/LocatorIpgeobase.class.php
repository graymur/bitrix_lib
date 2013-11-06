<?php
/**
 * User: graymur
 * Date: 06.11.13
 * Time: 16:14
 */

namespace Cpeople\Classes\Geolocation;

class LocatorIpgeobase extends Locator
{
    /**
     * @return \Cpeople\Classes\Geolocation\Result $result
     */
    public function locate()
    {
        $header = 'Content-Type:application/x-www-form-urlencoded';

        $url = "http://ipgeobase.ru:7020/geo/?ip=$this->ip";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $res = curl_exec($ch);

        if (curl_error($ch))
        {
            return false;
        }

        curl_close($ch);

        $result = new Result();

        if ($xml = simplexml_load_string($res))
        {
            $result->setCity((string) $xml->ip->city);
            $result->setCountry((string) $xml->ip->country);
            $result->setRegion((string) $xml->ip->region);
            $result->setDestrict((string) $xml->ip->district);
            $result->setLatitude((string) $xml->ip->lat);
            $result->setLongtitude((string) $xml->ip->lng);
        }
        else
        {
            $result->setError('Empty result');
        }

        return $result;
    }
}