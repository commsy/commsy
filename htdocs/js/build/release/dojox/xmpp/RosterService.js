//>>built
define("dojox/xmpp/RosterService",["dijit","dojo","dojox"],function(i,h,g){h.provide("dojox.xmpp.RosterService");g.xmpp.roster={ADDED:101,CHANGED:102,REMOVED:103};h.declare("dojox.xmpp.RosterService",null,{constructor:function(a){this.session=a},addRosterItem:function(a,e,c){if(!a)throw Error("Roster::addRosterItem() - User ID is null");var b={id:this.session.getNextIqId(),from:this.session.jid+"/"+this.session.resource,type:"set"},d=new g.string.Builder(g.xmpp.util.createElement("iq",b,!1));d.append(g.xmpp.util.createElement("query",
{xmlns:"jabber:iq:roster"},!1));a=g.xmpp.util.encodeJid(a);-1==a.indexOf("@")&&(a=a+"@"+this.session.domain);d.append(g.xmpp.util.createElement("item",{jid:a,name:g.xmpp.util.xmlEncode(e)},!1));if(c)for(a=0;a<c.length;a++)d.append("<group>"),d.append(c[a]),d.append("</group>");d.append("</item></query></iq>");c=this.session.dispatchPacket(d.toString(),"iq",b.id);c.addCallback(this,"verifyRoster");return c},updateRosterItem:function(a,e,c){-1==a.indexOf("@")&&(a+=a+"@"+this.session.domain);var b={id:this.session.getNextIqId(),
from:this.session.jid+"/"+this.session.resource,type:"set"},d=new g.string.Builder(g.xmpp.util.createElement("iq",b,!1));d.append(g.xmpp.util.createElement("query",{xmlns:"jabber:iq:roster"},!1));var f=this.session.getRosterIndex(a);if(-1!=f){a={jid:a};if(e)a.name=e;else if(this.session.roster[f].name)a.name=this.session.roster[f].name;if(a.name)a.name=g.xmpp.util.xmlEncode(a.name);d.append(g.xmpp.util.createElement("item",a,!1));if(e=c?c:this.session.roster[f].groups)for(c=0;c<e.length;c++)d.append("<group>"),
d.append(e[c]),d.append("</group>");d.append("</item></query></iq>");b=this.session.dispatchPacket(d.toString(),"iq",b.id);b.addCallback(this,"verifyRoster");return b}},verifyRoster:function(a){if("result"!=a.getAttribute("type"))this.onAddRosterItemFailed(this.session.processXmppError(a));return a},addRosterItemToGroup:function(a,e){if(!a)throw Error("Roster::addRosterItemToGroup() JID is null or undefined");if(!e)throw Error("Roster::addRosterItemToGroup() group is null or undefined");var c=this.session.getRosterIndex(a);
if(-1!=c){for(var b=this.session.roster[c],d=!1,f=0;b<b.groups.length&&!d;f++)b.groups[f]==e&&(d=!0);return!d?this.updateRosterItem(a,b.name,b.groups.concat(e),c):g.xmpp.xmpp.INVALID_ID}},removeRosterGroup:function(a){for(var e=this.session.roster,c=0;c<e.length;c++){var b=e[c];if(0<b.groups.length)for(var d=0;d<b.groups.length;d++)b.groups[d]==a&&(b.groups.splice(d,1),this.updateRosterItem(b.jid,b.name,b.groups))}},renameRosterGroup:function(a,e){for(var c=this.session.roster,b=0;b<c.length;b++){var d=
c[b];if(0<d.groups.length)for(var f=0;f<d.groups.length;f++)d.groups[f]==a&&(d.groups[f]=e,this.updateRosterItem(d.jid,d.name,d.groups))}},removeRosterItemFromGroup:function(a,e){if(!a)throw Error("Roster::addRosterItemToGroup() JID is null or undefined");if(!e)throw Error("Roster::addRosterItemToGroup() group is null or undefined");var c=this.session.getRosterIndex(a);if(-1!=c){for(var b=this.session.roster[c],d=!1,f=0;f<b.groups.length&&!d;f++)b.groups[f]==e&&(d=!0,c=f);return!0==d?(b.groups.splice(c,
1),this.updateRosterItem(a,b.name,b.groups)):g.xmpp.xmpp.INVALID_ID}},rosterItemRenameGroup:function(a,e,c){if(!a)throw Error("Roster::rosterItemRenameGroup() JID is null or undefined");if(!c)throw Error("Roster::rosterItemRenameGroup() group is null or undefined");var b=this.session.getRosterIndex(a);if(-1!=b){for(var b=this.session.roster[b],d=!1,f=0;f<b.groups.length&&!d;f++)b.groups[f]==e&&(b.groups[f]=c,d=!0);return!0==d?this.updateRosterItem(a,b.name,b.groups):g.xmpp.xmpp.INVALID_ID}},renameRosterItem:function(a,
e){if(!a)throw Error("Roster::addRosterItemToGroup() JID is null or undefined");if(!e)throw Error("Roster::addRosterItemToGroup() New Name is null or undefined");var c=this.session.getRosterIndex(a);return-1==c?void 0:this.updateRosterItem(a,e,this.session.roster.groups,c)},removeRosterItem:function(a){if(!a)throw Error("Roster::addRosterItemToGroup() JID is null or undefined");var e={id:this.session.getNextIqId(),from:this.session.jid+"/"+this.session.resource,type:"set"},c=new g.string.Builder(g.xmpp.util.createElement("iq",
e,!1));c.append(g.xmpp.util.createElement("query",{xmlns:"jabber:iq:roster"},!1));-1==a.indexOf("@")&&(a+=a+"@"+this.session.domain);c.append(g.xmpp.util.createElement("item",{jid:a,subscription:"remove"},!0));c.append("</query></iq>");a=this.session.dispatchPacket(c.toString(),"iq",e.id);a.addCallback(this,"verifyRoster");return a},getAvatar:function(){},publishAvatar:function(){},onVerifyRoster:function(){},onVerifyRosterFailed:function(){}})});