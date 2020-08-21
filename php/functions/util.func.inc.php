<?php
/*
 * *******************************************************************************************
 * @author:  Oliver Kaufmann (Kyri123)
 * @copyright Copyright (c) 2019-2020, Oliver Kaufmann
 * @license MIT License (LICENSE or https://github.com/Kyri123/Arkadmin/blob/master/LICENSE)
 * Github: https://github.com/Kyri123/Arkadmin
 * *******************************************************************************************
*/

/**
 * Setzte Icon von Dateien
 *
 * @param  mixed $target
 * @param  mixed $ico
 * @return string
 */

function setico($target, $ico = null) {
    if(is_dir($target)) {
        $ico = '<i class="nav-icon fas fa-folder-open" aria-hidden="true"></i>';
    }
    else {
        $type = pathinfo($target)['extension'];
        if($type == "sh" || $type == "ini") {
            $ico = '<i class="nav-icon fa fa-file-code-o" aria-hidden="true"></i>';
        }
        elseif($type == "txt" || $type == "log") {
            $ico = '<i class="nav-icon fa fa-file-text-o" aria-hidden="true"></i>';
        }
        elseif($type == "ark" || $type == "bak") {
            $ico = '<i class="nav-icon fa fa-map-o" aria-hidden="true"></i>';
        }
        elseif($type == "arkprofile" || $type == "profilebak") {
            $ico = '<i class="nav-icon fa fa-user" aria-hidden="true"></i>';
        }
        elseif($type == "tribebak" || $type == "arktributetribe" || $type == "arktribe") {
            $ico = '<i class="nav-icon fa fa-users" aria-hidden="true"></i>';
        }
        elseif($type == "pnt") {
            $ico = '<i class="nav-icon fa fa-file-image-o" aria-hidden="true"></i>';
        }
        else {
            $ico = '<i class="nav-icon fa fa-file-o" aria-hidden="true"></i>';
        }
    }
    return $ico;
}

/**
 * Wandelt serverstatus in String (ML)
 *
 * @param  mixed $serverstate
 * @return array
 */

function convertstate($serverstate) {
    if ($serverstate == 0) {
        $serv_state = "{::lang::php::function_allg::state_off}";
        $serv_color = "danger";
    }
    elseif ($serverstate == 1) {
        $serv_state = "{::lang::php::function_allg::state_start}";
        $serv_color = "info";
    }
    elseif ($serverstate == 2) {
        $serv_state = "{::lang::php::function_allg::state_on}";
        $serv_color = "success";
    }
    elseif ($serverstate == 3) {
        $serv_state = "{::lang::php::function_allg::state_notinstalled}";
        $serv_color = "warning";
    }
    return array("color" => $serv_color,"str" => $serv_state);
}

/**
 * Zufällige String reihenfolge
 *
 * @param  int $l
 * @return bool
 */

function rndbit($l) {
    return bin2hex(random_bytes($l));
}

/**
 * Löscht das verzeichnis Rekursiv
 *
 * @param  mixed $dir
 * @return bool
 */

function del_dir($dir) {
    if(is_dir($dir))
    {
        $dir_handle = opendir($dir);
        if($dir_handle) {
            while($file = readdir($dir_handle)) {
                if ($file != "." && $file != "..") {
                    if (!is_dir($dir."/".$file))
                    {
                        unlink( $dir."/".$file );
                    } else {
                        unlink($dir.'/'.$file);
                    }
                }
            }
            closedir($dir_handle);
        }
        rmdir($dir);
        return true;
    }
    return false;
}

/**
 * Rechnet bit in gewünschten Format um
 *
 * @param  mixed $size
 * @param  mixed $sourceUnit Quelle bit | B | KB | MK | GB
 * @param  mixed $targetUnit Ausgabe bit | B | KB | MK | GB
 * @return string
 */

function bitrechner( $size, $sourceUnit = 'bit', $targetUnit = 'MB' ) {
    $units = array(
        'bit' => 0,
        'B' => 1,
        'KB' => 2,
        'MB' => 3,
        'GB' => 4
    );

    if ( $units[$sourceUnit] <= $units[$targetUnit] ) {
        for ( $i = $units[$sourceUnit]; $size >= 1024; $i++ ) {
            if ( $i === 0 ) {
                $size /= 8;
            } else {
                $size /= 1024;
            }
        }
    } else {
        for ( $i = $units[$sourceUnit]; $i > $units[$targetUnit]; $i-- ) {
            if ( $i === 1 ) {
                $size *= 8;
            } else {
                $size *= 1024;
            }
        }
    }
    return round( $size, 2 ) . ' ' . array_keys($units)[$i];
}

/**
 * Schaut ob im Array ein String gefunden wird
 *
 * @param  string|int $haystack
 * @param  array $array
 * @return bool
 */

function strpos_arr($haystack, array $array)
{
    $bool = false;
    foreach($array as $str) {
        if(!is_array($str)) {
            if(strpos($haystack, $str) === false) {
                $bool = false;
            } else {
                $bool = true;
                break;
            }
        }
    }
    return $bool;
}

/**
 * Wandelt timestamp in String um
 *
 * @param  mixed $stamp
 * @param  mixed $withsec
 * @param  mixed $onlydate
 * @return string
 */

function converttime($stamp, $withsec = false, $onlydate = false) {
    if ($withsec) return date("d.m.Y H:i:s", $stamp);
    if ($onlydate) return date("d.m.Y", $stamp);
    return date("d.m.Y H:i", $stamp);
}

/**
 * Wandelt ein Verzeichnis Rekursiv in ein array
 *
 * @param  mixed $dir
 * @return array
 */

function dirToArray($dir) {

    $result = array();

    $cdir = scandir($dir);
    foreach ($cdir as $key => $value)
    {
        if (!in_array($value,array(".","..")))
        {
            if (is_dir($dir . DIRECTORY_SEPARATOR . $value))
            {
                $result[$value] = dirToArray($dir . DIRECTORY_SEPARATOR . $value);
            }
            else
            {
                $result[] = $value;
            }
        }
    }

    return $result;
}

/**
 * Fileter \r \t
 *
 * @param  mixed $str
 * @return void
 */

function ini_save_rdy($str) {
    $str = str_replace("\r", null, $str);
    $str = str_replace("\t", null, $str);
    return $str;
}

/**
 * Differnz von wann eine Datei erstellt wurde
 *
 * @param  mixed $file
 * @param  mixed $diff
 * @return void
 */

function timediff(String $file, Int $diff) {
    if($file == "" || $file == null || !file_exists($file)) return -1;
    $filetime = filemtime($file);
    $differnz = time()-$filetime;
    return ($differnz > $diff);
}

/**
 * Berechnung von %
 *
 * @param  mixed $curr
 * @param  mixed $max
 * @return int
 */

function perc($curr, $max) {
    return ($curr / $max * 100);
}

?>