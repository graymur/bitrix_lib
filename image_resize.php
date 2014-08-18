<?php

/*
width - ширина. Если указана только ширина без высоты - превью будет именно этой ширины.
height - высота. Если указана только высота без ширины - превью будет именно этой высоты.
type - если не указан, ресайзится пропорционально
    square - квадратное превью, вместо width и height можно указать один параметр size, края картинки обрезаются
    square_put - картинка ресайзится пропорционально и помещается в квадрат заданного размера, добавляются белые/прозрачные поля
    put_out - картинка ресайзится и вписывается в нужные размеры, края обрезаются
    put - картинка ресайзится и вписывается в нужные размеры, края не обрезаются, добавляются белые/прозрачные поля

Примеры

$object->getDetailImageThumb(array('width' => 200, 'height' => 200)) - пропорционально уменьшенное превью по большому краю. Например,
    если исходное изображение - 800*600, то превью будет 200*150

$object->getDetailImageThumb(array('size' => 200, 'type' => 'square')) - квадратное превью 200*200, лишнее обрезается.

$object->getDetailImageThumb(array('width' => 250, 'height' => 150, 'type' => 'put')) - превью 250*150, лишнее обрезается.

В .htaccess нужно добавить:

RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-l
RewriteRule ^resize(.*) lib/Cpeople/image_resize.php?__path=$1 [QSA,L]

и создать папку /resize с правами на запись.

*/

define('BASE_PATH', dirname(dirname(dirname(__FILE__))));
define('IMG_CACHE_PATH', BASE_PATH . '/resize');

include BASE_PATH . '/lib/Cpeople/common_lib.php';

error_reporting(E_ALL);
ini_set('display_error', 'On');

try
{
    $path_string = trim(str_replace(array('..', '\\'), '', urldecode($_REQUEST['__path'])), '/');
    $path = explode('/', $path_string);

    $temp = array_diff_key($_REQUEST, array('__path' => ''));

    if (!is_array($temp))
    {
        throw new Exception('Protection code not found');
    }

    if (!$hash = key($temp))
    {
        throw new Exception('Protection code not found');
    }

    $checkHash = cp_thumb_url_hash($_REQUEST['__path']);

    if ($checkHash != $hash)
    {
        throw new Exception('Wrong protection code');
    }

    $excepted_params = array('w', 'h', 't', 'm', 's', 'e');
    $params = array();

    $temp = explode('-', $path[0]);

    foreach ($temp as $k)
    {
        $key = substr($k, 0, 1);

        if (!in_array($key, $excepted_params))
        {
            throw new Exception('Unexpected param: ' . $key);
        }

        $params[$key] = substr($k, 1);
    }

    if (!empty($params))
    {
        array_shift($path);
    }

    $path_string = join('/', $path);

    if (!file_exists(BASE_PATH . "/$path_string") || !getimagesize(BASE_PATH . "/$path_string"))
    {
        throw new Exception('Source file does not exist or is not an image');
    }

    if (isset($params['w']) && empty($params['w']))
    {
        throw new Exception('Width set but is empty');
    }

    if (isset($params['h']) && empty($params['h']))
    {
        throw new Exception('Height set but is empty');
    }

    $source = BASE_PATH . "/{$path_string}";

    if (file_exists($source) && empty($params))
    {
        $file_name = $source;
    }
    else if (file_exists($source) && !empty($params))
    {
        $cache_path = IMG_CACHE_PATH . dirname($_REQUEST['__path']);

        if (!file_exists($cache_path))
        {
            mkdir(IMG_CACHE_PATH . dirname($_REQUEST['__path']), 0777, true);
        }

        if (!file_exists($cache_path))
        {
            throw new Exception('Cache path was not created');
        }

        $cache_name = $cache_path . DIRECTORY_SEPARATOR . basename($_REQUEST['__path']);

        if (!file_exists(IMG_CACHE_PATH))
        {
            mkdir(IMG_CACHE_PATH, 0777, true);
        }

        if (!file_exists($cache_name))
        {
            require BASE_PATH . '/lib/Cpeople/ImageEditor.class.php';

            $IE = new ImageEditorGD();

            $IE->setSource($source)->setTarget($cache_name);

            $size = @coalesce($params['s'], $params['w']);

            if (isset($params['e']) && (int) $params['e'] > 0 && (int) $params['e'] < 500)
            {
                $IE->cutEdgesByPercentage($v, $v, $v, $v);
            }

            switch (@$params['t'])
            {
                case 'square':
                    $IE->square($size);
                break;

                case 'square_put':
                    $IE->putIntoSquare($size);
                break;

                case 'put':
                    if (empty($params['w'])) throw new Exception('Width is not set for method PUT');
                    if (empty($params['h'])) throw new Exception('Height is not set for method PUT');

                    $IE->putIntoSize($params['w'], $params['h']);
                break;

                case 'put_out':
                    if (empty($params['w'])) throw new Exception('Width is not set for method PUT');
                    if (empty($params['h'])) throw new Exception('Height is not set for method PUT');

                    $IE->cutIntoSize($params['w'], $params['h']);
                break;

                default:

                    if (empty($params['w']))
                    {
                        $mode = IMAGE_EDITOR_RESIZE_HEIGHT;
                    }
                    else if (empty($params['h']))
                    {
                        $mode = IMAGE_EDITOR_RESIZE_WIDTH;
                    }
                    else
                    {
                        $mode = @coalesce($p['m'], IMAGE_EDITOR_RESIZE_PROPORTIONAL);
                    }

                    $thumb_width    = (int) @coalesce($params['w'], $params['h']);
                    $thumb_height   = (int) @coalesce($params['h'], $params['w']);

                    $IE->resize($thumb_width, $thumb_height, $mode);

                break;
            }

            $IE->/*sharpen()->*/commit();
        }

        $file_name = $cache_name;
    }

    $last_modified = filemtime($file_name);

    if (!empty($file_name) && !empty($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $last_modified <= strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']))
    {
        header('HTTP/1.0 304 Not Modified');
    }
    else if (!empty($file_name))
    {
        $size = getimagesize($file_name);
        header("Content-type: {$size['mime']}");
        header('Last-Modified: ' . gmdate("D, d M Y H:i:s", $last_modified) . ' GMT');
        readfile($file_name);
    }
    else
    {
        throw new Exception('File not found');
    }
}
catch (Exception $e)
{
    echo '<!-- ' . $e->getMessage() . ' -->';
    header('HTTP/1.0 404 Not Found');
}
