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
$adminlist_admin = null;

$page_tpl->r('cfg' ,$serv->name());
$page_tpl->r('SESSION_USERNAME' ,$user->name());

// Erstelle Dateien wenn die nicht exsistieren
$cheatfile = $serv->dir_save(true)."/AllowedCheaterSteamIDs.txt";
$whitelistfile = $serv->dir_main()."/ShooterGame/Binaries/Linux/PlayersJoinNoCheckList.txt";
if(!file_exists($cheatfile)) file_put_contents($cheatfile, " ");
if(!file_exists($whitelistfile) && file_exists($serv->dir_main()."/ShooterGame/Binaries/Linux/")) file_put_contents($whitelistfile, " ");

$playerjs = $helper->file_to_json('app/json/steamapi/profile_savegames_'.$serv->name().'.json', true)["response"]["players"];
$count = (is_countable($playerjs)) ? count($playerjs): false;

// Administrator hinzufügen
if (isset($_POST["addadmin"])) {
    $id = $_POST["id"];
    // SteamID bzw Input prüfen
    if(is_numeric($id) && $id > 700000000) {
        for ($ix=0;$ix<$count;$ix++) if($id == $playerjs[$ix]["steamid"]) {$i = $ix; break;};
        $content = file_get_contents($cheatfile)."\n$id";
        if (file_put_contents($cheatfile, $content)) {
            // Melde: Abschluss (Hinzugefügt)
            $alert->code = 100;
            $alert->r("name", strval($playerjs[$i]["personaname"]));
            $alert->overwrite_text = "{::lang::php::sc::page::home::add_admin}";
            $resp = $alert->re();
        } else {
            // Melde: Schreib/Lese Fehler
            $alert->code = 1;
            $resp = $alert->re();
        }
    } else {
        // Melde: Input Fehler
        $alert->code = 2;
        $resp = $alert->re();
    }
}

// Entfernte von Adminliste
if (isset($url[4]) && isset($url[5]) && $url[4] == 'rm') {
    $id = $url[5];
    for ($ix=0;$ix<$count;$ix++) if($id == $playerjs[$ix]["steamid"]) {$i = $ix; break;};
    $content = file_get_contents($cheatfile);
    // Prüfe ob die ID exsistent ist
    if (substr_count($content, $id) > 0) {
        $content = str_replace($id, null, $content);
        if (file_put_contents($cheatfile, $content)) {
            // Melde: Erfolgreich
            $alert->code = 101;
            $alert->r("name", $playerjs[$i]["personaname"]);
            $alert->overwrite_text = "{::lang::php::sc::page::home::remove_admin}";
            $resp = $alert->re();
        } else {
            // Melde: Lese/Schreib Fehler
            $alert->code = 1;
            $resp = $alert->re();
        }
    }
}


$serv->cfg_read('arkserverroot');
$savedir = $serv->dir_save();
$player_json = $helper->file_to_json('app/json/saves/player_'.$serv->name().'.json', false);
$tribe_json = $helper->file_to_json('app/json/saves/tribes_'.$serv->name().'.json', false);
if (!is_array($player_json)) $player_json = array();
if (!is_array($tribe_json)) $tribe_json = array();

// Liste Admins auf
if ($serv->isinstalled()) {
    if (!file_exists($cheatfile)) file_put_contents($cheatfile, "");
    $jhelper = new player_json_helper();
    $userlist_admin = null;
    $player_json = $helper->file_to_json('app/json/saves/player_'.$serv->name().'.json', false);
    if (!is_array($player_json)) $player_json = array();

    $file = file($cheatfile);

    for ($i=0;$i<count($file);$i++) {
        $find = array("\n", "\r", " ");
        $file[$i] = str_replace($find, null, $file[$i]);
        if (is_numeric($file[$i])) {
            $list_tpl = new Template('user_admin.htm', 'app/template/lists/serv/home/');
            $list_tpl->load();

            $found = false;
            for ($p=0;$p<count($player_json);$p++) {
                $pl = $jhelper->player($player_json, $p);
                $id = $pl->SteamId;
                if ($id === $file[$i]) {
                    $found = true;
                    break;
                }
            }

            if ($found) {
                for ($ix=0;$ix<$count;$ix++) if($id == $playerjs[$ix]["steamid"]) {$ix = $ix; break;};
                $list_tpl->r("igname", $pl->CharacterName);
            } else {
                $list_tpl->r("igname", "{::lang::php::sc::page::home::unknown_name}");
            }

            $list_tpl->r("stid", $playerjs[$ix]["steamid"]);
            $list_tpl->r("url", $playerjs[$ix]["profileurl"]);
            $list_tpl->r("cfg", $serv->name());
            $list_tpl->r("rndb", rndbit(25));
            $list_tpl->r("stname", $playerjs[$ix]["personaname"]);
            $list_tpl->r("img", $playerjs[$ix]["avatarmedium"]);

            $adminlist_admin .= $list_tpl->load_var();
        }
    }

    if (is_array($player_json)) {
        for ($i=0;$i<count($player_json);$i++) {
            $pl = $jhelper->player($player_json, $i);
            $id = $pl->SteamId;
            $name = $pl->SteamName;
            $ig_name = $pl->CharacterName;
            $not = true;
            for ($p=0;$p<count($file);$p++) {
                if (intval($file[$p]) == $id) {
                    $not = false;
                    break;
                }
            }

            if ($not) $userlist_admin .= "<option value='$id'>$name - $ig_name</option>";
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

$page_tpl->rif ("installed", $serv->isinstalled());
$page_tpl->rif ('ifwhitelist', $ifwhitelist);
$page_tpl->rif ('rcon', $serv->check_rcon());
$page_tpl->rif ('lchatactive', $lchatactive);
$page_tpl->rif ('whiteactive', $serv->cfg_check("arkflag_exclusivejoin"));
$page_tpl->r ('whiteactive_meld', $white_alert);
$page_tpl->r("userlist_admin", $userlist_admin);
$page_tpl->r("adminlist_admin", $adminlist_admin);
$page_tpl->r("whitelist_admin", $adminlist_admin);
$page_tpl->r("pick_whitelist", $serv->cfg_check("exclusivejoin"));
$page_tpl->session();
$panel = $page_tpl->load_var();

?>