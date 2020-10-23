<?php
/*
 * *******************************************************************************************
 * @author:  Oliver Kaufmann (Kyri123)
 * @copyright Copyright (c) 2019-2020, Oliver Kaufmann
 * @license MIT License (LICENSE or https://github.com/Kyri123/Arkadmin/blob/master/LICENSE)
 * Github: https://github.com/Kyri123/Arkadmin
 * *******************************************************************************************
*/

$pagename = '{::lang::php::sc::page::home::pagename}';
$page_tpl = new Template('home.htm', 'app/template/sub/serv/');
$page_tpl->load();
$urltop = '<li class="breadcrumb-item"><a href="/servercenter/'.$url[2].'/home">'.$serv->cfg_read('ark_SessionName').'</a></li>';
$urltop .= '<li class="breadcrumb-item">{::lang::php::sc::page::home::urltop}</li>';
$adminlist_admin = $userlist_admin = null;

$page_tpl->r('cfg' ,$serv->name());
$page_tpl->r('SESSION_USERNAME' ,$user->read("username"));

// Erstelle Dateien wenn die nicht exsistieren
$cheatfile = $serv->dir_save(true)."/AllowedCheaterSteamIDs.txt";
$whitelistfile = $serv->dir_main()."/ShooterGame/Binaries/Linux/PlayersJoinNoCheckList.txt";
if(!file_exists($cheatfile) && file_exists($serv->dir_main()."/ShooterGame/Binaries/Linux/")) file_put_contents($cheatfile, " ");
if(!file_exists($whitelistfile) && file_exists($serv->dir_main()."/ShooterGame/Binaries/Linux/")) file_put_contents($whitelistfile, " ");

$playerjson = $helper->file_to_json('app/json/steamapi/profile_allg.json', true);
$playerjs = isset($playerjson["response"]["players"]) ? $playerjson["response"]["players"] : [];
$count = (is_countable($playerjs)) ? count($playerjs): false;

// Administrator hinzufügen
if (isset($_POST["addadmin"]) && $user->perm("$perm/home/admin_send")) {
    $id = $_POST["id"];
    $cheatcontent = file_get_contents($cheatfile);

    // SteamID bzw Input prüfen
    if(is_numeric($id) && $id > 700000000) {
        if(!strpos($cheatcontent, $id)) {
            for ($ix=0;$ix<$count;$ix++) if($id == $playerjs[$ix]["steamid"]) {$i = $ix; break;};
            $content = file_get_contents($cheatfile)."\n$id";
            if (file_put_contents($cheatfile, $content)) {
                // Melde: Abschluss (Hinzugefügt)
                $alert->code = 100;
                $alert->r("name", isset($playerjs[$i]["personaname"]) ? strval($playerjs[$i]["personaname"]) : $id);
                $alert->overwrite_text = "{::lang::php::sc::page::home::add_admin}";
                $resp = $alert->re();
            } else {
                // Melde: Schreib/Lese Fehler
                $resp = $alert->rd(1);
            }
        }
        else {
            // Melde: Mutiple
            $resp = $alert->rd(5);
        }
    } else {
        // Melde: Input Fehler
        $resp = $alert->rd(2);
    }
}
elseif(isset($_POST["addadmin"])) {
    $resp = $alert->rd(99);
}

// Entfernte von Adminliste
if (isset($_POST["rm"]) && $user->perm("$perm/home/admin_send")) {
    $id = $_POST["stid"];
    $content = file_get_contents($cheatfile);
    // Prüfe ob die ID exsistent ist
    if (substr_count($content, $id) > 0) {
        $content = str_replace($id, null, $content);
        if (file_put_contents($cheatfile, $content)) {
            // Melde: Erfolgreich
            $resp = $alert->rd(101);
        } else {
            // Melde: Lese/Schreib Fehler
            $resp = $alert->rd(1);
        }
    }
}
elseif(isset($_POST["rm"])) {
    $resp = $alert->rd(99);
}


$serv->cfg_read('arkserverroot');
$savedir = $serv->dir_save();
$player_json = $helper->file_to_json('app/json/saves/player_'.$serv->name().'.json', false);
$tribe_json = $helper->file_to_json('app/json/saves/tribes_'.$serv->name().'.json', false);
if (!is_array($player_json)) $player_json = array();
if (!is_array($tribe_json)) $tribe_json = array();

// Liste Admins auf
if ($serv->isinstalled() && $user->perm("$perm/home/admin_show")) {

    if (!file_exists($cheatfile) && file_exists($serv->dir_save(true))) file_put_contents($cheatfile, "");
    if (file_exists($cheatfile)) {
        $file = file($cheatfile);
        $arr = [];

        if (is_array($file)) {
            for ($i = 0; $i < count($file); $i++) {
                $file[$i] = trim($file[$i]);
                if($file[$i] != "0" && $file[$i] != "" && $file[$i] != null) $arr[] = $file[$i];
            }
        }

        if(is_countable($arr) && is_array($arr) && count($arr) > 0) {
            for ($i=0;$i<count($arr);$i++) {
                $list_tpl = new Template('user_admin.htm', 'app/template/lists/serv/home/');
                $list_tpl->load();

                $query = "SELECT * FROM ArkAdmin_players WHERE `server`='".$serv->name()."' AND `SteamId`='".$arr[$i]."'";
                $query = $mycon->query($query);

                if($query->numRows() > 0) {
                    $row = $query->fetchArray();
                    $list_tpl->r("stname", $steamapi_user[$arr[$i]]["personaname"]);
                    $list_tpl->r("igname", $row["CharacterName"]);
                }
                else {
                    $list_tpl->r("stname", $steamapi_user[$arr[$i]]["personaname"]);
                    $list_tpl->r("igname", "{::lang::allg::default::noadmin}");
                }

                $list_tpl->r("stid", $steamapi_user[$arr[$i]]["steamid"]);
                $list_tpl->r("url", $steamapi_user[$arr[$i]]["profileurl"]);
                $list_tpl->r("cfg", $serv->name());
                $list_tpl->r("rndb", rndbit(25));
                $list_tpl->r("img", $steamapi_user[$arr[$i]]["avatarmedium"]);
                $list_tpl->rif("hidebtn", false);

                $adminlist_admin .= $list_tpl->load_var();
            }
        }
    }
    if($adminlist_admin == null) {
        $list_tpl = new Template('whitelist.htm', 'app/template/lists/serv/jquery/');
        $list_tpl->load();

        $list_tpl->r("sid", 0);
        $list_tpl->r("name", "{::lang::allg::default::noadmin}");
        $list_tpl->r("cfg", $serv->name());
        $list_tpl->r("rndb", rndbit(25));
        $list_tpl->r("img", "https://steamuserimages-a.akamaihd.net/ugc/885384897182110030/F095539864AC9E94AE5236E04C8CA7C2725BCEFF/");
        $list_tpl->rif("hidebtn", true);

        $adminlist_admin .= $list_tpl->load_var();
    }

    $query = "SELECT * FROM ArkAdmin_players WHERE `server`='".$serv->name()."'";
    $query = $mycon->query($query);

    if($query->numRows() > 0) {

        $row = $query->fetchAll();
        foreach ($row as $item) {
            if (!in_array($item["SteamId"], $arr)) $userlist_admin .= "<option value='". $item["SteamId"] ."'>". $steamapi_user[$item["SteamId"]]["personaname"] ." - ".$item["CharacterName"]."</option>";
        }

    }

} 

// Meldung wenn Clusterseitig Admin & Whitelist deaktiviert ist
if ($ifcadmin) $resp_cluster .= $alert->rd(300, 3);
if ($ifwhitelist) $resp_cluster .= $alert->rd(304, 3);
$lchatactive = true;
// Meldung wenn wegen fehlender Flagge Whitelist deaktiviert ist
if (
    !($serv->cfg_check("arkflag_servergamelog") &&
    $serv->cfg_check("arkflag_servergamelogincludetribelogs") &&
    $serv->cfg_check("arkflag_ServerRCONOutputTribeLogs") &&
    $serv->cfg_check("arkflag_logs"))
) {
    $resp_cluster .= $alert->rd(308, 3);
    $lchatactive = false;
}

$alert->code = 202;
$alert->overwrite_style = 3;
$alert->overwrite_mb = 0;
$white_alert = $alert->re();
$page_tpl->rif ("installed", $serv->isinstalled(true));
$page_tpl->rif ('ifwhitelist', $ifwhitelist);
$page_tpl->rif ('rcon', $serv->check_rcon());
$page_tpl->rif ('lchatactive', $lchatactive);
$page_tpl->rif ('whiteactive', $serv->cfg_check("arkflag_exclusivejoin"));
$page_tpl->r('lchatlog', $serv->dir_save(true).'/Logs/ServerPanel.log');
$page_tpl->r('rconlog', 'app/json/saves/rconlog_'.$serv->name().'.txt');
$page_tpl->r('whiteactive_meld', $white_alert);
$page_tpl->r("userlist_admin", $userlist_admin);
$page_tpl->r("adminlist_admin", $adminlist_admin);
$page_tpl->r("whitelist_admin", $adminlist_admin);
$page_tpl->r("pick_whitelist", $serv->cfg_check("exclusivejoin"));
$panel = $page_tpl->load_var();

