	var spots = {};
	var baseurl = 'https://rbn.telegraphy.de/bandmap.php'; 
	var contName = new Array("EU", "NA", "AS", "SA", "AF", "OC");
	var contShow = new Array();
	var bandName = new Array("160", "80", "60", "40", "30", "20", "17", "15", "12", "10", "6");
	var bandShow = new Array();
    var clubName = new Array("");
	var clubShow = new Array();
	var speedName = new Array("<20", "20-24", "25-29", "30-34", "35-39", ">39");
	var speedShow = new Array();
	var maxAge;
	var sort;
	var seqNr = seqNr || 1;
	var callFilter;
    var ownCall;
    var abbreviate = false;
    var linktarget = 'qrz';
    var show_all_events = false;

    var linktargets = { "qrz": "https://www.qrz.com/db/", "hamqth": "https://hamqth.com/", "rbn": "https://rbn.telegraphy.de/activity/" };

	window.setInterval('fetch_spots()', 30000);
    fetch_spots();

	function fetch_spots () {
			document.getElementById('upd').innerHTML = 'Updating...';

			var i;
            var queryurl = baseurl + "?req=14&EU=true&NA=true&AS=true&SA=true&AF=true&OC=true&160=true&80=true&60=true&40=true&30=true&20=true&17=true&15=true&12=true&10=true&6=true&CWOPS=false&FISTS=false&FOC=false&HSC=false&VHSC=false&SHSC=false&EHSC=false&SKCC=false&AGCW=false&NAQCC=false&BUG=false&RCWC=false&LIDS=false&NRR=false&QRPARCI=false&CWJF=false&TORCW=false&SOC=false&UFT=true&ECWARC=false&<20=true&20-24=true&25-29=true&30-34=true&35-39=true&>39=true&maxAge=20&sort=1&callFilter=*&ownCall=UFTweb&selfSpots=false";

			var request =  new XMLHttpRequest();
			request.open("GET", queryurl, true);
			request.onreadystatechange = function() {
				var done = 4, ok = 200;
				if (request.readyState == done && request.status == ok) {
					spots = JSON.parse(request.responseText);
					update_table();
				}
			}
			request.send(null);
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


	function update_table () {
			var d = document.getElementById('tab');

			var newtable;
			newtable = '<table class="tab" id="spots">' + '<tr><th class="myth">Frequency</th><th class="myth">Call</th><th class="myth">Age</th><th class="myth">Member of</th><th  class="myth" style="width:45px">WPM</th><th class="myth">Spotted by (and signal strength)</th></tr>';

			for (var i = 0; i < spots.length; i++) {
				if (spots[i].age < 2) { tabclass = 'newspot'; }
				else if (spots[i].age < 10) { tabclass = 'midspot'; }
                else { tabclass = 'oldspot'; }

                var scall = stripcall(spots[i].dxcall);

				newtable += '<tr class="' + tabclass + '">';
                newtable += '<td class="right">' + spots[i].freq+ '&nbsp;</td>';
                newtable += '<td><a href="' + linktargets[linktarget]  + spots[i].dxcall + '" target="_blank">' + spots[i].dxcall + '</a></td>';
				newtable += '<td class="right">' + spots[i].age+ '</td>';

                var mo = spots[i].memberof;

                if (abbreviate && mo.length > 10) {
                    var moa = mo.split(' ');
                    mo = '';
                    for (var j = 0; j < moa.length - 1; j++) {
                        mo += '<abbr title="' + moa[j] + '">' + moa[j].substr(0,1)  + '</abbr> '
                    }
                }
                else {
                    mo = mo.replace(/ /g, '&nbsp;')
                }

				newtable += '<td>' + mo + '</td>';
				newtable += '<td class="center">' + spots[i].wpm + '</td>';

                var mobile = false;
                var width = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth; 
                var limit = mobile ? 6 : Math.floor((width - 600) / 40);
                var moreskimmers = '';
				var high_contrast = true;

				newtable += '<td>';
				for (var j = 0; j < spots[i].snr_spotters.length; j++) {
                    if (j < limit) {
                        newtable += '<span title="' + spots[i].snr_spotters[j].snr +'" class="snr';
                        if (spots[i].snr_spotters[j].snr > 50) {
                                newtable += '50';
                        }
                        else if (spots[i].snr_spotters[j].snr > 40) {
                                newtable += '40';
                        }
                        else if (spots[i].snr_spotters[j].snr > 30) {
                                newtable += '30';
                        }
                        else if (spots[i].snr_spotters[j].snr > 20) {
                                newtable += '20';
                        }
                        else if (spots[i].snr_spotters[j].snr > 10) {
                                newtable += '10';
                        }
                        else {
                                newtable += '00';
                        }
                        newtable += '">';
                        newtable += spots[i].snr_spotters[j].call;
                        newtable += '</span> ';
                    }
                    else {
                        moreskimmers += spots[i].snr_spotters[j].call + ' (' +
                            + spots[i].snr_spotters[j].snr + ' dB) ';
                    }
                }

				if (moreskimmers != '') {
					newtable += ' <span title="' + moreskimmers + '" class="snr00';
					if (high_contrast == 'true' || high_contrast == true) {
						newtable += 'hc';
					}
					newtable += '">(+ ' + (spots[i].snr_spotters.length-limit);
					if (!mobile) {
						newtable += ' more';
					}
					newtable += ')</span>';
				}

				newtable += '</td>';
				newtable += '</tr>';
					
			}
			newtable += '</table>';

			d.innerHTML = newtable;
			document.getElementById('upd').innerHTML = '';
			
	}
