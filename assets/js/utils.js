function episode_tag(season, episode) {
	var s = season.index;
	var e = episode.index;
	r = (s<10)?'S0'+s:'S'+s;
	r += (e<10)?'E0'+e:'E'+e;
	return r;
} // end func: episode_tag


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

function inflect(num, single, plural) {
	if(num==1) return single;
	if(plural) return plural;
	return single+"s";
} // end func: inflect

function hl_bytes_to_human(bytes) {
	var b = parseInt(bytes);
	if(b < 1024) return b+' b';
	var kb = b/1024;
	if(kb < 1024) return Math.round(kb*100)/100+' Kb';
	var mb = kb/1024;
	if(mb < 1024) return Math.round(mb*100)/100+' Mb';
	var gb = mb/1024;
	if(gb < 1024) return Math.round(gb*100)/100+' Gb';
	var pb = gb/1024;
	return Math.round(pb*100)/100+' Pb';
	if(bytes < 1073741824) return Math.round(bytes/1048576*100)/100+' Mb';
	var kb = b/1024;
	if(bytes < 1099511627776) return Math.round(bytes/1073741824*100)/100+' Gb';
	return bytes+' Xb';
} // end func: hl_bytes_to_human