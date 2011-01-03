function _(key, args) {
	if(typeof(i18n)!='undefined' && typeof(i18n[key])!='undefined') {
		var str = i18n[key];
		if(typeof(args)!='undefined') {
			$.each(args, function(k,v){
				str = str.replace("%"+k+"%", v);
			});
		}
		return str;
	}
	console.log("i18n miss: "+key);
}


function episode_tag(season, episode) {
	var s = season.index;
	var e = episode.index;
	r = _("season_abbr") + ((s<10)?'0'+s:s);
	r += _("episode_abbr") + ((e<10)?'0'+e:e);
	return r;
} // end func: episode_tag


function number_format(num) {
	if(typeof(i18n)!='undefined' && typeof(i18n["number_format"])!='undefined') {
		return i18n.number_format(num);
	}
	var num = parseInt(num);
	if(num<1000) return num;
	var num = num.toString();
	var rgx = /(\d+)(\d{3})/;
	var sep = _("thousand_separator");
	while (rgx.test(num)) {
		num = num.replace(rgx, "$1" + sep + "$2");
	}
	return num;
} // end func: number_format


function inflect(num, key) {
	if(typeof(i18n)!='undefined' && typeof(i18n["inflect"])!='undefined') {
		return i18n.inflect(num, key);
	}
	if(num==1) return key;
	return key+"s";
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
} // end func: hl_bytes_to_human