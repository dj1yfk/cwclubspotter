<?
error_reporting(0);
$call = strtoupper($_GET['c']);

if (preg_match('/^[a-z0-9\/ ]+$/i', $call)) {

    $call = preg_replace('/ /', '+', $call);

    # error_log("Image for $call");

    if ($call == "NU6") {
        $call = "NU6I";
    }

    $filename = preg_replace('/\//', '_', $call);

    # error_log($filename);

    if (time()-filemtime('/tmp/rbn2_cache/'.$filename.'-small.png') > 1 * 3600) {   // 1h
        # error_log("generating picture for $filename");
        if (!file_exists("/tmp/rbn2_cache")) {
            mkdir("/tmp/rbn2_cache");
        }
        system("xvfb-run -a --server-args=\"-screen 0, 1280x768x24\" wkhtmltoimage -q --width 870 --javascript-delay 500 https://rbn.telegraphy.de/activity_iframe/$call /tmp/rbn2_cache/$filename.png 2> /dev/null");
        system("convert /tmp/rbn2_cache/$filename.png /tmp/rbn2_cache/$filename-small.png");
        system("rm -f /tmp/rbn2_cache/$filename.png");
    } 
    else {
        # error_log("using cache for $filename");
    }

    $data = file_get_contents("/tmp/rbn2_cache/$filename-small.png");
    header("Content-type: image/png");
    echo $data;
}

?>
