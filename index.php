<?php

$defaultShow = "thomas and friends";

$show = isset($_REQUEST["show"]) ? $_REQUEST["show"] : $defaultShow;

$folder = "/mnt/media-disk/recordings/";
$host = "localhost";
$port = "8080";

// get files from pvr recordings folder
$files = scandir($folder);
$newArray = array();
foreach($files as $eachFile) 
{
    $newString = strtolower(str_replace("_", " ", $eachFile));
    if(strpos($newString, strtolower($show)) > -1) {
        $newArray[] = $folder . $eachFile;
    }
}


// get list of tv shows
$tvShowsJSON = callApi(array(
    "method" => "VideoLibrary.GetTVShows"
));
$tvShowsArray = json_decode($tvShowsJSON, true);

$tvShowIds = array();
if($tvShowsArray && isset($tvShowsArray["result"]) && isset($tvShowsArray["result"]["tvshows"]))
{
    foreach($tvShowsArray["result"]["tvshows"] as $eachTvShow)
    {
        $newString = strtolower($eachTvShow["label"]);
        if(strpos($newString, strtolower($show)) > -1) {
            $tvShowIds[] = $eachTvShow["tvshowid"];
        }
    }
}

// get details ot matching tv show(s)

if(count($tvShowIds) > 0)
{
    foreach($tvShowIds as $eachTvShowId)
    {
        $episodesJSON = callApi(array(
            "method" => "VideoLibrary.GetEpisodes",
            "params" => array(
                "tvshowid" => $eachTvShowId,
                "properties" => array("season","episode","tvshowid","file")
            )
        ));
        $episodes = json_decode($episodesJSON, true);
        if($episodes && isset($episodes["result"]) && isset($episodes["result"]["episodes"]))
        {
            foreach($episodes["result"]["episodes"] as $eachEpisode)
            {
                $newArray[] = $eachEpisode["file"];
            }
        }
    }
}

if(count($newArray) > 0)
{
    // pick random file
    $rand_key = array_rand($newArray);
    $randomFile = $newArray[$rand_key];

    callApi(array(
        "method" => "Player.Open",
        "params" => array(
            "item" => array(
                "file" => $randomFile
            )
        )
    ));
}
else
{
    callApi(array(
        "method" => "GUI.ShowNotification",
        "params" => array(
            "title" => "Kidsplay",
            "message" => "No matching recordings found"
        )
    ));
}


// $data should include "method" and any params;
function callApi($data)
{
    global $host, $port;
    $data["jsonrpc"] = "2.0";
    $data["id"] = 1;

    $url = "http://$host:$port/jsonrpc?request=" . urlencode(json_encode($data));
    $username = 'houses';
    $password = 'rebecca';

    $cred = sprintf('Authorization: Basic %s', base64_encode("$username:$password"));
    $opts = array(
        'http'=>array(
        'method'=>'GET',
        'header'=>$cred) 
    );
    $context = stream_context_create($opts);

//    var_dump($url);

    return @file_get_contents($url, false, $context);

}
