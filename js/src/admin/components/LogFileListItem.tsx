import app from 'flarum/admin/app';
import Component from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';
import humanTime from 'flarum/common/utils/humanTime';
import classList from 'flarum/common/utils/classList';
import Icon from 'flarum/common/components/Icon';
import Tooltip from 'flarum/common/components/Tooltip';

export default class LogFileListItem extends Component {
  oninit(vnode) {
    super.oninit(vnode);

    this.file = this.attrs.file;
    this.state = this.attrs.state;
    this.loading = false;
  }

  view() {
    const file = this.file;
    const selected = this.state?.file?.data.id === file?.data.id;

    return (
      <div className="LogFile-item">
        <div className={classList('LogFile-itemWrapper', { active: selected })}>
          <div className="LogFile-info">
            <div className="fileName">
              <Icon name="far fa-file-alt" />
              <code>{file.fileName()}</code>
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
              <Button className="Button Button--icon" icon="fas fa-eye" onclick={() => this.setFile(file.fileName())} />
            </Tooltip>
            <Tooltip text={app.translator.trans('ianm-log-viewer.admin.viewer.download_log')}>
              <Button className="Button Button--icon" icon="fas fa-download" onclick={() => this.downloadFile(file.fileName())} />
            </Tooltip>
            <Tooltip text={app.translator.trans('ianm-log-viewer.admin.viewer.delete_log')}>
              <Button
                className="Button Button--icon Button--danger"
                icon="fas fa-trash"
                loading={this.loading}
                onclick={() => this.deleteFile(file.fileName())}
              />
            </Tooltip>
          </div>
        </div>
      </div>
    );
  }

  setFile(fileName: string) {
    this.state.loadLogFile(fileName);
  }

  downloadFile(fileName: string) {
    this.state.downloadFile(fileName);
  }

  deleteFile(fileName: string) {
    this.loading = true;
    this.state.deleteFile(fileName).then(() => {
      this.loading = false;
      m.redraw();
    });
  }
}
