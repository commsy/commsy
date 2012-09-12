var profile = (function(){
    return {
    	basePath:			"./src",
    	releaseDir:			"../build",
    	releaseName:		"release",
    	action:				"release",
    	
    	layerOptimize:		"closure",			/* "shinksafe" | "shinksafe.keeplines" | "closure" | "closure.keeplines" | "comment" | false */
    	optimize:			"closure",			/* "shinksafe" | "shinksafe.keeplines" | "closure" | "closure.keeplines" | "comment" | false */
    	stripConsole:		"normal",			/* "normal" | "none" | "warn" | "all" */
    	selectorEngine:		"acme",				/* "" | "lite" | "acme" */
    	cssOptimize:		false,				/* "comments" | "comments.keepLines" | false */
    	mini:				true,				/* true | false */
    	
    	/* this should solve errors - but it produces them :/
    	trees: [
    	    [ ".", ".", /(\/\.)|(~$)|(CVS)/ ]
    	],
    	*/
    	
    	layers: {
    		"final/dojo": {
    			include: [
    			    "dojo/dojo",
    			    "dojo/domReady",
    			    "dojo/_base/declare"
    			],
    			exclude: [
    			],
    			customBase:	true,
    			boot:		true
    		},
    		
    		"final/commsy": {
    			include: [
    			    "commsy/main"
    			],
    			exclude: [
    			]
    		}
    	},
        
        defaultConfig: {
        	
        	/* not working??? - for now, relative paths are used */
        	/*
        	paths: {
					"widgets":		"commsy/widgets",
					"templates":	"commsy/templates"
        	}
        	*/
        },
        
        packages: [{
        	name:			"dojo",
        	location:		"dojo"
        },{
        	name:			"dijit",
        	location:		"dijit"
        },{
        	name:			"dojox",
        	location:		"dojox"
        },{
        	name:			"cbtree",
        	location:		"cbtree"
        },{
        	name:			"ckeditor",
        	location:		"../3rdParty/ckeditor"
        },{
        	name:			"commsy",
        	location:		"commsy"
        }]/*,
        
        staticHasFeatures: {
        	"config-deferredInstrumentation":	0,
            "config-dojo-loader-catches":		0,
            "config-tlmSiblingOfDojo":			0,
            "dojo-amd-factory-scan":			0,
            "dojo-combo-api":					0,
            "dojo-config-api":					1,
            "dojo-config-require":				0,
            "dojo-debug-messages":				0,
            "dojo-dom-ready-api":				1,
            "dojo-firebug":						0,
            "dojo-guarantee-console":			1,
            "dojo-has-api":						1,
            "dojo-inject-api":					1,
            "dojo-loader":						1,
            "dojo-log-api":						0,
            "dojo-modulePaths":					0,
            "dojo-moduleUrl":					0,
            "dojo-publish-privates":			0,
            "dojo-requirejs-api":				0,
            "dojo-sniff":						0,
            "dojo-sync-loader":					0,
            "dojo-test-sniff":					0,
            "dojo-timeout-api":					0,
            "dojo-trace-api":					0,
            "dojo-undef-api":					0,
            "dojo-v1x-i18n-Api":				0,
            "dom":								1,
            "host-browser":						1,
            "extend-dojo":						1
        }
        */
    };
})();