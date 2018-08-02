define(
[
 	"dojo/_base/declare",
 	"commsy/widgets/PopupBase",
 	"dijit/_TemplatedMixin",
 	"dojo/text!./templates/PortfolioTagEditWidget.html",
 	"dojo/i18n!./nls/PortfolioTagEditWidget",
 	"dojo/_base/lang",
 	"commsy/request",
 	"dojo/topic",
 	"dojo/dom-attr",
 	"dijit/Tooltip",
 	"dojo/dom-construct",
 	"dojo/on"
], function
(
	declare,
	PopupBase,
	TemplatedMixin,
	Template,
	PopupTranslations,
	lang,
	request,
	Topic,
	DomAttr,
	Tooltip,
	DomConstruct,
	On
) {
	return declare([PopupBase, TemplatedMixin],
	{
		templateString:		Template,
		baseClass:			"portfolioEditWidget",
		
		canOverlay:			true,							///< Determs if popup can overlay other popus
		
		portfolioId:		null,							///< Mixed in by calling class
		tagId:				null,							///< Mixed in by calling class
		position:			null,							///< Mixed in by calling class
		
		tree:				null,
		selectedTagId:		null,
		
		// attributes
		title:				"",
		_setTitleAttr:		{ node: "titleNode", type: "innerHTML" },
		
		submit:				"",
		_setSubmitAttr:		{ node: "submitButtonNode", type: "attribute", attribute: "value" },
		
		description:		"",
		_setDescriptionAttr:	{ node: "descriptionNode", type: "innerHTML" },
		
		constructor: function(options)
		{
			options = options || {};
			declare.safeMixin(this, options);
			
			this.popupTranslations = PopupTranslations;
		},
		
		/**
		 * \brief	Processing after the DOM fragment is created
		 * 
		 * Called after the DOM fragment has been created, but not necessarily
		 * added to the document.  Do not include any operations which rely on
		 * node dimensions or placement.
		 */
		postCreate: function()
		{
			// run parent postCreate processes
			this.inherited(arguments);
			
			/************************************************************************************
			 * Initialization is done here
			 ************************************************************************************/
			if ( this.tagId === null )
			{
				this.set("title", this.popupTranslations.titleCreate);
				this.set("submit", this.popupTranslations.buttonCreate);
			}
			else
			{
				this.set("title", this.popupTranslations.titleEdit);
				this.set("submit", this.popupTranslations.buttonEdit);
			}
		},
		
		/**
		 * \brief 	Processing after the DOM fragment is added to the document
		 * 
		 * Called after a widget and its children have been created and added to the page,
		 * and all related widgets have finished their create() cycle, up through postCreate().
		 * This is useful for composite widgets that need to control or layout sub-widgets.
		 * Many layout widgets can use this as a wiring phase.
		 */
		startup: function()
		{
			this.inherited(arguments);
			
			var submitButtonNode = this.submitButtonNode;
			
			// insert tree
			require(["commsy/EditTree"], lang.hitch(this, function(EditTree)
			{	
				// redeclare the edit tree class
				var Tree = declare(EditTree, {
					onCreateEntrySuccessfull: function(newTag)
					{
						var pathToTag = this.buildPath(newTag.item_id[0].toString());
						
						this.tree.set("paths", [pathToTag]).then(lang.hitch(this, function()
						{
							this.tree.focusNode(this.tree.get('selectedNode'));
						}));
					},
					
					onDeleteEntrySuccessfull: function(itemId)
					{
						DomAttr.set(submitButtonNode, "disabled", "disabled");
					}
				});
				
				this.tree = new Tree({
					followUrl:		false,
					checkboxes:		false,
					room_id:		this.from_php.ownRoom.id,
					expanded:		false,
					item_id:		this.item_id,
					popup:			this
				});
				
				this.tree.setupTree(this.treeNode, lang.hitch(this, function(tree)
				{
					// if in edit mode, expand the tree and select the tag we are editing
					if ( this.tagId !== null ) {
						var pathToTag = tree.buildPath(this.tagId);
						tree.tree.set("paths", [pathToTag]).then(function()
						{
							if (tree.addCreateAndRenameToAllLabels) {
								tree.addCreateAndRenameToAllLabels();
							}
						});
						
						if (this.submitButtonNode) {
							DomAttr.remove(this.submitButtonNode, "disabled");
						}
						
						this.selectedTagId = this.tagId;
					}
					
					// add basic actions to all tree entries
					if (tree.addCreateAndRenameToAllLabels) {
						On(tree.tree, "open", lang.hitch(this, function(item, node) {
							tree.addCreateAndRenameToAllLabels();
						}));
					}
				}));
			}));
			
			// if in edit mode
			if ( this.tagId !== null ) {
				// add delete button
				var deleteButtonNode = DomConstruct.create("input",
				{
					"id":			"popup_button_delete",
					className:		"popup_button float-right submit",
					"data-custom":	"part: 'delete'",
					type:			"button",
					name:			"",
					value:			this.popupTranslations.buttonDelete
				}, this.buttonDivNode, "last");
				
				// register event
				On(deleteButtonNode, "click", lang.hitch(this, this.onDeleteTag));
			}
		},
		
		/************************************************************************************
		 * Getter / Setter
		 ************************************************************************************/
		
		/************************************************************************************
		 * Helper Functions
		 ************************************************************************************/
		
		/************************************************************************************
		 * Event Handling
		 ************************************************************************************/		
		onTagSelected: function(tagId) {
			DomAttr.remove(this.submitButtonNode, "disabled");
			
			// remove previous error tooltips
			var widgetManager = this.getWidgetManager();
			widgetManager.closeErrorTooltips();
			
			// store selected id
			this.selectedTagId = tagId;
		},
		
		Close: function()
		{
			this.inherited(arguments);
			
			// remove previous error tooltips
			var widgetManager = this.getWidgetManager();
			widgetManager.closeErrorTooltips();
		},
		
		onSaveTag: function(event)
		{
			var widgetManager = this.getWidgetManager();
			
			// get optional description
			var description = DomAttr.get(this.descriptionNode, "value");
			
			// remove previous error tooltips
			widgetManager.closeErrorTooltips();
			
			// prepare data to send
			var data =
			{
				portfolioId:		this.portfolioId,
				tagId:				this.selectedTagId,
				position:			this.position,
				oldTagId:			this.tagId || "NEW",
				description:		description
			};
			
			request.ajax({
				query: {
					cid:	this.uri_object.cid,
					mod:	'ajax',
					fct:	'portfolio',
					action:	'updatePortfolioTag'
				},
				data: data
			}).then(
				lang.hitch(this, function(response) {
					if (response.code == "902") {		/* 	portfolio tag already assigned */
						widgetManager.createErrorTooltip(this.treeNode, this.popupTranslations.errorTagDouble, ["below"]);
					} else {
						Topic.publish("updatePortfolios", { itemId: this.portfolioId });
						this.Close();
					}
					
				})
			);
		},
		
		onDeleteTag: function(event)
		{
			request.ajax({
				query: {
					cid:	this.uri_object.cid,
					mod:	'ajax',
					fct:	'portfolio',
					action:	'deletePortfolioTag'
				},
				data: {
					tagId:			this.tagId,
					portfolioId:	this.portfolioId
				}
			}).then(
				lang.hitch(this, function(response) {
					Topic.publish("updatePortfolios", { itemId: this.portfolioId });
					this.Close();
				})
			);
		}
	});
});