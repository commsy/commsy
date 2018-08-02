define([	"dojo/_base/declare",
        	"dijit/_WidgetBase",
        	"commsy/base",
        	"dijit/_TemplatedMixin",
        	"dojo/_base/lang",
        	"dojo/dom-construct",
        	"dojo/dom-attr",
        	"dojo/on",
        	"commsy/request",
        	"dojo/query",
        	"dojo/topic",
        	"dojo/dnd/Source"], function(declare, WidgetBase, BaseClass, TemplatedMixin, lang, DomConstruct, DomAttr, On, request, Query, Topic, Source) {

	return declare([BaseClass, WidgetBase, TemplatedMixin], {
		baseClass:			"CommSyWidget",
		widgetHandler:		null,

		itemId:				null,
		
		dndSource:			null,

		currentPage:		1,
		maxPage:			1,
		entriesPerPage:		20,
		search:				"",

		constructor: function(options) {
			options = options || {};
			declare.safeMixin(this, options);

			this.restrictions = {
				buzzwords: [],
				tags: []
			};
		},

		postCreate: function() {
			// run parent postCreate processes
			this.inherited(arguments);

			/************************************************************************************
			 * Initialization is done here
			 ************************************************************************************/
			this.itemId = this.from_php.ownRoom.id;

			// subscribes
			Topic.subscribe("newOwnRoomItem", lang.hitch(this, function(object) {
				this.updateList();
			}));
			
			// create dnd source
			this.dndSource = new Source(this.itemListNode, {
				creator:	lang.hitch(this, this.createDnDItem),
				accept:		[],
				copyOnly:	true,
				selfAccept:	false
			});

			// update list
			this.updateList();
		},

		updateList: function() {
			// empty dnd and list
			this.dndSource.selectAll().deleteSelectedNodes();
			DomConstruct.empty(this.itemListNode);
			
			request.ajax({
				query: {
					cid:	this.uri_object.cid,
					mod:	'ajax',
					fct:	'widget_stack',
					action:	'getListContent'
				},
				data: {
					search:					this.search.toLowerCase(),
					start:					(this.currentPage - 1) * this.entriesPerPage,
					numEntries:				this.entriesPerPage,
					buzzwordRestrictions:	dojo.map(this.restrictions.buzzwords, function(item) { return item.id; }),
					tagRestrictions:		dojo.map(this.restrictions.tags, function(item) { return item.id; })
				}
			}).then(
				lang.hitch(this, function(response) {
					// create dnd items
					dojo.forEach(response.data.items, lang.hitch(this, function(item, index, arr) {
						item.index = index;
						this.dndSource.insertNodes(false, [{data: item}]);
					}));

					// update max page
					this.maxPage = Math.ceil(response.data.total / this.entriesPerPage);

					// set template values
					this.currentPageNode.innerHTML = Math.min(this.currentPage, this.maxPage);
					this.maxPageNode.innerHTML = this.maxPage;
				})
			);
		},
		
		createDnDItem: function(data, hint)
		{
			var item = data.data;
			var index = item.index;
			
			// create list entries
			var rowNode = DomConstruct.create("div", {
				className:		(index % 2 == 0) ? "row_even even_sep_search" : "row_odd odd_sep_search"
			});

				var firstColumnNode = DomConstruct.create("div", {
					className:		"column_280"
				}, rowNode, "last");

					var pNode = DomConstruct.create("p", {}, firstColumnNode, "last");

						var aNode = DomConstruct.create("a", {
							"id":		"listItem" + item.itemId,
							className:	"stack_link",
							href:		"#",
							innerHTML:	item.title
						}, pNode, "last");

				var secondColumnNode = DomConstruct.create("div", {
					className:		"column_45"
				}, rowNode, "last");

					var pNode = DomConstruct.create("p", {}, secondColumnNode, "last");

						if (item.fileCount > 0) {
							DomConstruct.create("a", {
								className:		"attachment",
								href:			"#",
								innerHTML:		item.fileCount
							}, pNode, "last");
						}

				var thirdColumnNode = DomConstruct.create("div", {
					className:		"column_65"
				}, rowNode, "last");

					var pNode = DomConstruct.create("p", {}, thirdColumnNode, "last");

						DomConstruct.create("img", {
							src:		this.from_php.template.tpl_path + "img/netnavigation/" + item.image.img,
							title:		item.image.text
						}, pNode, "last");

				var fourthColumnNode = DomConstruct.create("div", {
					className:		"column_90"
				}, rowNode, "last");

					DomConstruct.create("p", {
						innerHTML:		item.modificationDate
					}, fourthColumnNode, "last");

				DomConstruct.create("div", {
					className:		"clear"
				}, rowNode, "last");

			require(["commsy/popups/ClickDetailPopup"], lang.hitch(this, function(ClickPopup) {
				var handler = new ClickPopup();
				handler.init(aNode, { iid: item.itemId, module: item.module, contextId: this.itemId, versionId: item.versionId });
			}));
			
			return { node: rowNode, data: data };
		},

		addBuzzwordRestriction: function(buzzwordId, buzzwordName) {
			// check if this buzzword is already in list
			var filtered = dojo.filter(this.restrictions.buzzwords, function(buzzword, index, arr) {
				return buzzword.id == buzzwordId;
			});

			if (filtered.length == 0 && this.restrictions.buzzwords.length == 0) {
				this.restrictions.buzzwords.push({ id: buzzwordId, name: buzzwordName });

				// update restriction list
				this.updateBuzzwordRestrictions();

				this.updateList();
			}
		},

		addTagRestriction: function(tagId, tagName) {
			// check if this tag is already in list
			var filtered = dojo.filter(this.restrictions.tags, function(tag, index, arr) {
				return tag.id == tagId;
			});

			if (filtered.length == 0 && this.restrictions.tags.length == 0) {
				this.restrictions.tags.push({ id: tagId, name: tagName });

				// update restriction list
				this.updateTagRestrictions();

				this.updateList();
			} else {
				// if it is, replace the old
				this.restrictions.tags[0] = { id: tagId, name: tagName };
				
				// update restriction list
				this.updateTagRestrictions();

				this.updateList();
			}
		},

		/************************************************************************************
		 * EventHandler
		 ************************************************************************************/
		onClickPaging20: function(event) {
			this.entriesPerPage = 20;
			this.currentPage = 1;
			this.paging20.innerHTML = "<strong>20</strong>";
			this.paging50.innerHTML = "50";
			this.updateList();
		},

		onClickPaging50: function(event) {
			this.entriesPerPage = 50;
			this.currentPage = 1;
			this.paging20.innerHTML = "20";
			this.paging50.innerHTML = "<strong>50</strong>";
			this.updateList();
		},

		onClickPagingFirst: function(event) {
			if (this.currentPage > 1) this.currentPage = 1;
			this.updateList();
		},

		onClickPagingPrev: function(event) {
			if (this.currentPage > 1) this.currentPage--;
			this.updateList();
		},

		onClickPagingNext: function(event) {
			if (this.currentPage < this.maxPage) this.currentPage++;
			this.updateList();
		},

		onClickPagingLast: function(event) {
			if (this.currentPage < this.maxPage) this.currentPage = this.maxPage;
			this.updateList();
		},

		onClickSearch: function(event) {
			var searchWord = this.searchNode.value;
			this.search = searchWord;
			this.updateList();
		},

		onBuzzwordRestrictionRemove: function(buzzwordId) {
			var liNode = Query("li#" + buzzwordId, this.buzzwordRestrictionsNode)[0];

			if (liNode) {
				DomConstruct.destroy(liNode);
			}

			this.restrictions.buzzwords = dojo.filter(this.restrictions.buzzwords, function(buzzword, index, arr) {
				return buzzword.id != buzzwordId;
			});

			this.updateList();
		},

		onTagRestrictionRemove: function(tagId) {
			var liNode = Query("li#" + tagId, this.tagRestrictionsNode)[0];

			if (liNode) {
				DomConstruct.destroy(liNode);
			}

			this.restrictions.tags = dojo.filter(this.restrictions.tags, function(tag, index, arr) {
				return tag.id != tagId;
			});

			this.updateList();
		},

		/************************************************************************************
		 * Helper Functions
		 ************************************************************************************/
		updateBuzzwordRestrictions: function() {
			DomConstruct.empty(this.buzzwordRestrictionsNode);

			dojo.forEach(this.restrictions.buzzwords, lang.hitch(this, function(buzzword, index, arr) {
				var liNode = DomConstruct.create("li", {
					"id":		buzzword.id,
					className:	"float-left"
				}, this.buzzwordRestrictionsNode, "last");

					DomConstruct.create("span", {
						innerHTML:	buzzword.name
					}, liNode, "last");

					var aNode = DomConstruct.create("a", {
						href:		"#"
					}, liNode, "last");

						DomConstruct.create("img", {
							src:	this.from_php.template.tpl_path + "img/cross.gif"
						}, aNode, "last");

				On(aNode, "click", lang.hitch(this, function(event) {
					this.onBuzzwordRestrictionRemove(buzzword.id);
				}));
			}));
		},

		updateTagRestrictions: function() {
			DomConstruct.empty(this.tagRestrictionsNode);

			dojo.forEach(this.restrictions.tags, lang.hitch(this, function(tag, index, arr) {
				var liNode = DomConstruct.create("li", {
					"id":		tag.id,
					className:	"float-left"
				}, this.tagRestrictionsNode, "last");

					DomConstruct.create("span", {
						innerHTML:	tag.name
					}, liNode, "last");

					var aNode = DomConstruct.create("a", {
						href:		"#"
					}, liNode, "last");

						DomConstruct.create("img", {
							src:	this.from_php.template.tpl_path + "img/cross.gif"
						}, aNode, "last");

				On(aNode, "click", lang.hitch(this, function(event) {
					this.onTagRestrictionRemove(tag.id);
				}));
			}));
		}
	});
});