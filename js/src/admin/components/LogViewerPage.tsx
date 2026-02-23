import app from 'flarum/admin/app';
import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import { type ExtensionPageAttrs } from 'flarum/admin/components/ExtensionPage';
import type Mithril from 'mithril';
import LogFileList from './LogFileList';
import LogFileViewer from './LogFileViewer';
import LogFileState from '../state/LogFileState';

export default class LogViewerPage extends ExtensionPage {
  logState!: LogFileState;

  oninit(vnode: Mithril.Vnode<ExtensionPageAttrs, this>) {
    super.oninit(vnode);
    this.logState = new LogFileState();
  }

  content() {
    const selectedFile = this.logState.getFile();
    const selectedFileName = selectedFile?.data?.attributes?.fileName as string | undefined;

    return (
      <div className="container">
        <div className="LogViewerPage">
          <div className="LogViewerPage--fileList">
            <h3>{app.translator.trans('ianm-log-viewer.admin.viewer.available_logs_heading')}</h3>
            <LogFileList state={this.logState} />
          </div>
          <div className="LogViewerPage--container">
            <div className="LogViewerPage--contentHeader">
              <h3>
                {selectedFileName
                  ? selectedFileName
                  : app.translator.trans('ianm-log-viewer.admin.viewer.file_contents_heading')}
              </h3>
            </div>
            <LogFileViewer state={this.logState} />
          </div>
        </div>
        <div className="LogViewerPage-settings">
          <div className="Form-group">
            {this.buildSettingComponent({
              setting: 'ianm-log-viewer.purge-days',
              type: 'number',
              min: 0,
              max: 365,
              required: true,
              label: app.translator.trans('ianm-log-viewer.admin.settings.purge-days'),
              help: app.translator.trans('ianm-log-viewer.admin.settings.purge-days-help'),
            })}
            {this.buildSettingComponent({
              setting: 'ianm-log-viewer.max-file-size',
              type: 'number',
              min: 0,
              max: 100,
              required: true,
              label: app.translator.trans('ianm-log-viewer.admin.settings.max-file-size'),
              help: app.translator.trans('ianm-log-viewer.admin.settings.max-file-size-help'),
            })}
            {this.submitButton()}
          </div>
        </div>
      </div>
    );
  }
}
