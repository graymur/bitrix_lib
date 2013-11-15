<?php

function dv_rb($die = true)
{
    global $APPLICATION;

    $APPLICATION->RestartBuffer();

    call_user_func_array('dv', func_get_args());

    if ($die)
    {
        die;
    }
}

class Exception404 extends Exception {} ;

function __($key, $special = false)
{
    return  cp_get_language_message($key, $special);
}

function cp_get_language_message($key, $special = false)
{
    static $MESS;

    if (!isset($MESS))
    {
        $path = BASE_PATH . '/bitrix/templates/.default/lang/'.LANGUAGE_ID.'/phrases.php';

        if (file_exists($path))
        {
            require $path;
        }
    }

    $retval = $key;

    if (isset($MESS[$key]))
    {
        $retval = $special ? htmlspecialchars($MESS[$key]) : $MESS[$key];
    }
    else
    {
        $missingFile = BASE_PATH . '/temp/missing_messages.txt';

        $missing = @file($missingFile);

        $phrases = array();

        foreach ($missing as $v)
        {
            list($phrase, $url) = explode("\t", $v);
            $phrases[] = trim($phrase);
        }

        if (!in_array($key, $phrases))
        {
            global $APPLICATION;

            $fp = fopen($missingFile, 'a+');
            fwrite($fp, "$key\t{$APPLICATION->GetCurUri()}\r\n");
            fclose($fp);
        }
    }

    return $retval;
}

function cp_fetch_site_info($site_id = 0)
{
    static $info;

    if (!isset($info))
    {
        $rsSites = CSite::GetByID(coalesce($site_id, SITE_ID));
        $info = $rsSites->Fetch();
    }

    return $info;
}

function cp_get_site_email()
{
    $info = cp_fetch_site_info();
    return $info['EMAIL'];
}

function cp_get_site_name()
{
    $info = cp_fetch_site_info();
    return $info['SITE_NAME'];
}

function cp_get_thumb_url($url, $options = array())
{
    if (!empty($options))
    {
        $url_part = '/' . basename(IMG_CACHE_PATH) . '/';

        foreach ($options as $k => $v)
        {
            $url_part .= substr($k, 0, 1) . "$v-";
        }

        $url_part = trim($url_part, '-');

        $url = $url_part . '/' . trim($url, '/');
    }

    return $url;
}

function cp_bitrix_date($format, $date)
{
    list ($d, $m, $y) = explode('.', $date);
    $time = mktime(1,1,1,$m,$d,$y);

    $retval = null;

    switch (LANGUAGE_ID)
    {
        case 'en':
            $retval = date($format, $time);
        break;

        default:
            $retval = russian_date($format, $time);
        break;
    }

    return $retval;
}

function CPGetMessage($key, $aReplace = false)
{
    $retval = GetMessage($key, $aReplace);

    return empty($retval) ? $key : $retval;
}

function cp_language_text($key)
{
    static $list;

    if (!isset($list))
    {
        $list = \Cpeople\Classes\Block\Getter::instance()
            ->addFilter('IBLOCK_TYPE', 'texts')
            ->setClassName('MultilangDummy')
            ->get();
    }

    /** @var $text  MultilangDummy */
    $text = false;

    foreach ($list as $item)
    {
        if ($item->code == $key)
        {
            $text = $item;
            break;
        }
    }

    if (!$text)
    {
        throw new Exception("Text with key '$key' not found");
    }

    return $text->getLangPropText('TEXT');
}

function cp_bitrix_sessid_post($varname='sessid')
{
	return preg_replace('/id\=".*"/isxU', '', bitrix_sessid_post($varname));
}

function cp_menu_plain2tree($plainArray)
{
    $tree = $pointers = array();

    $pointers[dirname($plainArray[0]['LINK'])] =& $tree;

    foreach ($plainArray as $item)
    {
        $item['CHILDREN'] = array();

        $pointers[rtrim($item['LINK'], '/')] =& $item['CHILDREN'];

        $pointers[dirname($item['LINK'])][] = $item;
    }

    return $tree;
}

function cp_categories_plain2tree(&$resultArray, $plainArray, $parentId)
{
    foreach($plainArray as $item)
    {
        if ((int) $item['IBLOCK_SECTION_ID'] == $parentId)
        {
            $item['CHILDREN'] = array();
            cp_categories_plain2tree($item['CHILDREN'], $plainArray, $item['ID']);
            $resultArray[] = $item;
        }
    }
}

function get_iblock_detail_picture_callback($element)
{
    if ($file = CFile::GetByID($element['DETAIL_PICTURE'])->GetNext())
    {
        $element['DETAIL_IMAGE_SRC'] = CFile::GetFileSRC($file);
    }

    return $element;
}

function get_iblock_preview_picture_callback($element)
{
    if ($file = CFile::GetByID($element['PREVIEW_PICTURE'])->GetNext())
    {
        $element['PREVIEW_IMAGE_SRC'] = CFile::GetFileSRC($file);
    }

    return $element;
}

function getIBlocks($arFilter = array(), $baseURL = null)
{
    $retval = array();

    $res = CIBlock::GetList(
        array(),
        array_merge(array(
            'SITE_ID' => SITE_ID,
            'ACTIVE' => 'Y',
            'CNT_ACTIVE'=>'Y',
            'CHECK_PERMISSIONS' => 'N'
        ), $arFilter), true
    );

    while($iblock = $res->Fetch())
    {
        if ($file = CFile::GetByID($iblock['PICTURE'])->GetNext())
        {
            $iblock['IMAGE_SRC'] = CFile::GetFileSRC($file);
        }

        $retval[] = $iblock;
    }

    return $retval;
}

function getIBlockById($id, $baseURL)
{
    $retval = getIBlocks(array('ID' => $id), $baseURL);
    return empty_array($retval) ? false : $retval[0];
}

function bitrix_404_error()
{
    define('ERROR_404', true);
}

function cp_current_url($removeQuery = false)
{
    global $APPLICATION;

    $url = $APPLICATION->GetCurUri();

    if ($removeQuery)
    {
        $url = preg_replace('/\?.*$/isxU', '', $url);
    }

    return $url;
}