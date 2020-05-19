<?php
/*
 * *******************************************************************************************
 * @author:  Oliver Kaufmann (Kyri123)
 * @copyright Copyright (c) 2019-2020, Oliver Kaufmann
 * @license MIT License (LICENSE or https://github.com/Kyri123/Arkadmin/blob/master/LICENSE)
 * Github: https://github.com/Kyri123/Arkadmin
 * *******************************************************************************************
*/

function check_curl() {
    if  (in_array  ('curl', get_loaded_extensions())) {
    return true;
    } else {
    return false;
    }
}

function check_rew() {
    if ($file = file_get_contents('http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."/checkthis")) {
        if ($file == 1) {
            return true;
        } else {
            return false;
        }
    }
    return false;
}

function check_OS() {
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        return false;
    } else {
        return true;
    }
}

function check_arkmanager() {
    $cmd = "arkmanager";
    $return = shell_exec(sprintf("which %s", escapeshellarg($cmd)));
    return !empty($return);
}


?>