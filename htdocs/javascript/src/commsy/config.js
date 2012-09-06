var dojoConfig = {
	has: {
		"dojo-firebug":			false,
		"dojo-debug-messages":	false
	},
	baseUrl:					"javascript/src/",
	tlmSiblingOfDojo:			false,
	packages: [
	           					{ name: "commsy", location: "commsy" }
	],
	paths: {
	           					"widgets":		"commsy/widgets",
	           					"templates":	"commsy/templates"
	},
	async:						true,
	parseOnLoad:				false,
	isDebug:					false
}

var CKEDITOR_BASEPATH = "javascript/commsy8_dojo/libs/ckeditor/";