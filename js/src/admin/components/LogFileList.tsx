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

    return (
      <div className="LogViewerPage--fileListItems">
        {this.files.map((file) => {
          return <LogFileListItem file={file} state={this.logState} />;
        })}
      </div>
    );
  }

  refresh(clear: boolean = true) {
    if (clear) {
      this.loading = true;
      this.files = [];
    }

    return this.loadResults().then(this.parseResults.bind(this));
  }

  loadResults() {
    return app.store.find('logs');
  }

  parseResults(results: any) {
    this.files = Array.isArray(results) ? results : [results];

    this.loading = false;

    m.redraw();
    return results;
  }
}
