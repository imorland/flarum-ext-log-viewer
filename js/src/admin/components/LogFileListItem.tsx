import app from 'flarum/admin/app';
import Component from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';
import humanTime from 'flarum/common/utils/humanTime';
import icon from 'flarum/common/helpers/icon';

export default class LogFileListItem extends Component {
  oninit(vnode) {
    super.oninit(vnode);

    this.file = this.attrs.file;
    this.state = this.attrs.state;
  }

  view() {
    const file = this.file;
    return (
      <div className="LogFile-item">
        <Button
          className="Button Button--logFile"
          onclick={() => {
            this.setFile(file.fileName());
          }}
        >
          <div>
            <div className="fileName">
              {icon('far fa-file-alt')}
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
        </Button>
      </div>
    );
  }

  setFile(fileName: string) {
    this.state.loadLogFile(fileName);
  }
}
