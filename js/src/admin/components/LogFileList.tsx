import app from 'flarum/admin/app';
import Component, { ComponentAttrs } from 'flarum/common/Component';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import Button from 'flarum/common/components/Button';
import type Mithril from 'mithril';
import LogFileListItem from './LogFileListItem';
import LogFileState from '../state/LogFileState';

interface LogFileListAttrs extends ComponentAttrs {
  state: LogFileState;
}

export default class LogFileList extends Component<LogFileListAttrs> {
  oninit(vnode: Mithril.Vnode<LogFileListAttrs, this>) {
    super.oninit(vnode);

    this.attrs.state.loadList();
  }

  view() {
    const state = this.attrs.state;

    if (!state.listLoaded) {
      if (state.listLoading) return <LoadingIndicator />;

      return (
        <div className="LogViewerPage--emptyList">
          <p>{app.translator.trans('ianm-log-viewer.admin.viewer.list_failed')}</p>
          <Button className="Button" icon="fas fa-redo" onclick={() => state.refreshList()}>
            {app.translator.trans('ianm-log-viewer.admin.viewer.retry')}
          </Button>
        </div>
      );
    }

    if (!state.files.length) {
      return (
        <div className="LogViewerPage--emptyList">
          <p>{app.translator.trans('ianm-log-viewer.admin.viewer.no_log_files')}</p>
        </div>
      );
    }

    return (
      <div className="LogViewerPage--fileListItems">
        {state.files.map((file) => {
          return <LogFileListItem key={file.relativePath()} file={file} state={state} />;
        })}
      </div>
    );
  }
}
