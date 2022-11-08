(()=>{var t={n:e=>{var i=e&&e.__esModule?()=>e.default:()=>e;return t.d(i,{a:i}),i},d:(e,i)=>{for(var n in i)t.o(i,n)&&!t.o(e,n)&&Object.defineProperty(e,n,{enumerable:!0,get:i[n]})},o:(t,e)=>Object.prototype.hasOwnProperty.call(t,e),r:t=>{"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(t,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(t,"__esModule",{value:!0})}},e={};(()=>{"use strict";t.r(e);const i=flarum.core.compat["admin/app"];var n=t.n(i);function o(t,e){return o=Object.setPrototypeOf?Object.setPrototypeOf.bind():function(t,e){return t.__proto__=e,t},o(t,e)}function r(t,e){t.prototype=Object.create(e.prototype),t.prototype.constructor=t,o(t,e)}const a=flarum.core.compat["admin/components/ExtensionPage"];var s=t.n(a);const l=flarum.core.compat["common/Component"];var u=t.n(l);const c=flarum.core.compat["common/components/LoadingIndicator"];var f=t.n(c);const p=flarum.core.compat["common/components/Button"];var d=t.n(p);const h=flarum.core.compat["common/utils/humanTime"];var v=t.n(h);const g=flarum.core.compat["common/helpers/icon"];var y=t.n(g),w=function(t){function e(){return t.apply(this,arguments)||this}r(e,t);var i=e.prototype;return i.oninit=function(e){t.prototype.oninit.call(this,e),this.file=this.attrs.file,this.state=this.attrs.state},i.view=function(){var t=this,e=this.file;return m("div",{className:"LogFile-item"},m(d(),{className:"Button Button--logFile",onclick:function(){t.setFile(e.fileName())}},m("div",null,m("div",{className:"fileName"},y()("far fa-file-alt"),m("code",null,e.fileName())),m("div",{className:"fileDate"},n().translator.trans("ianm-log-viewer.admin.viewer.last_updated",{updated:v()(e.modified())})),m("div",{className:"fileInfo"},n().translator.trans("ianm-log-viewer.admin.viewer.file_size",{size:e.size()})))))},i.setFile=function(t){this.state.loadLogFile(t)},e}(u()),b=function(t){function e(){return t.apply(this,arguments)||this}r(e,t);var i=e.prototype;return i.oninit=function(e){t.prototype.oninit.call(this,e),this.loading=!0,this.files=[],this.state=this.attrs.state,this.refresh()},i.view=function(){var t=this;return this.loading?m(f(),null):m("div",null,m("div",null,this.files.map((function(e){return m(w,{file:e,state:t.state})}))))},i.refresh=function(t){return void 0===t&&(t=!0),t&&(this.loading=!0,this.files=[]),this.loadResults().then(this.parseResults.bind(this))},i.loadResults=function(){return n().store.find("logs")},i.parseResults=function(t){var e;return(e=this.files).push.apply(e,t),this.loading=!1,m.redraw(),t},e}(u()),N=function(t){function e(){return t.apply(this,arguments)||this}r(e,t);var i=e.prototype;return i.oninit=function(e){t.prototype.oninit.call(this,e),this.state=this.attrs.state},i.view=function(){var t,e;if(null==(t=(e=this.state).getFile)||!t.call(e))return m("div",{className:"LogViewerPage--No-File"},m("p",null,n().translator.trans("ianm-log-viewer.admin.viewer.no_file_selected")));var i=this.state.getFile().data.attributes.content;return m("div",{className:"LogViewerPage--fileContent"},m("pre",null,i))},e}(u()),_=function(){function t(){this.file=null}var e=t.prototype;return e.loadLogFile=function(t){var e=this;n().request({method:"GET",url:n().forum.attribute("apiUrl")+"/logs/"+t}).then((function(t){e.file=t,m.redraw()}))},e.getFile=function(){return this.file},t}(),P=function(t){function e(){return t.apply(this,arguments)||this}return r(e,t),e.prototype.content=function(){var t=new _;return m("div",{className:"container"},m("div",{className:"LogViewerPage"},m("div",{className:"LogViewerPage--fileList"},m("h3",null,n().translator.trans("ianm-log-viewer.admin.viewer.available_logs_heading")),m(b,{state:t})),m("div",{className:"LogViewerPage--container"},m("h3",null,n().translator.trans("ianm-log-viewer.admin.viewer.file_contents_heading")),m("div",null,m(N,{className:"LogViewerPage--fileContent",state:t})))))},e}(s());const L=flarum.core.compat["common/Model"];var F=t.n(L),O=function(t){function e(){return t.apply(this,arguments)||this}r(e,t);var i=e.prototype;return i.fileName=function(){return F().attribute("fileName").call(this)},i.fullPath=function(){return F().attribute("fullPath").call(this)},i.size=function(){return F().attribute("size").call(this)},i.modified=function(){return F().attribute("modified",F().transformDate).call(this)},i.content=function(){return F().attribute("content").call(this)},e}(F());n().initializers.add("ianm-log-viewer",(function(){n().store.models.logs=O,n().extensionData.for("ianm-log-viewer").registerPermission({icon:"far fa-file-alt",label:n().translator.trans("ianm-log-viewer.admin.permissions.access_logfile_api"),permission:"readLogfiles"},"view").registerPage(P)}))})(),module.exports=e})();
//# sourceMappingURL=admin.js.map