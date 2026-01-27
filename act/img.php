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
        error_log("RBNimg.php: generating picture for $filename");
        if (!file_exists("/tmp/rbn2_cache")) {
            mkdir("/tmp/rbn2_cache");
        }

        $lock_count = 0;
        while (file_exists("/tmp/rbn2_cache/lock")) {
            $f = file_get_contents("/tmp/rbn2_cache/lock");
            error_log("RBNimg.php: $call: locking due to: $f (lock count: $lock_count)");
            sleep(1);
            $lock_count++;
            if ($lock_count > 5) {
                error_log("$cs: lock_count exceeds 5. Sending error.png");
                $data = file_get_contents("/home/fabian/rbn.telegraphy.de/error.png");
                header("Content-type: image/png");
                echo $data;
                exit();
            }
        }
        
        file_put_contents("/tmp/rbn2_cache/lock", $call);
        system("xvfb-run -a --server-args=\"-screen 0, 1280x768x24\" wkhtmltoimage -q --width 870 --javascript-delay 500 https://rbn.telegraphy.de/activity_iframe/$call /tmp/rbn2_cache/$filename.png 2> /dev/null");
        unlink("/tmp/rbn2_cache/lock");

        system("convert /tmp/rbn2_cache/$filename.png /tmp/rbn2_cache/$filename-small.png");
        system("rm -f /tmp/rbn2_cache/$filename.png");
        error_log("RBNimg.php: $call");
    } 
    else {
        error_log("RBNimg.php: $call (cached)");
    }

    $data = file_get_contents("/tmp/rbn2_cache/$filename-small.png");
    header("Content-type: image/png");
    echo $data;
}

?>
