define([	"dojo/_base/declare",
        	"commsy/base",
        	"dojo/_base/lang",
        	"dojo/query",
        	"dojo/on",
        	"dojo/cookie",
        	"dojo/dom-attr",
        	"dojo/dom-style",
        	"dojo/dom-class"], 

function(declare, BaseClass, Lang, Query, On, Cookie, DomAttr, DomStyle, DomClass) {
	return declare(BaseClass, {
		

		setup: function(printNode) {
		
			var aNodes = Query("div.content_item div");
			
			var cookieArray = [];
			dojo.forEach(aNodes, function(node, index, arr)
			{
				if(DomClass.contains(node, "hidden") || DomStyle.get(node, "display") == "none") {
					cookieArray.push(DomAttr.get(node, "id"));
				}
			});
			
			Cookie("hiddenDivs", cookieArray, { expires: 5 });
			window.open(window.location+"&mode=print", "Zweitfenster");
		}
	}); 
});