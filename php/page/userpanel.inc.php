<?php
/*
 * *******************************************************************************************
 * @author:  Oliver Kaufmann (Kyri123)
 * @copyright Copyright (c) 2019-2020, Oliver Kaufmann
 * @license MIT License (LICENSE or https://github.com/Kyri123/Arkadmin/blob/master/LICENSE)
 * Github: https://github.com/Kyri123/Arkadmin
 * *******************************************************************************************
*/

// Prüfe Rechte wenn nicht wird die seite nicht gefunden!
if(!$user->perm("userpanel/userpanel")) {
    header("Location: /404"); exit;
}

// Vars
$tpl_dir = 'app/template/core/userpanel/';
$tpl_dir_all = 'app/template/all/';
$setsidebar = false;
$cfglist = null;
$pagename = "{::lang::php::userpanel::pagename}";
$urltop = "<li class=\"breadcrumb-item\">$pagename</li>";

//tpl
$tpl = new Template('tpl.htm', $tpl_dir);
$tpl->load();

// Code hinzufügen
if (isset($_POST["add"]) && $user->perm("userpanel/create_code")) {
    $code = rndbit(10);
    $rank = $_POST["rank"];

    if(is_numeric($rank)) {
        $query = "INSERT INTO `ArkAdmin_reg_code` (`code`, `used`, `time`) VALUES ('$code', '0', '$rank')";
        if ($mycon->query($query)) {
            $alert->code = 100;
            $alert->overwrite_text = '<div class="input-group m"><input type="text" class="form-control rounded-0" readonly="true" value="'.$code.'" id="'.$code.'"><span class="input-group-append"><button onclick="copythis(\''.$code.'\')" class="btn btn-primary btn-flat"><i class="fas fa-copy" aria-hidden="true"></i></button></span></div>';
            $resp = $alert->re();
        } else {
            $resp = $alert->rd(3);
        }
    }
    else {
        $resp = $alert->rd(2);
    }
}
elseif(isset($_POST["add"]))  {
    $resp = $alert->rd(99);
}

// Code löschen
if (isset($url[3]) && $url[2] == "rmcode" && $user->perm("userpanel/delete_code")) {
    $id = $url[3];
    $query = "DELETE FROM `ArkAdmin_reg_code` WHERE (`id`='".$id."')";
    if ($mycon->query($query)) {
        $alert->code = 101;
        $alert->overwrite_text = '{::lang::php::userpanel::removed_code}';
        $resp = $alert->re();
    } else {
        $alert->code = 3;
        $resp = $alert->re();
    }
}
elseif (isset($url[3]) && $url[2] == "rmcode") {
    $resp = $alert->rd(99);
}

// Benutzer löschen
if (isset($_POST["del"]) && $user->perm("userpanel/delete_user")) {
    $id = $_POST["userid"];
    $user->setid($id);
    $tpl->r("del_username", $user->read("username"));
    $query = "DELETE FROM `ArkAdmin_users` WHERE (`id`='".$id."')";
    if ($mycon->query($query)) {
        $alert->code = 101;
        $alert->overwrite_text = '{::lang::php::userpanel::removed_user}';
        $resp = $alert->re();
    } else {
        $alert->code = 3;
        $resp = $alert->re();
    }
}
elseif (isset($_POST["del"])) {
    $resp = $alert->rd(99);
}

// Benutzer (ent-)bannen
if (isset($url[4]) && $url[2] == "tban" && $user->perm("userpanel/delete_ban")) {
    $uid = $url[3];
    $set = $url[4];
    if ($set == 0) {
        $to = "{::lang::php::userpanel::banned}";
    } else {
        $to = "{::lang::php::userpanel::notbanned}";
    }
    $user->setid($uid);
    $tpl->r("ban_username", $user->read("username"));
    $tpl->r("ban_uid", $uid);
    $tpl->r("ban_to", $to);
    $query = "UPDATE `ArkAdmin_users` SET `ban`='".$set."' WHERE (`id`='".$uid."')";
    if ($mycon->query($query)) {
        $alert->code = 101;
        $alert->overwrite_text = '{::lang::php::userpanel::changed_ban}';
        $resp = $alert->re();
     } else {
        $alert->code = 3;
        $resp = $alert->re();
    }
}
elseif (isset($url[4]) && $url[2] == "tban") {
    $resp = $alert->rd(99);
}

// Benutzer Liste
$query = 'SELECT * FROM `ArkAdmin_users`';
$mycon->query($query);
$userarray = $mycon->fetchAll();
$dir = dirToArray('remote/arkmanager/instances/');
$userlist = null; $userlist_modal = null;
for ($i=1;$i<count($userarray);$i++) {
    $id = $userarray[$i]["id"];
    $username = $userarray[$i]["username"];
    $email = $userarray[$i]["email"];
    $lastlogin = $userarray[$i]["lastlogin"];
    $registerdate = $userarray[$i]["registerdate"];
    $rang = $userarray[$i]["rang"];
    $ban = $userarray[$i]["ban"];
    $kuser = new userclass($id);



    // Kein Modal
    $list = new Template("list.htm", $tpl_dir);
    $list->load();

    if ($ban < 1) {
        $list->rif ("ifban", false);
    } else {
        $list->rif ("ifban", true);
    }

    $list->r("regdate", converttime($registerdate));
    $list->r("lastlogin", converttime($lastlogin));
    $list->r("email", $email);
    $list->r("uid", $id);
    $list->r("rank", "<span class='text-".((!$kuser->perm("allg/is_admin")) ? "success" : "danger")."'>{::lang::php::userpanel::".((!$kuser->perm("allg/is_admin")) ? "user" : "admin")."}</span>");
    $list->r("username", $username);

    $list->rif ("ifmodal", false);
    $list->rif ("self", ($id == $_SESSION["id"]));
    $userlist .= $list->load_var();

    // Modal
    $list = new Template("list.htm", $tpl_dir);
    $list->load();

    $list->r("username", $username);
    $list->r("uid", $id);

    $list->rif ("ifmodal", true);
    $userlist_modal .= $list->load_var();
}

// Liste Codes auf
$list_codes = null;
if($user->perm("userpanel/show_codes")) {
    $query = 'SELECT * FROM `ArkAdmin_reg_code` WHERE `used` = \'0\'';
    $mycon->query($query);
    $codearray = $mycon->fetchAll();
    if (count($codearray)>0) {
        for ($i=0;$i<count($codearray);$i++) {
            $list = new Template("codes.htm", $tpl_dir);
            $list->load();
            $list->r("rank", "<span class='text-".(($codearray[$i]["time"] == 0) ? "success" : "danger")."'>{::lang::php::userpanel::".(($codearray[$i]["time"] == 0) ? "user" : "admin")."}</span>");
            $list->r("code", $codearray[$i]["code"]);
            $list->r("id", $codearray[$i]["id"]);
            $list->rif ("ifemtpy", false);

            $list_codes .= $list->load_var();
        }
    } else {
        $list = new Template("codes.htm", $tpl_dir);
        $list->load();
        $list->r("code", "{::lang::php::userpanel::nocodefound}");
        $list->rif ("ifemtpy", true);

        $list_codes .= $list->load_var();
    }
}

// lade in TPL
$tpl->r("list", $userlist);
$tpl->r("list_modal", $userlist_modal);
$tpl->r("list_codes", $list_codes);
$tpl->r("resp", $resp);
$pageicon = "<i class=\"fa fa-users\" aria-hidden=\"true\"></i>";
$content = $tpl->load_var();
$btns = '<a href="#" class="btn btn-success btn-icon-split rounded-0" data-toggle="modal" data-target="#addserver">
            <span class="icon text-white-50">
                <i class="fas fa-plus" aria-hidden="true"></i>
            </span>
            <span class="text">{::lang::php::userpanel::btn-regcode}</span>
        </a>';
?>