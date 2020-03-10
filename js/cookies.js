// common include file for FOC RBN and Club Spotter.
// For some reason in FOC we use readCookie/createCookie,
// and in Club Spotter setCookie, getCookie.
// TODO: use the same for both


// FOC RBN
function readCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for(var i=0;i < ca.length;i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1,c.length);
        if (c.indexOf(nameEQ) == 0) {
            var ret = c.substring(nameEQ.length,c.length);
            if (ret == 'false') {
                return false;
            }
            else {
                return ret;
            }
        }
    }
    return null;
}

function createCookie(name,value,days) {
    if (days) {
        var date = new Date();
        date.setTime(date.getTime()+(days*24*60*60*1000));
        var expires = "; expires="+date.toGMTString();
    }
    else var expires = "";
    document.cookie = name+"="+value+expires+"; path=/";
}


// CW Club Spotter
function getCookieVal (offset) {
    var endstr = document.cookie.indexOf (";", offset);
    if (endstr == -1) { endstr = document.cookie.length; }
    return unescape(document.cookie.substring(offset, endstr));
}

function getCookie (name) {
    var arg = name + "=";
    var alen = arg.length;
    var clen = document.cookie.length;
    var i = 0;
    while (i < clen) {
            var j = i + alen;
            if (document.cookie.substring(i, j) == arg) {
                return getCookieVal (j);
            }
            i = document.cookie.indexOf(" ", i) + 1;
            if (i == 0) break; 
        }
    return null;
}

function setCookie(name, value) {
    var exdate = new Date();
    exdate.setDate(exdate.getDate()+4*7); // Expire in 4 weeks time
    var val=escape(value) + "; expires=" + exdate.toUTCString();
    document.cookie=name + "=" + val;
}
