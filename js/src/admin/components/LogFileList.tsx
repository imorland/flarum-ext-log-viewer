import app from 'flarum/admin/app';
import Component, { ComponentAttrs } from 'flarum/common/Component';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import type Mithril from 'mithril';
import LogFileListItem from './LogFileListItem';
import LogFileState from '../state/LogFileState';
import LogFile from '../models/LogFile';

interface LogFileListAttrs extends ComponentAttrs {
  state: LogFileState;
}

export default class LogFileList extends Component<LogFileListAttrs> {
  loading!: boolean;
  files!: LogFile[];
  logState!: LogFileState;

  oninit(vnode: Mithril.Vnode<LogFileListAttrs, this>) {
    super.oninit(vnode);

    this.loading = true;
    this.files = [];

    this.logState = this.attrs.state;

    // Register refresh callback with the logState
    this.logState.setRefreshCallback(() => this.refresh());

    this.refresh();
  }

  view() {
    if (this.loading) {
      return <LoadingIndicator />;
    }

    if (!this.files.length) {
      return (
        <div className="LogViewerPage--emptyList">
          <p>{app.translator.trans('ianm-log-viewer.admin.viewer.no_log_files')}</p>
        </div>
      );
    }

    return (
      <div className="LogViewerPage--fileListItems">
        {this.files.map((file) => {
          return <LogFileListItem file={file} state={this.logState} />;
        })}
      </div>
    );
  }

  refresh() {
    this.loading = true;
    this.files = [];
    m.redraw();

    return app
      .request<{ data: any[] }>({
        method: 'GET',
        url: app.forum.attribute('apiUrl') + '/logs',
      })
      .then((result) => {
        // Deserialise each item into a LogFile model instance via the store
        const items = Array.isArray(result.data) ? result.data : [result.data];
        this.files = items.map((item) => app.store.pushObject(item) as LogFile);
        this.loading = false;
        m.redraw();
      })
      .catch(() => {
        this.loading = false;
        m.redraw();
      });
  }
}
