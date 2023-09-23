(()=>{var t={n:e=>{var i=e&&e.__esModule?()=>e.default:()=>e;return t.d(i,{a:i}),i},d:(e,i)=>{for(var n in i)t.o(i,n)&&!t.o(e,n)&&Object.defineProperty(e,n,{enumerable:!0,get:i[n]})},o:(t,e)=>Object.prototype.hasOwnProperty.call(t,e),r:t=>{"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(t,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(t,"__esModule",{value:!0})}},e={};(()=>{"use strict";t.r(e);const i=flarum.core.compat["admin/app"];var n=t.n(i);function a(t,e){return a=Object.setPrototypeOf?Object.setPrototypeOf.bind():function(t,e){return t.__proto__=e,t},a(t,e)}function r(t,e){t.prototype=Object.create(e.prototype),t.prototype.constructor=t,a(t,e)}const o=flarum.core.compat["admin/components/ExtensionPage"];var s=t.n(o);const l=flarum.core.compat["common/Component"];var u=t.n(l);const c=flarum.core.compat["common/components/LoadingIndicator"];var f=t.n(c);const d=flarum.core.compat["common/components/Button"];var p=t.n(d);const g=flarum.core.compat["common/utils/humanTime"];var h=t.n(g);const v=flarum.core.compat["common/utils/classList"];var w=t.n(v);const y=flarum.core.compat["common/helpers/icon"];var b=t.n(y),N=function(t){function e(){return t.apply(this,arguments)||this}r(e,t);var i=e.prototype;return i.oninit=function(e){t.prototype.oninit.call(this,e),this.file=this.attrs.file,this.state=this.attrs.state},i.view=function(){var t,e=this,i=this.file,a=(null==(t=this.state)||null==(t=t.file)?void 0:t.data.id)===(null==i?void 0:i.data.id);return m("div",{className:"LogFile-item"},m(p(),{className:w()("Button Button--logFile",{active:a}),onclick:function(){e.setFile(i.fileName())}},m("div",null,m("div",{className:"fileName"},b()("far fa-file-alt"),m("code",null,i.fileName())),m("div",{className:"fileDate"},n().translator.trans("ianm-log-viewer.admin.viewer.last_updated",{updated:h()(i.modified())})),m("div",{className:"fileInfo"},n().translator.trans("ianm-log-viewer.admin.viewer.file_size",{size:i.size()})))))},i.setFile=function(t){this.state.loadLogFile(t)},e}(u()),P=function(t){function e(){return t.apply(this,arguments)||this}r(e,t);var i=e.prototype;return i.oninit=function(e){t.prototype.oninit.call(this,e),this.loading=!0,this.files=[],this.state=this.attrs.state,this.refresh()},i.view=function(){var t=this;return this.loading?m(f(),null):m("div",{className:"LogViewerPage--fileListItems"},this.files.map((function(e){return m(N,{file:e,state:t.state})})))},i.refresh=function(t){return void 0===t&&(t=!0),t&&(this.loading=!0,this.files=[]),this.loadResults().then(this.parseResults.bind(this))},i.loadResults=function(){return n().store.find("logs")},i.parseResults=function(t){var e;return(e=this.files).push.apply(e,t),this.loading=!1,m.redraw(),t},e}(u()),_=function(t){function e(){return t.apply(this,arguments)||this}r(e,t);var i=e.prototype;return i.oninit=function(e){t.prototype.oninit.call(this,e),this.state=this.attrs.state},i.view=function(){var t,e;if(null==(t=(e=this.state).getFile)||!t.call(e))return m("div",{className:"LogViewerPage--No-File"},m("p",null,n().translator.trans("ianm-log-viewer.admin.viewer.no_file_selected")));var i=this.state.getFile().data.attributes.content;return m("div",{className:"LogViewerPage--fileContent"},m("pre",null,i))},e}(u()),L=function(){function t(){this.file=null}var e=t.prototype;return e.loadLogFile=function(t){var e=this;n().request({method:"GET",url:n().forum.attribute("apiUrl")+"/logs/"+t}).then((function(t){e.file=t,m.redraw()}))},e.getFile=function(){return this.file},t}(),F=function(t){function e(){return t.apply(this,arguments)||this}return r(e,t),e.prototype.content=function(){var t=new L;return m("div",{className:"container"},m("div",{className:"LogViewerPage"},m("div",{className:"LogViewerPage--fileList"},m("h3",null,n().translator.trans("ianm-log-viewer.admin.viewer.available_logs_heading")),m(P,{state:t})),m("div",{className:"LogViewerPage--container"},m("h3",null,n().translator.trans("ianm-log-viewer.admin.viewer.file_contents_heading")),m(_,{className:"LogViewerPage--fileContent",state:t})),m("div",{className:"LogViewerPage-settings"},m("div",{className:"Form-group"},this.buildSettingComponent({setting:"ianm-log-viewer.purge-days",type:"number",min:0,max:365,required:!0,label:n().translator.trans("ianm-log-viewer.admin.settings.purge-days"),help:n().translator.trans("ianm-log-viewer.admin.settings.purge-days-help")}),this.buildSettingComponent({setting:"ianm-log-viewer.max-file-size",type:"number",min:0,max:100,required:!0,label:n().translator.trans("ianm-log-viewer.admin.settings.max-file-size"),help:n().translator.trans("ianm-log-viewer.admin.settings.max-file-size-help")}),this.submitButton()))))},e}(s());const O=flarum.core.compat["common/Model"];var z=t.n(O),x=function(t){function e(){return t.apply(this,arguments)||this}r(e,t);var i=e.prototype;return i.fileName=function(){return z().attribute("fileName").call(this)},i.fullPath=function(){return z().attribute("fullPath").call(this)},i.size=function(){return z().attribute("size").call(this)},i.modified=function(){return z().attribute("modified",z().transformDate).call(this)},i.content=function(){return z().attribute("content").call(this)},e}(z());n().initializers.add("ianm-log-viewer",(function(){n().store.models.logs=x,n().extensionData.for("ianm-log-viewer").registerPermission({icon:"far fa-file-alt",label:n().translator.trans("ianm-log-viewer.admin.permissions.access_logfile_api"),permission:"readLogfiles"},"view").registerPage(F)}))})(),module.exports=e})();
//# sourceMappingURL=admin.js.map