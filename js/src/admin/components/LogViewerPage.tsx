import app from 'flarum/admin/app';
import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import LogFileList from './LogFileList';
import LogFileState from '../state/LogFileState';

export default class LogViewerPage extends ExtensionPage {
  content() {
    const state = new LogFileState();

    return (
      <div className="container">
        <div className="LogViewerPage">
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
                max: 150,
                required: true,
                label: app.translator.trans('ianm-log-viewer.admin.settings.max-file-size'),
                help: app.translator.trans('ianm-log-viewer.admin.settings.max-file-size-help'),
              })}
              {this.submitButton()}
            </div>
          </div>
          <div className="LogViewerPage--fileList">
            <label for="LogViewerPage--logFileList">{app.translator.trans('ianm-log-viewer.admin.viewer.available_logs_heading')}</label>
            <div className="LogViewerPage--logFileList">
              <LogFileList state={state} />
            </div>
          </div>
        </div>
      </div>
    );
  }
}
