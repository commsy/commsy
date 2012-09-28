//>>built
define("dojox/grid/enhanced/plugins/IndirectSelection","dojo/_base/declare,dojo/_base/array,dojo/_base/event,dojo/_base/lang,dojo/_base/html,dojo/_base/window,dojo/_base/connect,dojo/_base/sniff,dojo/query,dojo/keys,dojo/string,../_Plugin,../../EnhancedGrid,../../cells/dijit".split(","),function(h,i,l,k,f,m,d,p,q,n,r,s,t){var j=k.getObject("dojox.grid.cells"),j=h("dojox.grid.cells.RowSelector",j._Widget,{inputType:"",map:null,disabledMap:null,isRowSelector:!0,_connects:null,_subscribes:null,checkedText:"&#10003;",
unCheckedText:"O",constructor:function(){this.map={};this.disabledMap={};this.disabledCount=0;this._connects=[];this._subscribes=[];this.inA11YMode=f.hasClass(m.body(),"dijit_a11y");this.baseClass="dojoxGridRowSelector dijitReset dijitInline dijit"+this.inputType;this.checkedClass=" dijit"+this.inputType+"Checked";this.disabledClass=" dijit"+this.inputType+"Disabled";this.checkedDisabledClass=" dijit"+this.inputType+"CheckedDisabled";this.statusTextClass=" dojoxGridRowSelectorStatusText";this._connects.push(d.connect(this.grid,
"dokeyup",this,"_dokeyup"));this._connects.push(d.connect(this.grid.selection,"onSelected",this,"_onSelected"));this._connects.push(d.connect(this.grid.selection,"onDeselected",this,"_onDeselected"));this._connects.push(d.connect(this.grid.scroller,"invalidatePageNode",this,"_pageDestroyed"));this._connects.push(d.connect(this.grid,"onCellClick",this,"_onClick"));this._connects.push(d.connect(this.grid,"updateRow",this,"_onUpdateRow"))},formatter:function(a,b,c){var a=c.baseClass,d=!!c.getValue(b),
e=!!c.disabledMap[b];d?(a+=c.checkedClass,e&&(a+=c.checkedDisabledClass)):e&&(a+=c.disabledClass);return["<div tabindex = -1 ","id = '"+c.grid.id+"_rowSelector_"+b+"' ","name = '"+c.grid.id+"_rowSelector' class = '"+a+"' ","role = "+c.inputType+" aria-checked = '"+d+"' aria-disabled = '"+e+"' aria-label = '"+r.substitute(c.grid._nls["indirectSelection"+c.inputType],[b+1])+"'>","<span class = '"+c.statusTextClass+"'>"+(d?c.checkedText:c.unCheckedText)+"</span>","</div>"].join("")},setValue:function(){},
getValue:function(a){return this.grid.selection.isSelected(a)},toggleRow:function(a,b){this._nativeSelect(a,b)},setDisabled:function(a,b){0>a||this._toggleDisabledStyle(a,b)},disabled:function(a){return!!this.disabledMap[a]},_onClick:function(a){a.cell===this&&this._selectRow(a)},_dokeyup:function(a){a.cellIndex==this.index&&0<=a.rowIndex&&a.keyCode==n.SPACE&&this._selectRow(a)},focus:function(a){(a=this.map[a])&&a.focus()},_focusEndingCell:function(a,b){this.grid.focus.setFocusCell(this.grid.getCell(b),
a)},_nativeSelect:function(a,b){this.grid.selection[b?"select":"deselect"](a)},_onSelected:function(a){this._toggleCheckedStyle(a,!0)},_onDeselected:function(a){this._toggleCheckedStyle(a,!1)},_onUpdateRow:function(a){delete this.map[a]},_toggleCheckedStyle:function(a,b){var c=this._getSelector(a);if(c&&(f.toggleClass(c,this.checkedClass,b),this.disabledMap[a]&&f.toggleClass(c,this.checkedDisabledClass,b),c.setAttribute("aria-checked",b),this.inA11YMode))c.firstChild.innerHTML=b?this.checkedText:
this.unCheckedText},_toggleDisabledStyle:function(a,b){var c=this._getSelector(a);c&&(f.toggleClass(c,this.disabledClass,b),this.getValue(a)&&f.toggleClass(c,this.checkedDisabledClass,b),c.setAttribute("aria-disabled",b));this.disabledMap[a]=b;0<=a&&(this.disabledCount+=b?1:-1)},_getSelector:function(a){var b=this.map[a];if(!b){var c=this.view.rowNodes[a];c&&(b=q(".dojoxGridRowSelector",c)[0])&&(this.map[a]=b)}return b},_pageDestroyed:function(a){for(var b=this.grid.scroller.rowsPerPage,a=a*b,b=a+
b-1;a<=b;a++)this.map[a]&&(f.destroy(this.map[a]),delete this.map[a])},destroy:function(){for(var a in this.map)f.destroy(this.map[a]),delete this.map[a];for(a in this.disabledMap)delete this.disabledMap[a];i.forEach(this._connects,d.disconnect);i.forEach(this._subscribes,d.unsubscribe);delete this._connects;delete this._subscribes}}),u=h("dojox.grid.cells.SingleRowSelector",j,{inputType:"Radio",_selectRow:function(a){a=a.rowIndex;this.disabledMap[a]||(this._focusEndingCell(a,0),this._nativeSelect(a,
!this.grid.selection.selected[a]))}}),o=h("dojox.grid.cells.MultipleRowSelector",j,{inputType:"CheckBox",swipeStartRowIndex:-1,swipeMinRowIndex:-1,swipeMaxRowIndex:-1,toSelect:!1,lastClickRowIdx:-1,unCheckedText:"&#9633;",constructor:function(){this._connects.push(d.connect(m.doc,"onmouseup",this,"_domouseup"));this._connects.push(d.connect(this.grid,"onRowMouseOver",this,"_onRowMouseOver"));this._connects.push(d.connect(this.grid.focus,"move",this,"_swipeByKey"));this._connects.push(d.connect(this.grid,
"onCellMouseDown",this,"_onMouseDown"));this.headerSelector&&(this._connects.push(d.connect(this.grid.views,"render",this,"_addHeaderSelector")),this._connects.push(d.connect(this.grid,"_onFetchComplete",this,"_addHeaderSelector")),this._connects.push(d.connect(this.grid,"onSelectionChanged",this,"_onSelectionChanged")),this._connects.push(d.connect(this.grid,"onKeyDown",this,function(a){-1==a.rowIndex&&a.cellIndex==this.index&&a.keyCode==n.SPACE&&this._toggletHeader()})))},toggleAllSelection:function(a){var b=
this.grid,c=b.selection;a?c.selectRange(0,b.rowCount-1):c.deselectAll()},_onMouseDown:function(a){a.cell==this&&(this._startSelection(a.rowIndex),l.stop(a))},_onRowMouseOver:function(a){this._updateSelection(a,0)},_domouseup:function(a){p("ie")&&this.view.content.decorateEvent(a);0<=a.cellIndex&&this.inSwipeSelection()&&!this.grid.edit.isEditRow(a.rowIndex)&&this._focusEndingCell(a.rowIndex,a.cellIndex);this._finishSelect()},_dokeyup:function(a){this.inherited(arguments);a.shiftKey||this._finishSelect()},
_startSelection:function(a){this.swipeStartRowIndex=this.swipeMinRowIndex=this.swipeMaxRowIndex=a;this.toSelect=!this.getValue(a)},_updateSelection:function(a,b){if(this.inSwipeSelection()){var c=0!==b,d=a.rowIndex,e=d-this.swipeStartRowIndex+b;if(0<e&&this.swipeMaxRowIndex<d+b)this.swipeMaxRowIndex=d+b;if(0>e&&this.swipeMinRowIndex>d+b)this.swipeMinRowIndex=d+b;for(var g=0<e?this.swipeStartRowIndex:d+b,d=0<e?d+b:this.swipeStartRowIndex,e=this.swipeMinRowIndex;e<=this.swipeMaxRowIndex;e++)this.disabledMap[e]||
0>e||(e>=g&&e<=d?this._nativeSelect(e,this.toSelect):c||this._nativeSelect(e,!this.toSelect))}},_swipeByKey:function(a,b,c){if(c&&!(0===a||!c.shiftKey||c.cellIndex!=this.index||0>this.grid.focus.rowIndex)){b=c.rowIndex;if(0>this.swipeStartRowIndex)this.swipeStartRowIndex=b,0<a?(this.swipeMaxRowIndex=b+a,this.swipeMinRowIndex=b):(this.swipeMinRowIndex=b+a,this.swipeMaxRowIndex=b),this.toSelect=this.getValue(b);this._updateSelection(c,a)}},_finishSelect:function(){this.swipeMaxRowIndex=this.swipeMinRowIndex=
this.swipeStartRowIndex=-1;this.toSelect=!1},inSwipeSelection:function(){return 0<=this.swipeStartRowIndex},_nativeSelect:function(a,b){this.grid.selection[b?"addToSelection":"deselect"](a)},_selectRow:function(a){var b=a.rowIndex;if(!this.disabledMap[b]){l.stop(a);this._focusEndingCell(b,0);var c=b-this.lastClickRowIdx,d=!this.grid.selection.selected[b];if(0<=this.lastClickRowIdx&&!a.ctrlKey&&!a.altKey&&a.shiftKey){a=0<c?b:this.lastClickRowIdx;for(c=0<c?this.lastClickRowIdx:b;0<=c&&c<=a;c++)this._nativeSelect(c,
d)}else this._nativeSelect(b,d);this.lastClickRowIdx=b}},getValue:function(a){if(-1==a){var b=this.grid;return 0<b.rowCount&&b.rowCount<=b.selection.getSelectedCount()}return this.inherited(arguments)},_addHeaderSelector:function(){var a=this.view.getHeaderCellNode(this.index);if(a){f.empty(a);var b=this.grid,a=a.appendChild(f.create("div",{"aria-label":b._nls.selectAll,tabindex:-1,id:b.id+"_rowSelector_-1","class":this.baseClass,role:"Checkbox",innerHTML:"<span class = '"+this.statusTextClass+"'></span><span style='height: 0; width: 0; overflow: hidden; display: block;'>"+
b._nls.selectAll+"</span>"}));this.map[-1]=a;b=this._headerSelectorConnectIdx;void 0!==b&&(d.disconnect(this._connects[b]),this._connects.splice(b,1));this._headerSelectorConnectIdx=this._connects.length;this._connects.push(d.connect(a,"onclick",this,"_toggletHeader"));this._onSelectionChanged()}},_toggletHeader:function(){if(!this.disabledMap[-1])this.grid._selectingRange=!0,this.toggleAllSelection(!this.getValue(-1)),this._onSelectionChanged(),this.grid._selectingRange=!1},_onSelectionChanged:function(){var a=
this.grid;if(this.map[-1]&&!a._selectingRange)a.allItemsSelected=this.getValue(-1),this._toggleCheckedStyle(-1,a.allItemsSelected)},_toggleDisabledStyle:function(a,b){this.inherited(arguments);if(this.headerSelector){var c=this.grid.rowCount==this.disabledCount;c!=!!this.disabledMap[-1]&&(arguments[0]=-1,arguments[1]=c,this.inherited(arguments))}}}),h=h("dojox.grid.enhanced.plugins.IndirectSelection",s,{name:"indirectSelection",constructor:function(){var a=this.grid.layout;this.connect(a,"setStructure",
k.hitch(a,this.addRowSelectCell,this.option))},addRowSelectCell:function(a){if(this.grid.indirectSelection&&"none"!=this.grid.selectionMode){var b=!1,c=["get","formatter","field","fields"],d={type:o,name:"",width:"30px",styles:"text-align: center;"};if(a.headerSelector)a.name="";this.grid.rowSelectCell&&this.grid.rowSelectCell.destroy();i.forEach(this.structure,function(e){var g=e.cells;if(g&&0<g.length&&!b){e=g[0];if(!e[0]||!e[0].isRowSelector){var f;f=k.mixin(d,a,{type:"single"==this.grid.selectionMode?
u:o,editable:!1,notselectable:!0,filterable:!1,navigatable:!0,nosort:!0});i.forEach(c,function(a){a in f&&delete f[a]});if(1<g.length)f.rowSpan=g.length;i.forEach(this.cells,function(a,b){0<=a.index?a.index+=1:console.warn("Error:IndirectSelection.addRowSelectCell()-  cell "+b+" has no index!")});g=this.addCellDef(0,0,f);g.index=0;e.unshift(g);this.cells.unshift(g);this.grid.rowSelectCell=g}b=!0}},this);this.cellCount=this.cells.length}},destroy:function(){this.grid.rowSelectCell.destroy();delete this.grid.rowSelectCell;
this.inherited(arguments)}});t.registerPlugin(h,{preInit:!0});return h});