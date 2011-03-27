/*
	Utility and Global functions for Plex Export
*/





/*
 * Input season & episode num, get S0XE0Y out
 */
function episode_tag(season, episode) {
	var s = season.index;
	var e = episode.index;
	r = (s<10)?'S0'+s:'S'+s;
	r += (e<10)?'E0'+e:'E'+e;
	return r;
} // end func: episode_tag


/*
 * Format a number with thousands separator
 */
function number_format(num) {
	var num = parseInt(num);
	if(num<1000) return num;
	var num = num.toString();
	var rgx = /(\d+)(\d{3})/;
	while (rgx.test(num)) {
		num = num.replace(rgx, '$1' + ',' + '$2');
	}
	return num;
} // end func: number_format


/*
 * Given a num, return single if 1 otherwise plural. (plural is optional)
 */
function inflect(num, single, plural) {
	if(num==1) return single;
	if(plural) return plural;
	return single+"s";
} // end func: inflect


/*
 * Convert an object to string representation
 * @author http://www.davidpirek.com/blog/object-to-string-how-to-deserialize-json
 */
function objectToString(o) {
	var parse = function(_o) {
		var a = [], t;
		for(var p in _o) {
			if(_o.hasOwnProperty(p)) {
				t = _o[p];
				if(t && typeof t == "object") {
					a[a.length]= p + ":{ " + arguments.callee(t).join(", ") + "}";
				} else {
					if(typeof t == "string") {
						a[a.length] = [ p+ ": \"" + t.toString() + "\"" ];
					} else {
						a[a.length] = [ p+ ": " + t.toString()];
					}
				}
			}
		}
		return a;
	}
	return "{" + parse(o).join(", ") + "}";
} // end func: objectToString


/*
 * Bytes go in; Kb, Mb, Gb, Tb come out
 */
function hl_bytes_to_human(bytes) {
	var b = parseInt(bytes);
	if(b < 1024) return b+' b';
	var kb = b/1024;
	if(kb < 1024) return Math.round(kb*100)/100+' Kb';
	var mb = kb/1024;
	if(mb < 1024) return Math.round(mb*100)/100+' Mb';
	var gb = mb/1024;
	if(gb < 1024) return Math.round(gb*100)/100+' Gb';
	var tb = gb/1024;
	return Math.round(tb*100)/100+' Tb';
} // end func: hl_bytes_to_human