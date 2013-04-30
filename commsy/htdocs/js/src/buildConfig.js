var dojoConfig = {
	has: {
		"dojo-firebug":			false,
		"dojo-debug-messages":	false
	},
	baseUrl:					"js/build/release/",
	tlmSiblingOfDojo:			false,
	selectorEngine:				'acme',
	packages: [
	           					{ name: "dojo", location: "dojo" },
	           					{ name: "dijit", location: "dijit" },
	           					{ name: "dojox", location: "dojox" },
	           					{ name: "commsy", location: "commsy" },
	           					/*{ name: "widgets", location: "commsy/widgets" },*/
	           					{ name: "ckeditor", location: "../../3rdParty/ckeditor_4.1.1" },
	           					{ name: "cbtree", location: "cbtree" }
	],
	/*aliases: [
	          					[ "widgets", "commsy/widgets" ]
	],*/
	async:						true,
	parseOnLoad:				false,
	isDebug:					false
};

var CKEDITOR_BASEPATH = "js/3rdParty/ckeditor_4.1.1/";