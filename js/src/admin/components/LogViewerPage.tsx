import app from 'flarum/admin/app';
import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import LogFileList from './LogFileList';
import LogFileViewer from './LogFileViewer';
import LogFileState from '../state/LogFileState';

export default class LogViewerPage extends ExtensionPage {
  content() {
    const state = new LogFileState();

    return (
      <div className="container">
        <div className="LogViewerPage">
          <div className="LogViewerPage--fileList">
            <h3>{app.translator.trans('ianm-log-viewer.admin.viewer.available_logs_heading')}</h3>
            <LogFileList state={state} />
          </div>
          <div className="LogViewerPage--container">
            <h3>{app.translator.trans('ianm-log-viewer.admin.viewer.file_contents_heading')}</h3>
            {/* Note to self: would be nice to show the filename here? */}
            <LogFileViewer className="LogViewerPage--fileContent" state={state} />
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
      </div>
    );
  }
}
