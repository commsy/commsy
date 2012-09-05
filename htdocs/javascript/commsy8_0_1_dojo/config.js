var dojoConfig = {
	has: {
		"dojo-firebug":			false,
		"dojo-debug-messages":	false
	},
	baseUrl:					"javascript/commsy8_0_1_dojo/",
	tlmSiblingOfDojo:			false,
	packages: [
	           					{ name: "dojo", location: "libs/dojo" },
	           					{ name: "dijit", location: "libs/dijit" },
	           					{ name: "dojox", location: "libs/dojox" },
	           					{ name: "commsy", location: "commsy" },
	           					{ name: "widgets", location: "commsy/widgets" },
	           					{ name: "ckeditor", location: "libs/ckeditor" },
	           					{ name: "cbtree", location: "libs/cbtree" }
	],
	async:						true,
	parseOnLoad:				false,
	isDebug:					false
}

var CKEDITOR_BASEPATH = "javascript/commsy8_0_1_dojo/libs/ckeditor/";