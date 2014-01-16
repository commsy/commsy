var dojoConfig = {
	has: {
		"dojo-firebug":			false,
		"dojo-debug-messages":	false
	},
	baseUrl:					"js/build/",
	tlmSiblingOfDojo:			false,
	selectorEngine:				'acme',
	packages: [
	           					{ name: "layer", location: "final" },
	           					{ name: "dojo", location: "release/dojo" },
	           					{ name: "dijit", location: "release/dijit" },
	           					{ name: "dojox", location: "release/dojox" },
	           					{ name: "commsy", location: "release/commsy" },
	           					/*{ name: "widgets", location: "release/commsy/widgets" },*/
	           					{ name: "cbtree", location: "release/cbtree" }
	],
	/*aliases: [
								[ "widgets", "commsy/widgets" ]
	],*/
	async:						true,
	parseOnLoad:				false,
	isDebug:					false
};

var CKEDITOR_BASEPATH = "js/3rdParty/ckeditor_4.3.1/";