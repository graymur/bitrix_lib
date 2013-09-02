<?php

function _empty()
{
    foreach (func_get_args() as $arg)
    {
        if (empty($arg))
        {
            return true;
        }
    }

    return false;
}

function dv()
{
    $args = func_get_args();
    if (!$args) return false;

    for ($i = 0; $i < count($args); $i++)
    {
        echo '<pre style="text-align: left; background-color: white; color: black; font-size: 12px">' . htmlspecialchars(print_r($args[$i], true)) . '</pre>';
    }
}

function dvv()
{
    $args = func_get_args();
    if (!$args) return false;

    for ($i = 0; $i < count($args); $i++)
    {
        echo '<pre style="text-align: left; background-color: white; color: black;">';
        var_dump($args[$i]);
        echo '</pre>';
    }
}

function dt($time, $format = 'd.m.Y H:i:s')
{
    foreach ((array) $time as $t)
    {
        dv(date($format, $t));
    }
}

function format_file_size($size)
{
    if ($size >= 1262485504)
    {
        return number_format($size / 1262485504, 2, '.', ' ') . ' Gb';
    }
    elseif ($size >= 1048576)
    {
        return number_format($size / 1048576, 2, '.', ' ') . ' Mb';
    }
    elseif ($size >= 1024)
    {
        return number_format($size / 1024, 2, '.', ' ') . ' kb';
    }
    else
    {
        return number_format($size, 0, '.', ' ') . ' b';
    }
}

function redirect($location, $timeout = 0)
{
    if ( ($timeout == 0) && (!headers_sent()))
    {
        header('Location: ' . $location);
        exit;
    }
    else
    {
        $timeout = $timeout * 1000;

        if ($timeout > 0)
        {
            print "<p>Click <a href=\"" . $location . "\">here</a> to continue</p>\n";
        }

        print <<< JS
<script>
window.setTimeout("window.location = '{$location}'", $timeout);
</script>

JS;
    }
}

/**
* @param int time Unix timestamp
* @param string mode day|hour|month|year
* @param boolean sqlReady
*/
function time_borders($time, $mode = 'day', $sqlReady = false)
{
    $_time  = getdate($time);
    $out    = array();

    switch ($mode)
    {
        case 'minute':
            $out[] = mktime($_time['hours'], $_time['minutes'], 0, $_time['mon'], $_time['mday'], $_time['year']);
            $out[] = $out[0] + 59;
        break;

        case 'hour':
            $out[] = mktime($_time['hours'], 0, 0, $_time['mon'], $_time['mday'], $_time['year']);
            $out[] = $out[0] + 3599;
        break;

        case 'day':
            $out[] = mktime(0, 0, 0, $_time['mon'], $_time['mday'], $_time['year']);
            $out[] = $out[0] + 86399;
        break;

        case 'week':
            $mday = date('j', $time) - date('w', $time) + 1;
            // +1 is here to make Monday the first day of week, like in all normal world :)
            $out[] = mktime(0, 0, 0, $_time['mon'], $mday, $_time['year']);
            $out[] = $out[0] + 86400 * 7 - 1;
        break;

        case 'month':
            $out[] = mktime(0, 0, 0, $_time['mon'], 1, $_time['year']);
            $out[] = mktime(23, 59, 59, $_time['mon'], date('t', $time),  $_time['year']);
        break;

        case 'year':
            $out[] = mktime(0, 0, 0, 1, 1, $_time['year']);
            $out[] = mktime(23, 59, 59, 12, 31, $_time['year']);
        break;
    }

    if ($sqlReady)
    {
        $out[0] = date('Y-m-d H:i:s', $out[0]);
        $out[1] = date('Y-m-d H:i:s', $out[1]);
    }

    return $out;
}

function syscall($command)
{
    if ($proc = popen("($command)2>&1", 'r'))
    {
        while (!feof($proc))
        {
            @$result .= fgets($proc, 1000);
        }

        pclose($proc);
        return $result;
    }
}

function enconvert($string, $from_encoding, $to_encoding)
{
    if (function_exists('mb_convert_encoding'))
    {
        return mb_convert_encoding($string, $to_encoding, $from_encoding);
    }
    else if (function_exists('iconv'))
    {
        return iconv($from_encoding, $to_encoding, $string);
    }
    else
    {
        return $string;
    }
}

function enconvert_file($filename, $from_encoding, $to_encoding)
{
    return file_put_contents($filename, enconvert(file_get_contents($filename), 'CP1251', 'UTF8'));
}

function text4JS($text, $quote = "'")
{
    $text = str_replace(array("\n", "\r"), '', $text);
    return str_replace($quote, "\\{$quote}", $text);
}

function text4flash($text)
{
    $text = str_replace('strong>', 'b>', $text);
    $text = preg_replace('/<br\s*\/>/i', '<br>', $text);
    return text4JS((strip_tags(preg_replace('/<[a-z0-9]+\s+([^\>]*)(?!\/)>/isxU', '', $text), '<p><br><i><b><u><a>')), '"');
}

function zerofill($number, $length)
{
    return str_repeat('0', $length - strlen($number)) . (int) $number;
}

function coalesce()
{
    $args = func_get_args();
    for ($i=0, $cnt = func_num_args(); $i < $cnt; $i++)
    {
        if (!empty($args[$i]))
        {
            return $args[$i];
        }
    }
    return end($args);
}

function url_name($string, $allow_slash = false)
{
    return trim(preg_replace('/[^a-z0-9\-_' . ($allow_slash ? '\/' : '') . ']/', '', $string), '/');
}

function url_trailing_sign($url)
{
    return strstr($url, '?') ? '&' : '?';
}

function url_rm_vars($url, $varnames)
{
    if (!is_array($varnames))
    {
        $varnames = preg_split('/\s*,\s*/isx', $varnames);
    }

    foreach ($varnames as $var)
    {
        if (empty($var)) continue;
        $url = preg_replace("/(\?|&)$var=([^\?\&$]*)/isx", '', $url);
    }

    return $url;
}

function checkEmail($email)
{
    return preg_match('|^[_a-z0-9:()-]+(\.[_a-z0-9:()-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*$|i', $email);
}

function empty_array($array)
{
    foreach ($array as $k => $v)
    {
        if (is_array($v) && !empty_array($v)) return false;
        elseif (!empty($v)) return false;
    }

    return true;
}

function get_ymd($time, $month_format = 'm')
{
    return array(date('Y', $time), date($month_format, $time), date('d', $time));
}

function time_borders_ymd($year = 0, $month = 0, $day = 0)
{
    $time = false;

    if (!empty($day))
    {
        $time = mktime(1,1,1,$month,$day,$year);
        $mode = 'day';
    }
    else if (!empty($month))
    {
        $time = mktime(1,1,1,$month,1,$year);
        $mode = 'month';
    }
    else if (!empty($year))
    {
        $time = mktime(1,1,1,1,1,$year);
        $mode = 'year';
    }

    return $time ? time_borders($time, $mode) : false;
}

function count_time($show = false)
{
    static $start;

    if (!$show)
    {
        $start = getmicrotime();
    }
    else
    {
        dv(getmicrotime() - $start);
        $start = 0;
    }
}

function dmY2time($val, $separator = '.')
{
    list($d, $m, $Y) = explode($separator, $val);
    return mktime(0,0,0, $m, $d, $Y);
}

function get_microtime($stamp = '')
{
    if (empty($stamp)) $stamp = microtime();
    list($usec, $sec) = explode(" ", $stamp);
    return ((float)$usec + (float)$sec);
}

function dm($object)
{
    if (!is_object($object))
    {
        dv($object);
        return ;
    }

    dv(get_class($object) . ':');

    $methods = get_class_methods($object);
    sort($methods);
    dv($methods);
}

function array_split($array, $chunks = 2, $preserveKeys = false)
{
    $newArray = array_fill(0, $chunks, array());

    $chunkNum = 0;

    foreach ($array as $k => $v)
    {
        $key = $preserveKeys ? $k : count($newArray[$chunkNum]);
        $newArray[$chunkNum][$key] = $v;

        $chunkNum++;

        if ($chunkNum > $chunks - 1)
        {
            $chunkNum = 0;
        }
    }

    return $newArray;
}

function num_to_string($value, $_1, $_2, $_3, $return_value = true)
{
    if ($value > 10 && $value < 20)
    {
        $v = $_3;
    }
    else
    {
        $arr = preg_split('//', (string) $value, null, PREG_SPLIT_NO_EMPTY);
        $last = end($arr);

        if ($last == 1)
        {
            $v = $_1;
        }
        else if ($last > 1 && $last < 5)
        {
            $v = $_2;
        }
        else
        {
            $v = $_3;
        }
    }

    return $return_value ? (int) $value . " $v" : $v;
}

function russian_date()
{
    $translation = array(
        "am" => "дп",
        "pm" => "пп",
        "AM" => "ДП",
        "PM" => "ПП",
        "Monday" => "Понедельник",
        "Mon" => "Пн",
        "Tuesday" => "Вторник",
        "Tue" => "Вт",
        "Wednesday" => "Среда",
        "Wed" => "Ср",
        "Thursday" => "Четверг",
        "Thu" => "Чт",
        "Friday" => "Пятница",
        "Fri" => "Пт",
        "Saturday" => "Суббота",
        "Sat" => "Сб",
        "Sunday" => "Воскресенье",
        "Sun" => "Вс",
        "January" => "января",
        "Jan" => "янв",
        "February" => "февраля",
        "Feb" => "фев",
        "March" => "марта",
        "Mar" => "мар",
        "April" => "апреля",
        "Apr" => "апр",
        "May" => "мая",
        "May" => "мая",
        "June" => "июня",
        "Jun" => "июн",
        "July" => "июля",
        "Jul" => "июл",
        "August" => "августа",
        "Aug" => "авг",
        "September" => "сентября",
        "Sep" => "сен",
        "October" => "октября",
        "Oct" => "окт",
        "November" => "ноября",
        "Nov" => "ноя",
        "December" => "декабря",
        "Dec" => "дек",
        "st" => "ое",
        "nd" => "ое",
        "rd" => "е",
        "th" => "ое",
    );

    if (func_num_args() > 1)
    {
        $timestamp = func_get_arg(1);
        return strtr(date(func_get_arg(0), $timestamp), $translation);
    }
    else
    {
        return strtr(date(func_get_arg(0)), $translation);
    }
}

function lang_date_nominative()
{
    switch (func_get_arg(2)) {
        case 'EN':
            $translation = array();
            break;

        case 'PL':
            $translation = array(
                "January" => "Styczeń",
                "February" => "Luty",
                "March" => "Marzec",
                "April" => "Kwiecień",
                "May" => "Maj",
                "June" => "Czerwiec",
                "July" => "Lipiec",
                "August" => "Sierpień",
                "September" => "Wrzesień",
                "October" => "Październik",
                "November" => "Listopad",
                "December" => "Grudzień"
            );
            break;

        default:
            $translation = array(
                "January" => "Январь",
                "February" => "Февраль",
                "March" => "Март",
                "April" => "Апрель",
                "May" => "Май",
                "June" => "Июнь",
                "July" => "Июль",
                "August" => "Август",
                "September" => "Сентябрь",
                "October" => "Октябрь",
                "November" => "Ноябрь",
                "December" => "Декабрь"
            );
    }

    if (func_num_args() > 1)
    {
        $timestamp = func_get_arg(1);
        return strtr(date(func_get_arg(0), $timestamp), $translation);
    }
    else
    {
        return strtr(date(func_get_arg(0)), $translation);
    }
}

function space2br($text)
{
    return preg_replace('/\s+/', '<br/>', $text);
}

function is_ajax_request()
{
    return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || isset($_REQUEST['__ajax__']);
}

function fix_url($url, $add_http = true)
{
    $url = str_replace('http://', '', $url);
    $url = trim(preg_replace('|/+|', '/', $url), '/');

    return $add_http ? "http://$url/" : $url;
}