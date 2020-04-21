var alert_text = readCookie('alerts');
var alertVisual= readCookie('alertVisual');
var alertAudio= readCookie('alertAudio');
console.log("read from cookie: visual: " + alertVisual);
var last_alert = {};       // e.g. "DJ1YFK": 1518113583000
// for visual alert
var interval_alert;
var count_alert;
var alert_title_orig = document.title;

function load_alerts () {
    document.getElementById('alerts').value = alert_text;
}

// in:  callsign, possibly with portable stuff
// out: longest of the parts
function stripcall (c) {
        var longest_len = 0;
        var longest = 0;
        var parts = c.split("/");

        for (var i = 0; i < parts.length; i++) {
            if (parts[i].length > longest_len) {
                longest_len = parts[i].length;
                longest = i;	
            }
        }

        return parts[longest];
}


// in: c(all), f(req), al (alert list; calls and optionally freq
// ranges)
function match_alert(c, f, al) {
    for (var i = 0; i < al.length; i++) {
        var a = al[i];  // can either be a call ("DJ1YFK") or a call with freq filter ("DJ1YFK(3565-3575,7024-7026)")
        if (a.indexOf("(") == -1) {  // normal call
            if (al.indexOf(c) != -1) {
                return true;
            }
        }  
        else {
            var o = a.match(/[A-Z0-9\/]+/g);
            // out:  DJ1YFK 3565 3575 7024 7026 => call plus freq tuples
            if (c == o[0] && ((o.length - 1) % 2 == 0 && o.length >= 3)) {
                var tuples = (o.length - 1)/2;
                for (var j = 0; j < tuples; j++) {
                    if ((f >= parseFloat(o[1 + j*2])) && (f <= parseFloat(o[2 + j*2]))) {
                        return true;
                    }
                }
            }
        }
    }
    return false;
}


// in: list of calls to alert, a hash with frequencies for each call
// out: call generate_alert with list of calls that were not alerted
// within last 300 seconds
function check_alert (c, f) {
    var now = (new Date).getTime(); 
    var o = [];

    // corner case: no spots. don't generate an empty alert
    if (c.length == 1 && c[0] == "") {
        return;
    }

    for (var i = 0; i < c.length; i++) {
        // Make sure every call has a "Last spotted" timestamp, and if it's 1970...
        if (last_alert[c[i]] == null) {
            last_alert[c[i]] = 0;
        }

        if  (now - last_alert[c[i]] > 300000) {     // five minutes between alerts
            console.log("Alert! " + c[i]);
            o.push(c[i] + " (" + f[c[i]].join(", ") + ")");
            last_alert[c[i]] = now;
        }
        else {
            console.log("Alert not yet for " + c[i]);
        }
    }

    if (o.length)
        generate_alert(o);
}

function generate_alert (c) {

    if (alertVisual && !interval_alert) {  // no visual alert running already
        count_alert = 0;
        interval_alert = window.setInterval( function () { visual_alert(c); }, 500);

        // notification
        try {
            if (Notification.permission !== "granted")
                Notification.requestPermission();
            else {
                var notification = new Notification('RBN Alert!', {
                    icon: '/favicon.ico',
                    body: "Spots: " + c.join(", "),
             });
            }
        }
        catch (e) {
            console.log("Desktop notifications not available.");
        }
    }

    if (alertAudio) {
        sound_alert(c);
    }

}

function visual_alert (c) {

    var calls = c.join(", ");

    if (count_alert++ % 2) {
        document.title = "### Alert " + calls  + " ###";
    }
    else {
        document.title = "+++ Alert " + calls  + " +++";
    }

    if (count_alert == 10) {
        document.title = alert_title_orig;   
        clearInterval(interval_alert);
        interval_alert = null;
    }
}

function sound_alert (c) {
    var p = document.getElementById('cwplayer');
    var text = c.join(" ").replace(/\(.*\)/g, "");
    p.src = "/cgi-bin/cw.mp3?s=35&e=35&f=600&t=alert " + text
    p.play();
}

function toggle_alerts() {
    show_alertdiv = !show_alertdiv;
    document.getElementById("alertdiv").style.display = show_alertdiv ? 'block' : 'none';
}
