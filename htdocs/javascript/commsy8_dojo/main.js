var dojoConfig = {
	has: {
		"dojo-firebug":			true,
		"dojo-debug-messages":	true
	},
	baseUrl:					"/javascript/commsy8_dojo/",
	tlmSiblingOfDojo:			false,
	packages: [
	           					{ name: "dojo", location: "libs/dojo" },
	           					{ name: "dijit", location: "libs/dijit" },
	           					{ name: "dojox", location: "libs/dojox" },
	           					{ name: "commsy", location: "commsy"}
	]
}

var test = require(["commsy/base"]);
console.log(test);