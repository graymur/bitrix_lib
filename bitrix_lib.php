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

    $langFilePath = BASE_PATH . '/bitrix/templates/.default/lang/'.LANGUAGE_ID.'/phrases.php';

    if (!isset($MESS))
    {
        if (file_exists($langFilePath))
        {
            require $langFilePath;
        }
    }

    $retval = $key;

    if (isset($MESS[$key]))
    {
        $retval = $special ? htmlspecialchars($MESS[$key]) : $MESS[$key];
    }
    else
    {
        if (!file_exists($langFilePath))
        {
            @mkdir(dirname($langFilePath), 0777, true);
            @touch($langFilePath, 0666);
            file_put_contents($langFilePath, "<?\n\$MESS = array(\n);");
        }

        if (file_exists($langFilePath) && ($content = file_get_contents($langFilePath)))
        {
            $string = "'" . addslashes($key) . "'";
            $content = str_replace('?>', '', $content);
            $content = str_replace(');', "    $string => $string,\n);", $content);
            file_put_contents($langFilePath, $content);

            require $langFilePath;
        }

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
    $initialUrl = $url;

    if (!empty($options))
    {
        $url_part = '/';

        foreach ($options as $k => $v)
        {
            $url_part .= substr($k, 0, 1) . "$v-";
        }

        $url_part = trim($url_part, '-');

        $url = $url_part . '/' . trim($url, '/');

        $url = '/' . basename(IMG_CACHE_PATH) . $url . '?' . cp_thumb_url_hash($url);
    }

    if (defined('CP_GET_THUMB_URL_GENERATE') && CP_GET_THUMB_URL_GENERATE)
    {
        cp_generate_thumb($initialUrl, $url, $options);
    }

    return $url;
}

function cp_generate_thumb($url, $resizedUrl, $options)
{
    try
    {
        $sourcePath = BASE_PATH . $url;
        $targetPath = BASE_PATH . preg_replace('#(\?.*)#', '', $resizedUrl);
        $targetDir = dirname($targetPath);

        if (file_exists($targetPath))
        {
            throw new Exception('Already exists');
        }

        if (!file_exists($sourcePath))
        {
            throw new Exception('Source not found');
        }

        if (!file_exists($targetDir))
        {
            mkdir($targetDir, 0777, true);
        }

        if (!file_exists($targetDir))
        {
            throw new Exception('Could not create cache dir');
        }

        if (!class_exists('ImageEditorGD'))
        {
            require_once BASE_PATH . '/lib/Cpeople/ImageEditor.class.php';
        }

        $IE = new ImageEditorGD();

        $IE->setSource($sourcePath)->setTarget($targetPath);

        $size = @coalesce($options['size'], $options['width']);

        switch (@$options['type'])
        {
            case 'square':
                $IE->square($size);
                break;

            case 'square_put':
                $IE->putIntoSquare($size);
                break;

            case 'put':
                if (empty($options['width'])) throw new Exception('Width is not set for method PUT');
                if (empty($options['height'])) throw new Exception('Height is not set for method PUT');

                $IE->putIntoSize($options['width'], $options['height']);
                break;

            case 'put_out':
                if (empty($options['width'])) throw new Exception('Width is not set for method PUT');
                if (empty($options['height'])) throw new Exception('Height is not set for method PUT');

                $IE->cutIntoSize($options['width'], $options['height']);
                break;

            default:

                if (empty($options['width']))
                {
                    $mode = IMAGE_EDITOR_RESIZE_HEIGHT;
                }
                else if (empty($options['height']))
                {
                    $mode = IMAGE_EDITOR_RESIZE_WIDTH;
                }
                else
                {
                    $mode = @coalesce($options['mode'], IMAGE_EDITOR_RESIZE_PROPORTIONAL);
                }

                $thumb_width    = (int) @coalesce($options['width'], $options['height']);
                $thumb_height   = (int) @coalesce($options['height'], $options['width']);

                $IE->resize($thumb_width, $thumb_height, $mode);

                break;
        }

        $IE->commit();

    }
    catch (Exception $e)
    {
//        dv( 'cp_generate_thumb: ' . $e->getMessage());
    }
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

function cp_get_iblocks_by_type($type, $filter = array())
{
    $retval = array();

    $filter['TYPE'] = $type;

    $res = \CIBlock::GetList(null, $filter);

    while($row = $res->Fetch())
    {
        $retval[] = $row;
    }

    return $retval;
}

function cp_is_main()
{
    return cp_current_url(true) == SITE_URL;
}

function cp_get_ib_properties($IBlockId)
{
    static $result = array();
    if(!isset($result[$IBlockId]))
    {
        $rs = CIBlockProperty::GetList(array('sort'=>'asc'), array('IBLOCK_ID'=>$IBlockId));

        while($ar = $rs->Fetch())
        {
//            $result[$IBlockId][$ar['ID']] = $ar;
            $result[$IBlockId][$ar['CODE']] = $ar;
        }
    }

    return $result[$IBlockId];
}

function cp_is_standard_field($fieldName)
{
    static $arStandardFields = array('ID', 'CODE', 'EXTERNAL_ID', 'XML_ID', 'NAME',
        'IBLOCK_ID', 'IBLOCK_SECTION_ID',
        'ACTIVE', 'DATE_ACTIVE_FROM', 'DATE_ACTIVE_TO',
        'SORT', 'PREVIEW_PICTURE', 'PREVIEW_TEXT', 'PREVIEW_TEXT_TYPE',
        'DETAIL_PICTURE', 'DETAIL_TEXT', 'DETAIL_TEXT_TYPE',
        'MODIFIED_BY', 'TAGS');

    return in_array($fieldName, $arStandardFields);
}

/**
 * Фунцкция принимает список инфоблоков \Cpeople\Classes\Block\Object,
 * делает выборку разделов и возвращает список разделов \Cpeople\Classes\Section\Object
 * с полем elements, содержащим инфорблоки
 *
 * @param $iblocks
 * @return array
 */
function cp_group_by_section($iblocks, $level = 0)
{
    if (empty($iblocks) || !is_array($iblocks)) return false;

    $sectionsIds = array();

    $list = new \Cpeople\Classes\Block\Collection($iblocks);

    foreach ($list as $item)
    {
        $sectionsIds[] = $item->iblock_section_id;
    }

    if (empty_array($sectionsIds)) return false;

    $sections = \Cpeople\Classes\Section\Getter::instance()->setFilter(array(
        'ID' => $sectionsIds
    ))->checkPermissions(false)->get();

    foreach ($sections as $section)
    {
        $section->elements = $list->getBy('iblock_section_id', $section->id);
    }

    return $sections;
}

function cp_get_iblock_dates($filter = array())
{
    $dates = array();

    \Cpeople\Classes\Block\Getter::instance()
        ->setOrder(array('DATE_ACTIVE_FROM' => 'DESC'))
        ->setFilter($filter)
        ->setHydrationMode(\Cpeople\Classes\Block\Getter::HYDRATION_MODE_ARRAY)
        ->setSelectFields(array('DATE_ACTIVE_FROM'))
        ->addCallback(function ($element) use(&$dates) {
            $timestamp = strtotime($element['ACTIVE_FROM']);
            $dates[date('Y', $timestamp)][date('n', $timestamp)]++;
        })
        ->get();

    return $dates;
}

function cp_month_name($month, $format)
{
    $timestamp = mktime(1,1,1, $month, 1, date('Y'));
    return date($format, $timestamp);
}