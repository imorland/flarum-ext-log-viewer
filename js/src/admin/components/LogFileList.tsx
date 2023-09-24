import app from 'flarum/admin/app';
import Component, { ComponentAttrs } from 'flarum/common/Component';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import type Mithril from 'mithril';
import LogFileListItem from './LogFileListItem';
import LogFile from '../models/LogFile';
import LogFileState from '../state/LogFileState';

interface LogFileListAttrs extends ComponentAttrs {
  state: LogFileState;
}

export default class LogFileList extends Component<LogFileListAttrs> {
  loading: boolean = true;
  files: LogFile[] = [];
  logFileState!: LogFileState;

  oninit(vnode: Mithril.Vnode<LogFileListAttrs, this>) {
    super.oninit(vnode);

    this.logFileState = vnode.attrs.state;
    this.refresh();
  }

  view() {
    if (this.loading) {
      return <LoadingIndicator />;
    }
    return (
      <div className="LogViewerPage--fileListItems">
        {this.files.map((file) => {
          return <LogFileListItem file={file} state={this.logFileState} />;
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

  async loadResults(): Promise<LogFile[]> {
    const results = await app.store.find('logs');
    return results as unknown as Promise<LogFile[]>;
  }

  parseResults(results: LogFile[]) {
    this.files.push(...results);
    this.loading = false;
    m.redraw();
    return results;
  }
}
