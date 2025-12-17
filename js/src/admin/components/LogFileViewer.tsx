import app from 'flarum/admin/app';
import Component, { ComponentAttrs } from 'flarum/common/Component';
import type Mithril from 'mithril';
import LogFileState from '../state/LogFileState';

interface LogFileViewerAttrs extends ComponentAttrs {
  state: LogFileState;
}

export default class LogFileViewer extends Component<LogFileViewerAttrs> {
  logState!: LogFileState;

  oninit(vnode: Mithril.Vnode<LogFileViewerAttrs, this>) {
    super.oninit(vnode);

    this.logState = this.attrs.state;
  }

  view() {
    if (!this.logState.getFile?.()) {
      return (
        <div className="LogViewerPage--No-File">
          <p>{app.translator.trans('ianm-log-viewer.admin.viewer.no_file_selected')}</p>
        </div>
      );
    }

    const file = this.logState.getFile();
    const content = file['data']['attributes']['content'];

    return (
      <div className="LogViewerPage--fileContent">
        <pre>{content}</pre>
      </div>
    );
  }
}
