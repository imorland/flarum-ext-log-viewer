import app from 'flarum/admin/app';
import Component from 'flarum/common/Component';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import type Mithril from 'mithril';
import LogFileListItem from './LogFileListItem';

export default class LogFileList extends Component {
  oninit(vnode) {
    super.oninit(vnode);

    this.loading = true;
    this.files = [];

    this.state = this.attrs.state;

    this.refresh();
  }

  view() {
    if (this.loading) {
      return <LoadingIndicator />;
    }

    return (
      <div>
        <div>
          {this.files.map((file) => {
            return <LogFileListItem file={file} state={this.state} />;
          })}
        </div>
      </div>
    );
  }

  refresh(clear = true) {
    if (clear) {
      this.loading = true;
      this.files = [];
    }

    return this.loadResults().then(this.parseResults.bind(this));
  }

  loadResults() {
    return app.store.find('logs');
  }

  parseResults(results) {
    this.files.push(...results);

    this.loading = false;

    m.redraw();
    return results;
  }
}
