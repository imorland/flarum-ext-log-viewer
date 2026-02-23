import app from 'flarum/admin/app';
import Component, { ComponentAttrs } from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';
import humanTime from 'flarum/common/utils/humanTime';
import classList from 'flarum/common/utils/classList';
import Icon from 'flarum/common/components/Icon';
import Tooltip from 'flarum/common/components/Tooltip';
import type Mithril from 'mithril';
import LogFileState from '../state/LogFileState';
import LogFile from '../models/LogFile';

interface LogFileListItemAttrs extends ComponentAttrs {
  file: LogFile;
  state: LogFileState;
}

function humanFileSize(bytes: number): string {
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
  return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
}

export default class LogFileListItem extends Component<LogFileListItemAttrs> {
  file!: LogFile;
  logState!: LogFileState;
  loading!: boolean;

  oninit(vnode: Mithril.Vnode<LogFileListItemAttrs, this>) {
    super.oninit(vnode);

    this.file = this.attrs.file;
    this.logState = this.attrs.state;
    this.loading = false;
  }

  view() {
    const file = this.file;
    const currentRelativePath = this.logState?.file?.data?.attributes?.relativePath;
    const selected = currentRelativePath === file.relativePath();
    const relativePath = file.relativePath() ?? '';
    const parts = relativePath.split('/');
    const fileName = parts[parts.length - 1];
    const dirName = parts.length > 1 ? parts.slice(0, -1).join('/') + '/' : null;

    return (
      <div className="LogFile-item">
        <div
          className={classList('LogFile-itemWrapper', { active: selected })}
          role="button"
          tabIndex={0}
          onclick={() => this.setFile(relativePath)}
          onkeydown={(e: KeyboardEvent) => e.key === 'Enter' && this.setFile(relativePath)}
        >
          <div className="LogFile-info">
            <div className="fileName">
              <Icon name="far fa-file-alt" />
              <span className="fileName-text">
                {dirName && <span className="fileName-dir">{dirName}</span>}
                <span className="fileName-name">{fileName}</span>
              </span>
            </div>
            <div className="fileMeta">
              <span className="fileSize">{humanFileSize(file.size() ?? 0)}</span>
              <span className="fileDot">·</span>
              <span className="fileDate">{humanTime(file.modified())}</span>
            </div>
          </div>
          <div className="LogFile-actions" onclick={(e: MouseEvent) => e.stopPropagation()}>
            <Tooltip text={app.translator.trans('ianm-log-viewer.admin.viewer.download_log')}>
              <Button className="Button Button--icon" icon="fas fa-download" onclick={() => this.downloadFile(relativePath)} />
            </Tooltip>
            <Tooltip text={app.translator.trans('ianm-log-viewer.admin.viewer.delete_log')}>
              <Button
                className="Button Button--icon Button--danger"
                icon="fas fa-trash"
                loading={this.loading}
                onclick={() => this.deleteFile(relativePath)}
              />
            </Tooltip>
          </div>
        </div>
      </div>
    );
  }

  setFile(relativePath: string) {
    this.logState.loadLogFile(relativePath);
  }

  downloadFile(relativePath: string) {
    this.logState.downloadFile(relativePath);
  }

  deleteFile(relativePath: string) {
    this.loading = true;
    this.logState.deleteFile(relativePath).then(() => {
      this.loading = false;
      m.redraw();
    });
  }
}
