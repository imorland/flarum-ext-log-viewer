import app from 'flarum/admin/app';
import Component from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';
import humanTime from 'flarum/common/utils/humanTime';
import classList from 'flarum/common/utils/classList';
import icon from 'flarum/common/helpers/icon';

export default class LogFileListItem extends Component {
  oninit(vnode) {
    super.oninit(vnode);

    this.file = this.attrs.file;
    this.state = this.attrs.state;
  }

  view() {
    const file = this.file;
    const selected = this.state?.file?.data.id === file?.data.id;

    return (
      <div className="LogFile-item">
        <Button
          className={classList('Button Button--logFile', { active: selected })}
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
