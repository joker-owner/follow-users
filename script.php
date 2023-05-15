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

$srcToken = $argv[1];
$destToken = $argv[2];

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

?>
