#!/usr/bin/php
<?php
$inputchoice = $argv[1];

if ($inputchoice == "low") {
    $findipsrange = array(
/*
PLACE THE IP ADDRESSES YOU WANT TO FIND IN HERE
*/
    );
    $remrange      = array(
/*
PLACE THE IP ADDRESSES YOU WANT TO REPLACE IN HERE
*/
    );
}

elseif ($inputchoice == "high") {
    $findipsrange = array(
/*
SAME AS ABOVE BUT IN REVERSE
*/
    );
    $remrange      = array(
  /*
SAME AS ABOVE BUT IN REVERSE
*/
    );
}

if (($inputchoice !== "low") && ($inputchoice !== "high")) {
    print $inputchoice . " is not valid. Try 'low' or 'high'.\n";
    exit("error: low or high range needed.\n");
}

for ($i = 0; $i < count($findipsrange); ++$i) {
    
    //Set the auth credentials here!
    $authemail = "blahhhh@blah.con";
    $authkey   = "12345678912345678912345678";
    
    
    // Old IP address to find
    $oldip = $remrange[$i];
    
    // New IP address to replace the old one with
    $newip = $findipsrange[$i];
    
    $ch = curl_init("https://api.cloudflare.com/client/v4/zones?page=1&per_page=50&match=all");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'X-Auth-Email: ' . $authemail,
        'X-Auth-Key: ' . $authkey,
        'Content-Type: application/json'
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $r = json_decode($response, true);
    
    
    if ($r['success'] && true === $r['success']) {
        print "success in contacting Cloudflare! Continuing..." . "\n";
    } elseif (isset($r['success']) && false === $r['success']) {
        print "Failure:\n";
        var_dump($error);
        $error = ($r['errors'][0]['message']);
        print $error . "\n";
        die();
    }
    
    $result = $r['result'];
    
    $count = 1;
    foreach ($result as $zone) {
        if (isset($zone['id'])) {
            $zoneid   = $zone['id'];
            $zonename = $zone['name'];
            
            $count++;
            
            // List all DNS records for this domain
            $ch = curl_init("https://api.cloudflare.com/client/v4/zones/$zoneid/dns_records");
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'X-Auth-Email: ' . $authemail,
                'X-Auth-Key: ' . $authkey,
                'Content-Type: application/json'
            ));
            
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $response = curl_exec($ch);
            curl_close($ch);
            $r = json_decode($response, true);
            
            $dnsrecs = $r['result'];
            print "Searching domain name: " . $dns['zone_name'] . " for IP " . $oldip . " to replace it with " . $newip . "\n";
            foreach ($dnsrecs as $dns) {
                if (preg_match("/$oldip/", $dns['content'])) {
                    // OK! Change this DNS record.
                    $newcontent     = str_replace($oldip, $newip, $dns['content']);
                    // Swap the content then
                    $dns['content'] = $newcontent;
                    updateDNSRecord($dns, $newip, $oldip);
                    print $dns['type'] . " record pointing at " . $dns['name'] . "record HAS BEEN CHANGED! Pointing at " . $dns['content'] . "\n";
                }
                
                print "IP " . $oldip . " not found for the " . $dns['type'] . " record pointing at " . $dns['name'] . ". Continuing to search other records in this domain.\n";
            }
        }
        print "\n";
        
        checkemwreckem($newip, $dns['content'], $zone['id']);
    }
    
}

function checkemwreckem($newip, $choldip, $zoneid)
{
    print "Verifying changes to domain.\n";
    
    // Check'em in case we wrecked 'em, yo.'
    global $authemail, $authkey;
    $ch = curl_init("https://api.cloudflare.com/client/v4/zones/$zoneid/dns_records");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'X-Auth-Email: ' . $authemail,
        'X-Auth-Key: ' . $authkey,
        'Content-Type: application/json'
    ));
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    curl_close($ch);
    $checks = json_decode($response, true);
    if (true === in_multiarray($choldip, $checks)) {
        die("Old IP " . $choldip . " found during post-domain run integrity check. Bailing out!\n");
    } else {
        print "Old IP " . $choldip . " not found. All instances replaced with " . $newip . ".\n";
    }
}
function updateDNSRecord($dns, $newip, $oldip)
{
    global $authemail, $authkey;
    $ch = curl_init("https://api.cloudflare.com/client/v4/zones/" . $dns['zone_id'] . "/dns_records/" . $dns['id']);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'X-Auth-Email: ' . $authemail,
        'X-Auth-Key: ' . $authkey,
        'Content-Type: application/json'
    ));
    
    $data_string = json_encode($dns);
    //    print "JSON_DATA_STRING: $data_string";
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    curl_close($ch);
    $respjson = array(
        json_decode($data_string)
    );
    $content  = returnContent($respjson, $newip);
    if ($content !== $oldip) {
        print "old IP Address: " . "for " . $dns['type'] . " record pointing at " . $dns['name'] . " was " . $oldip . ", new IP Address: " . $newip . "\n";
    }
    
    else {
        die("Failure! Soemthing weird happened. Please check for errors.");
    }
}

function returnContent($myObjectArray, $content)
{
    foreach ($myObjectArray as $obj) {
        if ($obj->Number == $content) {
            return $obj->Content;
        }
    }
    return "$content was the IP we failed upon.\n";
}

function in_multiarray($elem, $array)
{
    $top    = sizeof($array) - 1;
    $bottom = 0;
    while ($bottom <= $top) {
        if ($array[$bottom] == $elem)
            return true;
        else if (is_array($array[$bottom]))
            if (in_multiarray($elem, ($array[$bottom])))
                return true;
        
        $bottom++;
    }
    return false;
}
?>