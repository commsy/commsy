define([], function(){
	var
		rev = "$Rev: e6490e5 $".match(/[0-9a-f]{7,}/),
		version= {
			major: 1, minor: 9, patch: 2, flag: "",
			revision: rev ? +rev[0] : NaN,
			toString: function(){
				var v= version;
				return v.major + "." + v.minor + "." + v.patch + v.flag + " (" + v.revision + ")";
			}
		};
	return version;
});
