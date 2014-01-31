var profile = (function(){
    return {
    	basePath:			"./src",
    	releaseDir:			"../build",
    	releaseName:		"release",
    	action:				"release",
    	
    	layerOptimize:		"closure",			/* "shrinksafe" | "shrinksafe.keeplines" | "closure" | "closure.keeplines" | "comment" | false */
    	optimize:			"closure",			/* "shrinksafe" | "shrinksafe.keeplines" | "closure" | "closure.keeplines" | "comment" | false */
    	stripConsole:		"normal",			/* "normal" | "none" | "warn" | "all" */
    	selectorEngine:		"acme",				/* "" | "lite" | "acme" */
    	cssOptimize:		"comments",			/* "comments" | "comments.keepLines" | false */
    	mini:				true,				/* true | false */
    	
    	/* this should solve errors - but it produces them :/
    	trees: [
    	    [ ".", ".", /(\/\.)|(~$)|(CVS)/ ]
    	],
    	*/
    	
    	layers: {
    		"dojo/dojo": {
    			include: [
    			    "dojo/dojo",
    			    "dojo/domReady",
    			    "dojo/_base/declare",
    			    "dojo/i18n",
    			    
    			    "dijit/TooltipDialog",
    			    
    			    "dojox/image/Lightbox"
    			],
    			exclude: [
    			],
    			customBase:	true,
    			boot:		true
    		},
    		
    		"final/commsy": {
    			include: [
    			    "commsy/main",
//    			    "commsy/popups/ToggleRoomConfiguration",
    			    /*"commsy/popups/TogglePersonalConfiguration",*/
    			    "commsy/popups/ToggleBreadcrumb",
    			    "commsy/popups/ToggleClipboard",
    			    "commsy/popups/ToggleStack",
    			    /*"commsy/popups/ToggleWidgets",*/
    			    /*"commsy/popups/TogglePortfolio",*/
    			    
    			    "commsy/widgets/WidgetManager",
    			    
    			    "commsy/popups/ClickDatePopup",
    			    "commsy/popups/ClickTodoPopup",
    			    "commsy/popups/ClickDiscussionPopup",
    			    "commsy/popups/ClickMaterialPopup",
    			    "commsy/popups/ClickAnnouncementPopup",
    			    "commsy/popups/ClickTopicPopup",
    			    "commsy/popups/ClickGroupPopup",
    			    "commsy/popups/ClickBuzzwordsPopup",
    			    "commsy/popups/ClickTagsPopup",
    			    "commsy/popups/ClickMailtomodPopup",
    			    
    			    "commsy/DivToggle",
    			    "commsy/AjaxActions",
    			    "commsy/tree",
    			    "commsy/Search",/*,
    			    "commsy/Overlay"*/
    			    "commsy/DivExpander",/*
    			    "commsy/Lightbox",*/
    			    "commsy/ListSelection",
    			    "commsy/Assessment"/*,
    			    "commsy/AutoOpenPopup"*/
    			],
    			exclude: [
    			    /*
    			     * There is a problem building i18n packages, so wee need to exlude this file,
    			     * otherwise it would be loaded as a popup dependency
    			     */
    			    "commsy/PopupHandler"
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
        	location:		"../3rdParty/ckeditor_4.3.2"
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