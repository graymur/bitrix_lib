<?php
/**
 * User: graymur
 * Date: 02.09.13
 * Time: 15:28
 */

$__lib_base__ = dirname(dirname(__FILE__));

foreach (glob("$__lib_base__/Cpeople/*_lib.php") as $__path__)
{
    include_once $__path__;
}

spl_autoload_register('__autoload__');
function __autoload__($className)
{
    global $__lib_base__;
    $pathTpl = $__lib_base__ . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.%s.php';

    foreach (array('class', 'trait') as $type)
    {
        $path = sprintf($pathTpl, $type);

        if (file_exists($path))
        {
            include_once $path;
            break;
        }
    }
}

\CModule::IncludeModule('iblock');

\Cpeople\Classes\Cache\Manager::instance()->setCachePath(BASE_PATH . '/temp/cache');

if (isset($_REQUEST['clear_cache']) && $_REQUEST['clear_cache'] == 'Y')
{
    \Cpeople\Classes\Cache\Manager::instance()->clear();
    \Cpeople\Classes\Cache\Manager::instance()->setEnabled(false);
}