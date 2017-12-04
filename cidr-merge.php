<?php

/**
 * READ / PARSE FROM FILE
 */
$lines = file('input.txt', FILE_IGNORE_NEW_LINES); //  read every line of a text file into an array and have each line in a new element
foreach ($lines as $ipandmask) {
    if (preg_match('~^(.+?)/([^/]+)$~', $ipandmask, $part)) {
        $ip = $part[1];
        $mask = $part[2];
        $ipsbymask[$mask][] = ip2long($ip) & (-1 << (32 - $mask));
        // %(-1<<...)this is not needed if the addresses in your database are subnet addresses, but I included it just for case. That will do a bitwise AND on IP address and subnet mask and calculate the subnet address for any IP.
    }
} // print_r($ipsbymask);

/**
 * START FUNCTION
 * @var string multidimensional $arr[mask] = [ip1, ip2, ip3]
 */
$result_ips = summarize($ipsbymask);

/**
 * WRITE TO FILE
 */
$output = fopen("output.txt", "w") or die("Unable to open file!");
foreach ($result_ips as $mask => $ips) {
    foreach($ips as $ip) {
        echo long2ip($ip) . "/" . $mask . "<br/>";
        fwrite($output, long2ip($ip) . "/" . $mask . "\n");
    }
}
fclose($output);

function summarize($ipsbymask)
{
    $changed = false; // Did you change anything in this iteration?
    $new = array();   // Array with summarized scopes
    krsort($ipsbymask);  // Sort array keys (CIDR)
    foreach ($ipsbymask as $mask => $ips) {
        sort($ipsbymask[$mask]);  // Sort the scopes from lowest to highest
        for ($i = 0; $i < sizeof($ipsbymask[$mask]); $i++) {
            if ($ipsbymask[$mask][$i] == $ipsbymask[$mask][$i+1]) {   //Skip if you have two same subnets (not needed if your list of scopes is clean)
                continue;
            }
            if (($ipsbymask[$mask][$i] & (-1 << 33 - $mask)) == ($ipsbymask[$mask][$i+1] & (-1 << 33 - $mask))){ //Are subnet IDs from current and next subnet the same if you have smaller subnet mask?
                $new[$mask-1][] = $ipsbymask[$mask][$i] & (-1 << 33 - $mask);    //If yes add the summarized subnet to the new array
                $i++;                                                       //Skip the next subnet
                $changed = true;                                            //And raise the changed flag
            } else {
                $new[$mask][] = $ipsbymask[$mask][$i];                          //If not just copy the current subnet
            }
        }
    }
    return $changed ? summarize($new) : $ipsbymask; //If there were no changes you have the optimized summarization, otherwise summarize the new array
} // 10-15 lines of code? I got only 40, may be more... For ex: right regexp... :) Nariman
