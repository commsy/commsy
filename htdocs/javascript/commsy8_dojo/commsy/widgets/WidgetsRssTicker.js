define([	"dojo/_base/declare",
        	"dijit/_WidgetBase",
        	"commsy/base",
        	"dijit/_TemplatedMixin",
        	"dojo/_base/lang",
        	"dojo/dom-construct",
        	"dojo/dom-attr",
        	"dojo/query",
        	"dojo/on"], function(declare, WidgetBase, BaseClass, TemplatedMixin, Lang, DomConstruct, DomAttr, Query, On) {
	
	return declare([BaseClass, WidgetBase, TemplatedMixin], {
		baseClass:			"CommSyWidget",
		widgetHandler:		null,
		
		items:				[],
		
		constructor: function(options) {
			options = options || {};
			declare.safeMixin(this, options);
		},
		
		postCreate: function() {
			// run parent postCreate processes
			this.inherited(arguments);
			
			/************************************************************************************
			 * Initialization is done here
			 ************************************************************************************/
			this.AJAXRequest("widget_rss_ticker", "getRssFeeds", { },
				Lang.hitch(this, function(response) {
					dojo.forEach(response.feeds, Lang.hitch(this, function(feed, index, arr) {
						DomConstruct.create("li", {
							innerHTML:		feed.title
						}, this.listNode, "last");
					}));
					
					/*
					$html .= ' <h4 style="margin-bottom:0px; margin-top:0px;">'.$rss_item['title'].'</h4> '.LF;
			           $html .= '<div id="'.$rss_item['title'].'" class="ticker">'.$rss_item['title'];
			           $html .= '</div>';
			           
			           
			           
			           //rssticker_ajax(RSS_id, cachetime, divId, divClass, delay, optionalswitch)
     //1) RSS_id: "Array key of RSS feed in PHP script bridge.php"
     //2) cachetime: Time to cache the feed in minutes (0 for no cache)
     //3) divId: "ID of DIV to display ticker in. DIV dynamically created"
     //4) divClass: "Class name of this ticker, for styling purposes"
     //5) delay: delay between message change, in milliseconds
     //6) optionalswitch: "optional arbitrary" string to create additional logic in call back function
     $current_context_item = $this->_environment->getCurrentContextItem();
     $current_user_item = $this->_environment->getCurrentUserItem();
     $hash_manager = $this->_environment->getHashManager();
     $html  = '<script type="text/javascript"> '.LF;
     $html .= '   var rss_ticker_cid = "'.$current_context_item->getItemID().'";'.LF;
     $html .= '   var rss_ticker_sid = "'.$this->_environment->getSessionID().'";'.LF;
     $html .= '</script>'.LF;
     $portlet_rss_array = $current_context_item->getPortletRSSArray();
     foreach($portlet_rss_array as $rss_item){
        if (isset($rss_item['title']) and !empty($rss_item['title']) and isset($rss_item['adress']) and !empty($rss_item['adress'])){
           $html .= '<script type="text/javascript"> '.LF;
           if (isset($rss_item['title']) and !empty($rss_item['title']) and $rss_item['display'] == '2'){
              $html .= ' new rssticker_ajax("'.$rss_item['title'].'", 0, "'.$rss_item['title'].'", "ticker", 10000, "date",rss_ticker_cid,rss_ticker_sid);'.LF;
           }else{
              $html .= ' new rssticker_ajax("'.$rss_item['title'].'", 0, "'.$rss_item['title'].'", "ticker", 10000, "date",rss_ticker_cid,rss_ticker_sid);'.LF;
           }
           $html .= '</script>'.LF;
        }
     }
     return $html;
			           
			           */
				})
			);
		},
		
		/************************************************************************************
		 * EventHandler
		 ************************************************************************************/
		onClickNewRss: function(event) {
		}
	});
});