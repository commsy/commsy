//>>built
define("dijit/form/_FormWidgetMixin",["dojo/_base/array","dojo/_base/declare","dojo/dom-attr","dojo/dom-style","dojo/_base/lang","dojo/mouse","dojo/_base/sniff","dojo/_base/window","dojo/window","../a11y"],function(_1,_2,_3,_4,_5,_6,_7,_8,_9,_a){
return _2("dijit.form._FormWidgetMixin",null,{name:"",alt:"",value:"",type:"text",tabIndex:"0",_setTabIndexAttr:"focusNode",disabled:false,intermediateChanges:false,scrollOnFocus:true,_setIdAttr:"focusNode",_setDisabledAttr:function(_b){
this._set("disabled",_b);
_3.set(this.focusNode,"disabled",_b);
if(this.valueNode){
_3.set(this.valueNode,"disabled",_b);
}
this.focusNode.setAttribute("aria-disabled",_b?"true":"false");
if(_b){
this._set("hovering",false);
this._set("active",false);
var _c="tabIndex" in this.attributeMap?this.attributeMap.tabIndex:("_setTabIndexAttr" in this)?this._setTabIndexAttr:"focusNode";
_1.forEach(_5.isArray(_c)?_c:[_c],function(_d){
var _e=this[_d];
if(_7("webkit")||_a.hasDefaultTabStop(_e)){
_e.setAttribute("tabIndex","-1");
}else{
_e.removeAttribute("tabIndex");
}
},this);
}else{
if(this.tabIndex!=""){
this.set("tabIndex",this.tabIndex);
}
}
},_onFocus:function(by){
if(by=="mouse"&&this.isFocusable()){
var _f=this.connect(this.focusNode,"onfocus",function(){
this.disconnect(_10);
this.disconnect(_f);
});
var _10=this.connect(_8.body(),"onmouseup",function(){
this.disconnect(_10);
this.disconnect(_f);
if(this.focused){
this.focus();
}
});
}
if(this.scrollOnFocus){
this.defer(function(){
_9.scrollIntoView(this.domNode);
});
}
this.inherited(arguments);
},isFocusable:function(){
return !this.disabled&&this.focusNode&&(_4.get(this.domNode,"display")!="none");
},focus:function(){
if(!this.disabled&&this.focusNode.focus){
try{
this.focusNode.focus();
}
catch(e){
}
}
},compare:function(_11,_12){
if(typeof _11=="number"&&typeof _12=="number"){
return (isNaN(_11)&&isNaN(_12))?0:_11-_12;
}else{
if(_11>_12){
return 1;
}else{
if(_11<_12){
return -1;
}else{
return 0;
}
}
}
},onChange:function(){
},_onChangeActive:false,_handleOnChange:function(_13,_14){
if(this._lastValueReported==undefined&&(_14===null||!this._onChangeActive)){
this._resetValue=this._lastValueReported=_13;
}
this._pendingOnChange=this._pendingOnChange||(typeof _13!=typeof this._lastValueReported)||(this.compare(_13,this._lastValueReported)!=0);
if((this.intermediateChanges||_14||_14===undefined)&&this._pendingOnChange){
this._lastValueReported=_13;
this._pendingOnChange=false;
if(this._onChangeActive){
if(this._onChangeHandle){
this._onChangeHandle.remove();
}
this._onChangeHandle=this.defer(function(){
this._onChangeHandle=null;
this.onChange(_13);
});
}
}
},create:function(){
this.inherited(arguments);
this._onChangeActive=true;
},destroy:function(){
if(this._onChangeHandle){
this._onChangeHandle.remove();
this.onChange(this._lastValueReported);
}
this.inherited(arguments);
}});
});
