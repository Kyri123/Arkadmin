<?php
/*
 * *******************************************************************************************
 * @author:  Oliver Kaufmann (Kyri123)
 * @copyright Copyright (c) 2019-2020, Oliver Kaufmann
 * @license MIT License (LICENSE or https://github.com/Kyri123/Arkadmin/blob/master/LICENSE)
 * Github: https://github.com/Kyri123/Arkadmin
 * *******************************************************************************************
*/

$pagename = '{::lang::php::sc::page::jobs::pagename}';
$page_tpl = new Template('jobs.htm', 'app/template/sub/serv/');
$page_tpl->load();
$page_tpl->debug(true);
$urltop = '<li class="breadcrumb-item"><a href="/servercenter/'.$url[2].'/home">'.$serv->cfg_read('ark_SessionName').'</a></li>';
$urltop .= '<li class="breadcrumb-item">{::lang::php::sc::page::jobs::urltop}</li>';

$user = new userclass();
$user->setid($_SESSION['id']);
$page_tpl->r('cfg' ,$url[2]);
$page_tpl->r('SESSION_USERNAME' ,$user->name());

// Cronjobs aktualisieren (AutoUpdate/AutoBackup)
if (isset($_POST['set'])) {
    $key = $_POST['key'];
    $intervall = $_POST['intervall'];
    $datetime = $_POST['time'];
    $datetime = strtotime($datetime);
    $json = $helper->file_to_json($cpath, true);
    if ($intervall != null && is_numeric($intervall) && $intervall > 0) {
        if ($datetime != null) {
            $json['option'][$key]['active'] = $_POST['active'];
            $json['option'][$key]['datetime'] = $datetime;
            $json['option'][$key]['intervall'] = $intervall;
            $json['option'][$key]['para'] = $_POST['parameter'];
            if ($helper->savejson_exsists($json, $cpath)) {
                $alert->code = 102;
                $resp = $alert->re();
            } else {
                $alert->code = 1;
                $resp = $alert->re();
            }
        } else {
            $alert->code = 15;
            $resp = $alert->re();
        }
    } else {
        $alert->code = 14;
        $resp = $alert->re();
    }
}

// Cronjob erstellen
if (isset($_POST['addjob'])) {
    $name = $_POST['name'];
    $action = $_POST['action'];
    $parameter = $_POST['parameter'];
    $intervall = $_POST['intervall'];
    $datetime = $_POST['time'];
    $datetime = strtotime($datetime);
    if ($name != null) {
        if ($action != null) {
            if ($intervall != null && is_numeric($intervall) && $intervall > 0) {
                if ($datetime != null) {
                    $query = "INSERT INTO `ArkAdmin_jobs` 
                    (
                        `job`, 
                        `parm`, 
                        `time`, 
                        `intervall`, 
                        `active`, 
                        `server`, 
                        `name`
                    ) VALUES (
                        '$action', 
                        '$parameter', 
                        '$datetime', 
                        '$intervall', 
                        '1',  
                        '".$serv->name()."',
                        '$name'
                    )";
                    if ($mycon->query($query)) {
                        $alert->code = 100;
                        $resp = $alert->re();
                    }
                    else {
                        $alert->code = 3;
                        $resp = $alert->re();
                    }
                }
                else {
                    $alert->code = 15;
                    $resp = $alert->re();
                }
            } else {
                $alert->code = 14;
                $resp = $alert->re();
            }
        } else {
            $alert->code = 2;
            $resp = $alert->re();
        }
    } else {
        $alert->code = 2;
        $resp = $alert->re();
    }
}

// Cronjob erstellen
if (isset($_POST['edit'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $action = $_POST['action'];
    $parameter = $_POST['parameter'];
    $intervall = $_POST['intervall'];
    $datetime = $_POST['time'];
    $datetime = strtotime($datetime);
    if ($name != null) {
        if ($action != null) {
            if ($intervall != null && is_numeric($intervall) && $intervall > 0) {
                if ($datetime != null) {
                    $query = "UPDATE `ArkAdmin_jobs` SET 
                        `job` = '$action', 
                        `parm` = '$parameter', 
                        `time` = '$datetime', 
                        `intervall` = '$intervall', 
                        `name` = '$name'
                    WHERE `id` = '$id';";
                    if ($mycon->query($query)) {
                        $alert->code = 102;
                        $resp = $alert->re();
                    }
                    else {
                        $alert->code = 4;
                        $resp = $alert->re();
                    }
                }
                else {
                    $alert->code = 15;
                    $resp = $alert->re();
                }
            } else {
                $alert->code = 14;
                $resp = $alert->re();
            }
        } else {
            $alert->code = 2;
            $resp = $alert->re();
        }
    } else {
        $alert->code = 2;
        $resp = $alert->re();
    }
}

//Remove Jobs
if (isset($url[4]) && isset($url[5]) && $url[4] == "delete") {
    $i = $url[5];
    $i = intval($i);
    $query = 'SELECT * FROM `ArkAdmin_jobs` WHERE `id` = \''.$i.'\'';
    if($mycon->query($query)->numRows() > 0) {
        $query = 'DELETE FROM `ArkAdmin_jobs` WHERE `id` = \''.$i.'\'';
        if ($mycon->query($query)) {
            $alert->code = 101;
            $resp = $alert->re();
        } else {
            $alert->code = 1;
            $resp = $alert->re();
        }
    } else {
        $alert->code = 16;
        $resp = $alert->re();
    }
}


//Erstelle Grund Jobs
if (isset($url[4]) && isset($url[5]) && $url[4] == "create") {
    echo 1;
    $type = $url[5];
    $i = intval($i);
    if(($type == "update" || $type == "backup")) {
        $query = ($type == "update") ? "INSERT INTO `ArkAdmin_jobs` 
        (
            `job`, 
            `parm`, 
            `time`, 
            `intervall`, 
            `active`, 
            `server`, 
            `name`
        ) VALUES (
            'update', 
            '--update-mods --warn --saveworld', 
            '".time()."', 
            '1800', 
            '1',  
            '".$serv->name()."',
            'Auto Update'
        )" : 
        "INSERT INTO `ArkAdmin_jobs` 
        (
            `job`, 
            `parm`, 
            `time`, 
            `intervall`, 
            `active`, 
            `server`, 
            `name`
        ) VALUES (
            'backup', 
            '--allmaps', 
            '".time()."', 
            '1800', 
            '1',  
            '".$serv->name()."',
            'Auto Backup'
        )";
        if ($mycon->query($query)) {
            header("location: /servercenter/".$serv->name()."/jobs/");
            exit;
        }
        else {
            $alert->code = 3;
            $resp = $alert->re();
        }
    }
}


if (isset($url[4]) && isset($url[5]) && $url[4] == "toggle") {
    $i = $url[5];
    $i = intval($i);
    $query = 'SELECT * FROM `ArkAdmin_jobs` WHERE `id` = \''.$i.'\'';
    if($mycon->query($query)->numRows() > 0) {
        $arr = $mycon->query($query)->fetchArray();
        if ($arr['active'] == 1) {
            $set = 0;
            $txt = "{::lang::php::sc::page::jobs::job_active}";
        } else {
            $set = 1;
            $txt = "{::lang::php::sc::page::jobs::job_disturb}";
        }

        $query = 'UPDATE `ArkAdmin_jobs` SET `active` = \''.$set.'\' WHERE `id` = \''.$i.'\'';
        if ($mycon->query($query)) {
            $alert->code = 100;
            $alert->overwrite_text = $txt;
            $resp = $alert->re();
        } else {
            $alert->code = 1;
            $resp = $alert->re();
        }
    } else {
        $alert->code = 16;
        $resp = $alert->re();
    }
}

$commands = array("start", "restart", "stop", "installmods", "uninstallmods", "saveworld", "update", "backup");
$json = $jobs = $jobs_modal = null;
$query = 'SELECT * FROM `ArkAdmin_jobs` WHERE `server` = \''.$serv->name().'\'';
if($mycon->query($query)->numRows() > 0) {
    $json = $mycon->query($query)->fetchAll();
    foreach($json as $key => $value) {
        $list = new Template('jobs.htm', 'app/template/lists/serv/jobs/');
        $list->load();
        $list->rif ('empty', true);
        if ($value['active'] == 0) {
            $toggle_icon = 'fa fa-check';
            $toggle_btn_color = 'success';
            $toggle_icon_color = 'danger';
            $toggle_tooltip = '{::lang::php::sc::page::jobs::tool_active}';
        } else {
            $toggle_icon = 'fa fa-times';
            $toggle_btn_color = 'danger';
            $toggle_icon_color = 'success';
            $toggle_tooltip = '{::lang::php::sc::page::jobs::tool_disturb}';
        }

        for($i=0;$i<count($commands);$i++) $list->r("__".$commands[$i], (($commands[$i] == $value['job'] ? "selected" : null)));

        // List
        $list->r('toggle_tooltip', $toggle_tooltip);
        $list->r('toggle_icon', $toggle_icon);
        $list->r('toggle_btn_color', $toggle_btn_color);
        $list->r('toggle_icon_color', $toggle_icon_color);
        $list->r('title', $value['name']);
        $list->r('action', $value['job']);
        $list->r('parameter', $value['parm']);
        $list->r('intervall', $value['intervall']);
        $list->r('cfg', $serv->name());
        $list->r('i', $value["id"]);
        $list->r('rnd', md5($value["id"]));
        $list->r('datetime', date('d.m.Y - H:i', $value['time']));
        $list->r('datetime_edit', date('Y-m-d H:i', $value['time']));
        $list->rif('modal', false);
        $list->rif('update', ($value['job'] == "update") ? true : false);
        $list->rif('backup', ($value['job'] == "backup") ? true : false);
        $jobs .= $list->load_var();
    } 
} 


$query_backup = 'SELECT * FROM `ArkAdmin_jobs` WHERE `server` = \''.$serv->name().'\' AND `job` = \'backup\'';
$query_update = 'SELECT * FROM `ArkAdmin_jobs` WHERE `server` = \''.$serv->name().'\' AND `job` = \'update\'';

$page_tpl->rif("update_btn", ($mycon->query($query_update)->numRows() == 0) ? true : false);
$page_tpl->rif("backup_btn", ($mycon->query($query_backup)->numRows() == 0) ? true : false);
$page_tpl->r('update_url', "/servercenter/".$serv->name()."/jobs/create/update/");
$page_tpl->r('backup_url', "/servercenter/".$serv->name()."/jobs/create/backup/");
$page_tpl->r('listmodal', $jobs_modal);
$page_tpl->r('listmodal', $jobs_modal);
$page_tpl->r('list', $jobs);
$page_tpl->session();
$panel = $page_tpl->load_var();


?>
