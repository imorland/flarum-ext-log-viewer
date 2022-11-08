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
        </div>
      </div>
    );
  }
}
