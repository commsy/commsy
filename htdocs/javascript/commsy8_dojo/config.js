var dojoConfig = {
	has: {
		"dojo-firebug":			true,
		"dojo-debug-messages":	true
	},
	baseUrl:					"javascript/commsy8_dojo/",
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
	parseOnLoad:				false
}

var CKEDITOR_BASEPATH = "javascript/commsy8_dojo/libs/ckeditor/";