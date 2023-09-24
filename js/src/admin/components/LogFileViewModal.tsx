import app from 'flarum/admin/app';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import Modal from 'flarum/common/components/Modal';
import LogFileState from '../state/LogFileState';
import LogFile from '../models/LogFile';
import type Mithril from 'mithril';

interface LogFileViewModalAttrs {
  logFileState: LogFileState;
  file: LogFile;
}

export default class LogFileViewModal extends Modal {
  logFileState!: LogFileState;
  file!: LogFile;
  loading: boolean = true;

  oninit(vnode: Mithril.Vnode<LogFileViewModalAttrs>) {
    super.oninit(vnode);
    this.logFileState = vnode.attrs.logFileState;
    this.file = vnode.attrs.file;

    // Load the file content
    this.loadFileContent();
  }

  className() {
    return 'LogFileViewModal Modal--large';
  }

  title() {
    return this.file.fileName();
  }

  async loadFileContent() {
    if (this.loading || !this.file) {
      try {
        await this.logFileState.loadLogFile(this.file.fileName());
        this.loading = false;
        m.redraw();
      } catch (error) {
        console.error('Error loading log file:', error);
      }
    }
  }

  content() {
    if (this.loading || !this.logFileState.getFile()) {
      return (
        <div className="Modal-body">
          <LoadingIndicator />
        </div>
      );
    }

    const file = this.logFileState.getFile();
    const logContent = file.data.attributes.content;
    return (
      <div className="Modal-body">
        <div className="LogViewerPage--fileContent">
          <pre>
            <code className="LogViewerPage--logfile">{logContent}</code>
          </pre>
        </div>
      </div>
    );
  }
}
