<?php

$limit = 5000;
$used = 0;
$message = "";

function getHeaders($curl, $header_line)
{
    if (strpos($header_line, "X-RateLimit-Limit:") !== false) {
        $GLOBALS["limit"] = (int) preg_replace("/[^0-9]/", "", $header_line);
    }
    if (strpos($header_line, "X-RateLimit-Used:") !== false) {
        $GLOBALS["used"] = (int) preg_replace("/[^0-9]/", "", $header_line);
    }
    return strlen($header_line);
}

function checkCount($token)
{
    $cURLConnection = curl_init();
    curl_setopt($cURLConnection, CURLOPT_URL, "https://api.github.com/user");
    curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($cURLConnection, CURLOPT_HEADERFUNCTION, "getHeaders");
    curl_setopt($cURLConnection, CURLOPT_HTTPHEADER, [
        "Accept: application/vnd.github.v3+json",
        "User-Agent: Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/93.0.4577.82 Mobile Safari/537.36",
        "Authorization: Bearer ". $token
    ]);

    $result = curl_exec($cURLConnection);
    curl_close($cURLConnection);
    return $result;
}

function doAction($token, $action, $user)
{
    $cURLConnection = curl_init();
    curl_setopt(
        $cURLConnection,
        CURLOPT_URL,
        "https://api.github.com/user/following/" . $user
    );
    curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($cURLConnection, CURLOPT_CUSTOMREQUEST, $action);
    curl_setopt($cURLConnection, CURLOPT_HEADERFUNCTION, "getHeaders");
    curl_setopt($cURLConnection, CURLOPT_HTTPHEADER, [
        "Accept: application/vnd.github.v3+json",
        "User-Agent: Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/93.0.4577.82 Mobile Safari/537.36",
        "Authorization: Bearer ". $token
    ]);
    $result = curl_exec($cURLConnection);
    curl_close($cURLConnection);
    return $result;
}

function getUsers($token, $type, $page)
{
    $cURLConnection = curl_init();
    curl_setopt(
        $cURLConnection,
        CURLOPT_URL,
        "https://api.github.com/user/" . $type . "?per_page=100&page=" . $page
    );
    curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($cURLConnection, CURLOPT_HEADERFUNCTION, "getHeaders");
    curl_setopt($cURLConnection, CURLOPT_HTTPHEADER, [
        "Accept: application/vnd.github.v3+json",
        "User-Agent: Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/93.0.4577.82 Mobile Safari/537.36",
        "Authorization: Bearer ". $token
    ]);
    $json = curl_exec($cURLConnection);
    curl_close($cURLConnection);
    $obj = json_decode($json, true);
    if (isset($obj["message"])) {
        $GLOBALS["message"] = $GLOBALS["message"] . $obj["message"];
    }
    return $json;
}

function getFollowings($token) {
    $z = 1;
    $following = [];

    while ($z <= 50) {
        $list = json_decode(
            getUsers($token, "following", $z),
            true
        );
        if (count($list) == 0) {
            break;
        }
        if ($GLOBALS["message"] != "") {
            break;
        }
        $following = array_merge($list, $following);
        $z++;
    }
    return $following;
}

function getFollowers($token) {
    $z = 1;
    $following = [];

    while ($z <= 50) {
        $list = json_decode(
            getUsers($token, "followers", $z),
            true
        );
        if (count($list) == 0) {
            break;
        }
        if ($GLOBALS["message"] != "") {
            break;
        }
        $following = array_merge($list, $following);
        $z++;
    }
    return $following;
}

function validation($array, $idx, $name) {
    if (!isset($array[$idx])) {
        echo "Need ".$name."\n";
        exit;
    }
}

$mode = $argv[1];

if ($mode == "-c") {
    validation($argv, 2, "srcToken");
    validation($argv, 3, "descToken");
    copyList($argv[2], $argv[3]);
} else if ($mode == "-a") {
    validation($argv, 2, "srcToken");
    adjustList($argv[2]);
} else if ($mode == "-d") {
    validation($argv, 2, "srcToken");
    deleteList($argv[2]);
} else {
    echo "Unknown mode parameter ".$argv[1]."\n";
}

function copyList($srcToken, $destToken) {
    $srcFollowing = getFollowings($srcToken);
    echo "Load following list of srcUser(".count($srcFollowing).")\n";
    $destFollowing = getFollowings($destToken);
    echo "Load following list of destUser(".count($destFollowing).")\n";
    $loginArr = [];
    
    foreach ($destFollowing as $destFl) {
        array_push($loginArr, $destFl["login"]);
    }
    
    foreach ($srcFollowing as $fl) {
        if (!in_array($fl["login"], $loginArr)) {
            doAction($destToken, "PUT", $fl["login"]);
            echo "User ".$fl["login"]."\t added to following\n";
            sleep(15);
        }
    }    
}

function adjustList($srcToken) {
    $srcFollowing = getFollowings($srcToken);
    echo "Load following list of srcUser(".count($srcFollowing).")\n";
    $srcFollowers = getFollowers($srcToken);
    echo "Load follower list of srcUser(".count($srcFollowers).")\n";
    $loginArr = [];
    
    foreach ($srcFollowers as $fl) {
        array_push($loginArr, $fl["login"]);
    }

    foreach ($srcFollowing as $fl) {
        if (!in_array($fl["login"], $loginArr)) {
            doAction($srcToken, "DELETE", $fl["login"]);
            echo "User ".$fl["login"]."\t removed from following\n";
            sleep(15);
        }
    }  
}

function deleteList($srcToken) {
    $srcFollowing = getFollowings($srcToken);
    echo "Load following list of srcUser(".count($srcFollowing).")\n";

    foreach ($srcFollowing as $fl) {
        doAction($srcToken, "DELETE", $fl["login"]);
        echo "User ".$fl["login"]."\t removed from following\n";
        sleep(15);
    }  
}

?>
