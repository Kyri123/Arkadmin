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
if(!$user->perm("cluster/show")) {
    header("Location: /401"); exit;
}

// Vars
$tpl_dir = 'app/template/core/cluster/';
$setsidebar = false;
$cfglist = null;
$pagename = "{::lang::php::cluster::pagename}";
$urltop = "<li class=\"breadcrumb-item\">$pagename</li>";

$tpl = new Template("tpl.htm", $tpl_dir);
$tpl->load();

$clusterjson_path = "app/json/panel/cluster_data.json";

// Hole Cluster Array / Json
if (!file_exists($clusterjson_path)) if (!file_put_contents($clusterjson_path, "[]")) die;
$json = $helper->file_to_json($clusterjson_path);

//Entferne Cluster
if (isset($url[3]) && $url[2] == "removecluster" && $user->perm("cluster/delete")) {
    $key = $url[3];
    if (isset($json[$key])) unset($json[$key]);
    $helper->savejson_exsists($json, $clusterjson_path);
    header("Location: /cluster"); exit;
}
elseif(isset($url[3]) && $url[2] == "removecluster") {
    $resp = $alert->rd(99);
}

// Entferne Server vom Cluster
if (isset($url[4]) && $url[2] == "remove" && $user->perm("cluster/remove_server")) {
    $key = $url[3];
    $cfg = $url[4];
    $array = array_column($json[$key]["servers"], 'server');
    foreach ($array as $k => $v) {
        if ($v == $cfg) {
            $i = $k; break;
        }
    }
    if (isset($json[$key]["servers"][$i])) unset($json[$key]["servers"][$i]);
    $helper->savejson_exsists($json, $clusterjson_path);
    header("Location: /cluster"); exit;
}
elseif(isset($url[4]) && $url[2] == "remove") {
    $resp = $alert->rd(99);
}

// Toggle Type vom Server (Master/Slave)
if (isset($url[5]) && $url[2] == "settype" && $user->perm("cluster/toogle_master")) {
    $key = $url[3];
    $cfgkey = $url[4];
    $to = $url[5];
    if ($to > 0) {
        $to = 1;
        $i = 0;
        $f = false;
        $array = array_column($json[$key]["servers"], 'type');
        foreach ($array as $k => $v) {
            if ($v == $to) {
                $i = $k; $f = true; break;
            }
        }
        if (!$f) {
            $json[$key]["servers"][$cfgkey]["type"] = 1;
        } else {
            $json[$key]["servers"][$i]["type"] = 0;
            $json[$key]["servers"][$cfgkey]["type"] = 1;
        }
    } else {
        $json[$key]["servers"][$cfgkey]["type"] = 0;
    }
    $helper->savejson_exsists($json, $clusterjson_path);
    header("Location: /cluster"); exit;
}
elseif(isset($url[5]) && $url[2] == "settype") {
    $resp = $alert->rd(99);
}

//Füge server zum Cluster hinzu
if (isset($_POST["addserver"]) && $user->perm("cluster/add_server")) {
    $key = $_POST["key"];
    $cfg = $_POST["server"];
    if ($cfg != "") {
        $no = true;
        foreach ($json as $mk => $mv) {
            if (array_search($cfg, array_column($json[$mk]["servers"], 'server')) !== FALSE) $no = false;
        }
        if ($no) {
            $i = count($json[$key]["servers"]);
            $cluster =  $json[$key]["name"];
            $json[$key]["servers"][$i]["server"] = $cfg;
            $json[$key]["servers"][$i]["type"] = 0;
            $server = new server($cfg);

            if ($helper->savejson_exsists($json, $clusterjson_path)) {
                // Melde: Server hinzugefpgt
                $alert->code = 104;
                $alert->overwrite_text = "{::lang::php::cluster::overwrite::addedserver}";
                $alert->r("servername", $server->cfg_read("ark_SessionName"));
                $alert->r("cluster", $cluster);
                $resp = $alert->re();
            } else {
                // Melde: Schreib/Lese Fehler
                $alert->code = 1;
                $resp = $alert->re();
            }
        } else {
            // Melde: Cluster Fehler
            $alert->code = 10;
            $resp = $alert->re();
        }
    } else {
        // Melde: Server nicht ausgewählt
        $alert->code = 9;
        $resp = $alert->re();
    }
}
elseif(isset($_POST["addserver"])) {
    $resp = $alert->rd(99);
}


// Editiere einen Cluster
if (isset($_POST["editcluster"]) && $user->perm("cluster/edit_options")) {
    //set vars
    $i = $_POST["key"];
    $cluster = $_POST["name"];
    $clustermd5 = md5($_POST["name"]);

    //sync opt
    $sync["admin"] = true; if (!isset($_POST["admin"])) $sync["admin"] = false;
    $sync["mods"] = true; if (!isset($_POST["mods"])) $sync["mods"] = false;
    $sync["konfig"] = true; if (!isset($_POST["konfig"])) $sync["konfig"] = false;
    $sync["whitelist"] = true; if (!isset($_POST["whitelist"])) $sync["whitelist"] = false;

    // options / rules
    $opt["NoTransferFromFiltering"] = true; if (!isset($_POST["NoTransferFromFiltering"])) $opt["NoTransferFromFiltering"] = false;
    $opt["NoTributeDownloads"] = true; if (!isset($_POST["NoTributeDownloads"])) $opt["NoTributeDownloads"] = false;
    $opt["PreventDownloadSurvivors"] = true; if (!isset($_POST["PreventDownloadSurvivors"])) $opt["PreventDownloadSurvivors"] = false;
    $opt["PreventUploadSurvivors"] = true; if (!isset($_POST["PreventUploadSurvivors"])) $opt["PreventUploadSurvivors"] = false;
    $opt["PreventDownloadItems"] = true; if (!isset($_POST["PreventDownloadItems"])) $opt["PreventDownloadItems"] = false;
    $opt["PreventUploadItems"] = true; if (!isset($_POST["PreventUploadItems"])) $opt["PreventUploadItems"] = false;
    $opt["PreventDownloadDinos"] = true; if (!isset($_POST["PreventDownloadDinos"])) $opt["PreventDownloadDinos"] = false;
    $opt["PreventUploadDinos"] = true; if (!isset($_POST["PreventUploadDinos"])) $opt["PreventUploadDinos"] = false;

    if ($cluster != null && ($clustermd5 == $json[$i]["clusterid"] || array_search($clustermd5, array_column($json, 'clusterid')) === FALSE)) {
        $json[$i]["name"] = $cluster;
        $json[$i]["clusterid"] = $clustermd5;
        $json[$i]["sync"] = $sync;
        $json[$i]["opt"] = $opt;

        if ($helper->savejson_exsists($json, $clusterjson_path)) {
            $alert->code = 104;
            $alert->overwrite_text = "{::lang::php::cluster::overwrite::changedcluster}";
            $alert->r("cluster", $cluster);
            $alert->r("clustermd5", $clustermd5);
            $resp = $alert->re();
        } else {
            $alert->code = 1;
            $resp = $alert->re();
        }
    } else {
        $alert->code = 11;
        $alert->r("input", $cluster);
        $resp = $alert->re();
    }
}
elseif(isset($_POST["editcluster"])) {
    $resp = $alert->rd(99);
}

// erstelle ein Cluster
if (isset($_POST["add"]) && $user->perm("cluster/create")) {
    //set vars
    $cluster = $_POST["name"];
    $clustermd5 = md5($_POST["name"]);

    //sync opt
    $sync["admin"] = true; if (!isset($_POST["admin"])) $sync["admin"] = false;
    $sync["mods"] = true; if (!isset($_POST["mods"])) $sync["mods"] = false;
    $sync["konfig"] = true; if (!isset($_POST["konfig"])) $sync["konfig"] = false;
    $sync["whitelist"] = true; if (!isset($_POST["whitelist"])) $sync["whitelist"] = false;

    // options / rules
    $opt["NoTransferFromFiltering"] = true; if (!isset($_POST["NoTransferFromFiltering"])) $opt["NoTransferFromFiltering"] = false;
    $opt["NoTributeDownloads"] = true; if (!isset($_POST["NoTributeDownloads"])) $opt["NoTributeDownloads"] = false;
    $opt["PreventDownloadSurvivors"] = true; if (!isset($_POST["PreventDownloadSurvivors"])) $opt["PreventDownloadSurvivors"] = false;
    $opt["PreventUploadSurvivors"] = true; if (!isset($_POST["PreventUploadSurvivors"])) $opt["PreventUploadSurvivors"] = false;
    $opt["PreventDownloadItems"] = true; if (!isset($_POST["PreventDownloadItems"])) $opt["PreventDownloadItems"] = false;
    $opt["PreventUploadItems"] = true; if (!isset($_POST["PreventUploadItems"])) $opt["PreventUploadItems"] = false;
    $opt["PreventDownloadDinos"] = true; if (!isset($_POST["PreventDownloadDinos"])) $opt["PreventDownloadDinos"] = false;
    $opt["PreventUploadDinos"] = true; if (!isset($_POST["PreventUploadDinos"])) $opt["PreventUploadDinos"] = false;

    if ($cluster != null && (count($json) < 1 || array_search($clustermd5, array_column($json, 'clusterid')) === FALSE)) {

        $clusterarray["name"] = $cluster;
        $clusterarray["clusterid"] = $clustermd5;
        $clusterarray["sync"] = $sync;
        $clusterarray["opt"] = $opt;
        $clusterarray["servers"] = array();

        if (array_push($json, $clusterarray)) {
            if ($helper->savejson_exsists($json, $clusterjson_path)) {
                $alert->code = 104;
                $alert->overwrite_text = "{::lang::php::cluster::overwrite::createdcluster}";
                $alert->r("cluster", $cluster);
                $alert->r("clustermd5", $clustermd5);
                $resp = $alert->re();
            } else {
                $alert->code = 1;
                $resp = $alert->re();
            }
        } else {
            $alert->code = 11;
            $alert->overwrite_title = "{::lang::alert::c_3::title_array}";
            $resp = $alert->re();
        }
    } else {
        $alert->code = 11;
        $alert->overwrite_text = "{::lang::alert::c_11::text} {::lang::alert::c_11::ornoinput}";
        $alert->r("input", $cluster);
        $resp = $alert->re();
    }
}
elseif(isset($_POST["add"])) {
    $resp = $alert->rd(99);
}

$i = 0;
foreach ($json as $mk => $mv) {
    $old = $json[$mk]["servers"];
    $json[$mk]["servers"] = array();
    foreach ($old as $k => $v) {
        $array["server"] = $v["server"];
        $array["type"] = $v["type"];
        array_push($json[$mk]["servers"], $array);
        $i++;
    }
}
$helper->savejson_exsists($json, $clusterjson_path);

$json = $helper->file_to_json($clusterjson_path);
$list = null;
foreach ($json as $mk => $mv) {
    $listtpl = new Template("clusters.htm", $tpl_dir);
    $listtpl->load();

    $serverlist = null;
    $alert_r = null;


    $count = 0; if (isset($json[$mk]["servers"])) $count = count($json[$mk]["servers"]);

    if ($count > 0) {
        $x = 0;
        foreach ($json[$mk]["servers"] as $key) {
            $listserv = new Template("_lists.htm", $tpl_dir);
            $listserv->load();
            $listserv->rif ("ifserver", true);
            $server = new server($key["server"]);
            $data = $server->status();

            $color_type = "green";
            $master = false;
            if ($key["type"] > 0) {
                $color_type = "blue";
                $master = true;
            }

            $listserv->rif ("ifmaster", $master);
            $listserv->r("servername", $server->cfg_read("ark_SessionName"));
            $listserv->r("cfg", $server->name());
            $listserv->r("cfgkey", $x);
            $listserv->r("key", $mk);
            $listserv->r("curr", $data->aplayers);
            $listserv->r("type", $clustertype[$key["type"]]);
            $listserv->r("max", $server->cfg_read("ark_MaxPlayers"));
            $listserv->r("color", convertstate($server->statecode())["color"]);
            $listserv->r("color_type", $color_type);
            $listserv->r("state", convertstate($server->statecode())["str"]);

            $x++;

            $serverlist .= $listserv->load_var();
        }
    }

    $list_sync = null;
    $list_opt = null;

    //sync
    if ($json[$mk]["sync"]["admin"]) $list_sync .= "<tr><td>Admins</td></tr>";
    if ($json[$mk]["sync"]["mods"]) $list_sync .= "<tr><td>Mods</td></tr>";
    if ($json[$mk]["sync"]["konfig"]) $list_sync .= "<tr><td>Config</td></tr>";
    if ($json[$mk]["sync"]["whitelist"]) $list_sync .= "<tr><td>Whitelist</td></tr>";

    $listtpl->rif ("Administratoren", $json[$mk]["sync"]["admin"]);
    $listtpl->rif ("Mods", $json[$mk]["sync"]["mods"]);
    $listtpl->rif ("Konfigurationen", $json[$mk]["sync"]["konfig"]);
    $listtpl->rif ("whitelist", $json[$mk]["sync"]["whitelist"]);
    //opt
    if ($json[$mk]["opt"]["NoTransferFromFiltering"]) $list_opt .= "<tr><td>NoTransferFromFiltering</td></tr>";
    if ($json[$mk]["opt"]["NoTributeDownloads"]) $list_opt .= "<tr><td>NoTributeDownloads</td></tr>";
    if ($json[$mk]["opt"]["PreventDownloadSurvivors"]) $list_opt .= "<tr><td>PreventDownloadSurvivors</td></tr>";
    if ($json[$mk]["opt"]["PreventUploadSurvivors"]) $list_opt .= "<tr><td>PreventUploadSurvivors</td></tr>";
    if ($json[$mk]["opt"]["PreventDownloadItems"]) $list_opt .= "<tr><td>PreventDownloadItems</td></tr>";
    if ($json[$mk]["opt"]["PreventUploadItems"]) $list_opt .= "<tr><td>PreventUploadItems</li></tr>";
    if ($json[$mk]["opt"]["PreventDownloadDinos"]) $list_opt .= "<tr><td>PreventDownloadDinos</td></tr>";
    if ($json[$mk]["opt"]["PreventUploadDinos"]) $list_opt .= "<tr><td>PreventUploadDinos</td></tr>";


    $listtpl->rif ("NoTransferFromFiltering", $json[$mk]["opt"]["NoTransferFromFiltering"]);
    $listtpl->rif ("NoTributeDownloads", $json[$mk]["opt"]["NoTributeDownloads"]);
    $listtpl->rif ("PreventDownloadSurvivors", $json[$mk]["opt"]["PreventDownloadSurvivors"]);
    $listtpl->rif ("PreventUploadSurvivors", $json[$mk]["opt"]["PreventUploadSurvivors"]);
    $listtpl->rif ("PreventDownloadItems", $json[$mk]["opt"]["PreventDownloadItems"]);
    $listtpl->rif ("PreventUploadItems", $json[$mk]["opt"]["PreventUploadItems"]);
    $listtpl->rif ("PreventDownloadDinos", $json[$mk]["opt"]["PreventDownloadDinos"]);
    $listtpl->rif ("PreventUploadDinos", $json[$mk]["opt"]["PreventUploadDinos"]);

    if (count($json[$mk]["servers"]) == 0 || array_search(1, array_column($json[$mk]["servers"], 'type')) === FALSE) {
        $alert_r = $alert->rd(203, 3);
    }

    if ($serverlist == null) $serverlist = "<tr><td colspan='5'>Kein Server wurde gesetzt | <a href=\"javascript:void()\" data-toggle=\"modal\" data-target=\"#addservtocluster".$json[$mk]["clusterid"]."\">Server Hinzufügen</a> </td></tr>";
    if ($list_sync == null) $list_sync = "<tr><td colspan='5'>Synchronisation wurde nicht gesetzt | <a href=\"javascript:void()\" data-toggle=\"modal\" data-target=\"#options".$json[$mk]["clusterid"]."\">Einstellungen</a> </td></tr>";
    if ($list_opt == null) $list_opt = "<tr><td colspan='5'>Keine Optionen wurde gesetzt | <a href=\"javascript:void()\" data-toggle=\"modal\" data-target=\"#options".$json[$mk]["clusterid"]."\">Einstellungen</a> </td></tr>";

    $listtpl->r("alert", $alert_r);
    $listtpl->r("key", $mk);
    $listtpl->r("list_sync", $list_sync);
    $listtpl->r("list_opt", $list_opt);
    $listtpl->r("servercount", $count);
    $listtpl->r("serverlist", $serverlist);
    $listtpl->r("clustername", $json[$mk]["name"]);
    $listtpl->r("clusterid", $json[$mk]["clusterid"]);
    $list .= $listtpl->load_var();
}

$cfg_array = $helper->file_to_json("app/json/serverinfo/all.json");
$sel_serv = null;
foreach ($cfg_array["cfgs"] as $key) {
    $cfg = str_replace(".cfg", null, $key);
    $server = new server($cfg);
    $no = true;
    foreach ($json as $mk => $mv) {
        if (array_search($cfg, array_column($json[$mk]["servers"], 'server')) !== FALSE) $no = false;
    }
    if ($no) $sel_serv .= "<option value='$cfg'>".$server->cfg_read("ark_SessionName")."</option>";
}


$tpl->r("list", $list);
$tpl->r("resp", $resp);
$tpl->r("sel_serv", $sel_serv);
$content = $tpl->load_var();
$pageicon = "<i class=\"fas fa-random\"></i>";
if($user->perm("cluster/create")) $btns = '<a href="#" class="btn btn-outline-success btn-icon-split rounded-0" data-toggle="modal" data-target="#addcluster">
            <span class="icon">
                <i class="fas fa-plus" aria-hidden="true"></i>
            </span>
            <span class="text">{::lang::php::cluster::btn_addcluster}</span>
        </a>';
