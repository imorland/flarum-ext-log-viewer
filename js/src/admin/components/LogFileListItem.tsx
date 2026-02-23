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

    return (
      <div className="LogFile-item">
        <div className={classList('LogFile-itemWrapper', { active: selected })}>
          <div className="LogFile-info">
            <div className="fileName">
              <Icon name="far fa-file-alt" />
              <code>{file.relativePath()}</code>
            </div>
            <div className="fileDate">
              {app.translator.trans('ianm-log-viewer.admin.viewer.last_updated', {
                updated: humanTime(file.modified()),
              })}
            </div>
            <div className="fileInfo">
              {app.translator.trans('ianm-log-viewer.admin.viewer.file_size', {
                size: file.size(),
              })}
            </div>
          </div>
          <div className="LogFile-actions">
            <Tooltip text={app.translator.trans('ianm-log-viewer.admin.viewer.view_log')}>
              <Button className="Button Button--icon" icon="fas fa-eye" onclick={() => this.setFile(file.relativePath())} />
            </Tooltip>
            <Tooltip text={app.translator.trans('ianm-log-viewer.admin.viewer.download_log')}>
              <Button className="Button Button--icon" icon="fas fa-download" onclick={() => this.downloadFile(file.relativePath())} />
            </Tooltip>
            <Tooltip text={app.translator.trans('ianm-log-viewer.admin.viewer.delete_log')}>
              <Button
                className="Button Button--icon Button--danger"
                icon="fas fa-trash"
                loading={this.loading}
                onclick={() => this.deleteFile(file.relativePath())}
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
