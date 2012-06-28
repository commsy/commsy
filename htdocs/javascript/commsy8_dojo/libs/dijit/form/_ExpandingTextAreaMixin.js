//>>built
define("dijit/form/_ExpandingTextAreaMixin",["dojo/_base/declare","dojo/dom-construct","dojo/_base/lang","dojo/_base/window"],function(_1,_2,_3,_4){
var _5;
return _1("dijit.form._ExpandingTextAreaMixin",null,{_setValueAttr:function(){
this.inherited(arguments);
this.resize();
},postCreate:function(){
this.inherited(arguments);
var _6=this.textbox;
if(_5==undefined){
var te=_2.create("textarea",{rows:"5",cols:"20",value:" ",style:{zoom:1,overflow:"hidden",visibility:"hidden",position:"absolute",border:"0px solid black",padding:"0px"}},_4.body(),"last");
_5=te.scrollHeight>=te.clientHeight;
_4.body().removeChild(te);
}
this.connect(_6,"onscroll","_resizeLater");
this.connect(_6,"onresize","_resizeLater");
this.connect(_6,"onfocus","_resizeLater");
_6.style.overflowY="hidden";
this._estimateHeight();
this._resizeLater();
},_onInput:function(e){
this.inherited(arguments);
this.resize();
},_estimateHeight:function(){
var _7=this.textbox;
_7.style.height="auto";
_7.rows=(_7.value.match(/\n/g)||[]).length+2;
},_resizeLater:function(){
this.defer("resize");
},resize:function(){
function _8(){
var _9=false;
if(_a.value===""){
_a.value=" ";
_9=true;
}
var sh=_a.scrollHeight;
if(_9){
_a.value="";
}
return sh;
};
var _a=this.textbox;
if(_a.style.overflowY=="hidden"){
_a.scrollTop=0;
}
if(this.busyResizing){
return;
}
this.busyResizing=true;
if(_8()||_a.offsetHeight){
var _b=_a.style.height;
if(!(/px/.test(_b))){
_b=_8();
_a.rows=1;
_a.style.height=_b+"px";
}
var _c=Math.max(Math.max(_a.offsetHeight,parseInt(_b))-_a.clientHeight,0)+_8();
var _d=_c+"px";
if(_d!=_a.style.height){
_a.rows=1;
_a.style.height=_d;
}
if(_5){
var _e=_8(),_f=_e,_10=_a.style.minHeight,_11=4,_12;
_a.style.minHeight=_d;
_a.style.height="auto";
while(_c>0){
_a.style.minHeight=Math.max(_c-_11,4)+"px";
_12=_8();
var _13=_f-_12;
_c-=_13;
if(_13<_11){
break;
}
_f=_12;
_11<<=1;
}
_a.style.height=_c+"px";
_a.style.minHeight=_10;
}
_a.style.overflowY=_8()>_a.clientHeight?"auto":"hidden";
}else{
this._estimateHeight();
}
this.busyResizing=false;
}});
});
