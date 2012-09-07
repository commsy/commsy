var profile = (function(){
	
	var		testResourceRegEx = /^dijit\/tests\//,	/* todo */
			
			copyOnly = function(filename, mid){
				var list = {
					"commsy/commsy.profile":1,
					"commsy/package.json":1
				};
				return		(mid in list) ||
							(/^dijit\/resources\//.test(mid) && !/\.css$/.test(filename)) ||
							/(png|jpg|jpeg|gif|tiff)$/.test(filename);
			};
	
    return {
    	basePath:			"../",
    	releaseDir:			"lib",
    	releaseName:		"release",
    	action:				"release",
    	layerOptimize:		"closure",
    	optimize:			"closure",
    	cssOptimize:		false,//"comments",
    	mini:				true,
    	stripConsole:		"normal",
    	selectorEngine:		"lite",
    	
    	trees: [
    	    [ ".", ".", /(\/\.)|(~$)|(CVS)/ ]
    	],
    	
    	layers: {
    		/*
    		"dojo/dojo": {
    	        include: [ "dojo/dojo", "dojo/i18n", "dojo/domReady",
    	            "app/main", "app/run" ],
    	        customBase: true,
    	        boot: true
    	    },
    	    */
    	    
    	    "commsy/CommSy": {
    	        include: [
    	            "commsy/Accounts"/*,
    	            "commsy/ActionExpander",
    	            "commsy/AjaxActions",
    	            "commsy/AnchorFollower",
    	            "commsy/Assessment",
    	            "commsy/AutoOpenPopup",
    	            "commsy/base",
    	            "commsy/Calendar",
    	            "commsy/ckeditor",
    	            "commsy/ClickPopupHandler",
    	            "commsy/Clipboard",
    	            "commsy/Colorpicker",
    	            "commsy/DateCalendar",
    	            "commsy/DiscussionTree",
    	            "commsy/DivExpander",
    	            "commsy/DivToggle",
    	            "commsy/EditTree",
    	            "commsy/Lightbox",
    	            "commsy/List",
    	            "commsy/ListSelection",
    	            "commsy/main",
    	            "commsy/Netnavigation",
    	            "commsy/Overlay",
    	            "commsy/Path",
    	            "commsy/PopupHandler",
    	            "commsy/PortfolioTree",
    	            "commsy/ProgressBar",
    	            "commsy/Search",
    	            "commsy/TogglePopupHandler",
    	            "commsy/tree",
    	            "commsy/Uploader",
    	            "commsy/WidgetPopupHandler",
    	            
    	            "commsy/widgets/DetailView",
    	            "commsy/widgets/ErrorDialog",
    	            "commsy/widgets/Portfolio",
    	            "commsy/widgets/PortfolioItem",
    	            "commsy/widgets/StackBuzzwordView",
    	            "commsy/widgets/StackNew"
    	            "commsy/widgets/StackStack",
    	            "commsy/widgets/StackTagView",
    	            "commsy/widgets/WidgetsNewEntries",
    	            "commsy/widgets/WidgetsReleasedEntries",
    	            "commsy/widgets/WidgetsRssTicker",
    	            
    	            "commsy/popups/ClickAnnotationPopup",
    	            "commsy/popups/ClickAnnouncementPopup",
    	            "commsy/popups/ClickBuzzwordsPopup",
    	            "commsy/popups/ClickDatePopup",
    	            "commsy/popups/ClickDeletePopup",
    	            "commsy/popups/ClickDetailPopup",
    	            "commsy/popups/ClickDiscarticlePopup",
    	            "commsy/popups/ClickDiscussionPopup",
    	            "commsy/popups/ClickGroupPopup",
    	            "commsy/popups/ClickInstitutionPopup",
    	            "commsy/popups/ClickJoinPopup",
    	            "commsy/popups/ClickMailtogroupPopup",
    	            "commsy/popups/ClickMailtomodPopup",
    	            "commsy/popups/ClickMaterialPopup",
    	            "commsy/popups/ClickPortfolioListPopup",
    	            "commsy/popups/ClickPortfolioPopup",
    	            "commsy/popups/ClickProjectPopup",
    	            "commsy/popups/ClickRssPopup",
    	            "commsy/popups/ClickSectionPopup",
    	            "commsy/popups/ClickSendPopup",
    	            "commsy/popups/ClickStepPopup",
    	            "commsy/popups/ClickTagPortfolioPopup",
    	            "commsy/popups/ClickTagsPopup",
    	            "commsy/popups/ClickTodoPopup",
    	            "commsy/popups/ClickTopicPopup",
    	            "commsy/popups/ClickUserContextJoinPopup",
    	            "commsy/popups/ClickParticipationPopup",
    	            "commsy/popups/ClickUserPopup",
    	            "commsy/popups/ToggleBreadcrumb",
    	            "commsy/popups/ToggleClipboard",
    	            "commsy/popups/TogglePersonalConfiguration",
    	            "commsy/popups/TogglePortfolio",
    	            "commsy/popups/ToggleRoomConfiguration",
    	            "commsy/popups/ToggleStack",
    	            "commsy/popups/ToggleWidgets"
    	            */
    	        ]
    	    }
    	},
    	
        resourceTags: {
        	test: function(filename, mid){
				return testResourceRegEx.test(mid) || mid=="dijit/robot" || mid=="dijit/robotx";
			},

			copyOnly: function(filename, mid){
				return copyOnly(filename, mid);
			},

			amd: function(filename, mid){
				return !testResourceRegEx.test(mid) && !copyOnly(filename, mid) && /\.js$/.test(filename);
			},

			miniExclude: function(filename, mid){
				return /^dijit\/bench\//.test(mid) || /^dijit\/themes\/themeTest/.test(mid);
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
        	location:		"ckeditor"
        },{
        	name:			"commsy",
        	location:		"commsy"
        }]/*,
        
        /*
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