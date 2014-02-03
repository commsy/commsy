define([	"dojo/_base/declare",
        	"commsy/base",
        	"dojo/query",
        	"dojo/dom-attr",
        	"dojo/_base/lang",
        	"dojo/on"], function(declare, BaseClass, Query, DomAttr, Lang, On) {
	return declare(BaseClass, {
		constructor: function(options) {
			options = options || {};
			declare.safeMixin(this, options);
		},
		
		setup: function() {
			var scrollDivNode = Query("div.cal_table_scroll")[0];
			
			if (scrollDivNode) {
				scrollDivNode.scrollTop = 250;
			}
			
			var selectMonthNode = Query("select#calendar_switch_month")[0];
			if (selectMonthNode) {
				On(selectMonthNode, "change", Lang.hitch(this, function(event) {
					var uriObject = this.replaceOrSetURIParam("month", event.target.value);
					
					location.href = "commsy.php?" + dojo.objectToQuery(uriObject);
				}));
			}
			
			var selectWeekNode = Query("select#calendar_switch_week")[0];
			if (selectWeekNode) {
				On(selectWeekNode, "change", Lang.hitch(this, function(event) {
					var uriObject = this.replaceOrSetURIParam("week", event.target.value);
					
					location.href = "commsy.php?" + dojo.objectToQuery(uriObject);
				}));
			}
		}
	});
});