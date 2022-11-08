import app from 'flarum/admin/app';
import Component from 'flarum/common/Component';

export default class LogFileViewer extends Component {
  oninit(vnode) {
    super.oninit(vnode);

    this.state = this.attrs.state;
  }

  view() {
    if (!this.state.getFile?.()) {
      return (
        <div className="LogViewerPage--No-File">
          <p>{app.translator.trans('ianm-log-viewer.admin.viewer.no_file_selected')}</p>
        </div>
      );
    }

    const file = this.state.getFile();
    const content = file['data']['attributes']['content'];

    return (
      <div className="LogViewerPage--fileContent">
        <pre>{m.trust(content)}</pre>
      </div>
    );
  }
}
