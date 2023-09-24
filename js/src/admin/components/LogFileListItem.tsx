import app from 'flarum/admin/app';
import Component, { ComponentAttrs } from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';
import humanTime from 'flarum/common/utils/humanTime';
import icon from 'flarum/common/helpers/icon';
import LogFile from '../models/LogFile';
import LogFileState from '../state/LogFileState';
import Mithril from 'mithril';
import LogFileViewModal from './LogFileViewModal';
import ItemList from 'flarum/common/utils/ItemList';
import LabelValue from 'flarum/common/components/LabelValue';
import Tooltip from 'flarum/common/components/Tooltip';

interface LogFileListItemAttrs extends ComponentAttrs {
  file: LogFile;
  state: LogFileState;
}

export default class LogFileListItem extends Component<LogFileListItemAttrs> {
  file!: LogFile;
  logFileState!: LogFileState;

  oninit(vnode: Mithril.Vnode<LogFileListItemAttrs, this>) {
    super.oninit(vnode);
    this.file = vnode.attrs.file;
    this.logFileState = vnode.attrs.state;
  }

  view() {
    return (
      <div className="LogFile-item">
        <div className="LogFile-item--icon">{icon('fas fa-file-alt')}</div>
        <div className="LogFile-item--info">{this.infoItems().toArray()}</div>
        <div className="LogFile-item--actions">{this.actionItems().toArray()}</div>
      </div>
    );
  }

  infoItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'fileName',
      <div className="fileName">
        <code>{this.file.fileName()}</code>
      </div>,
      100
    );

    items.add(
      'fileDate',
      <div className="fileDate">
        <LabelValue label={app.translator.trans('ianm-log-viewer.admin.viewer.last_updated')} value={humanTime(this.file.modified())} />
      </div>,
      80
    );

    items.add(
      'fileInfo',
      <div className="fileInfo">
        <LabelValue label={app.translator.trans('ianm-log-viewer.admin.viewer.size_label')} value={this.file.formattedSize()} />
      </div>,
      60
    );

    return items;
  }

  actionItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();
    const fileName = this.file.fileName();

    items.add(
      'view',
      <Tooltip text={app.translator.trans('ianm-log-viewer.admin.viewer.view_label')}>
        <Button
          icon="fas fa-eye"
          className="Button Button--icon"
          onclick={() => {
            app.modal.show(LogFileViewModal, { logFileState: this.logFileState, file: this.file });
          }}
        />
      </Tooltip>,
      50
    );

    items.add(
      'download',
      <Tooltip text={app.translator.trans('ianm-log-viewer.admin.viewer.download_label')}>
        <Button
          icon="fas fa-download"
          className="Button Button--icon"
          onclick={() => {
            this.logFileState.downloadFile(fileName);
          }}
        />
      </Tooltip>,
      40
    );

    items.add(
      'delete',
      <Tooltip text={app.translator.trans('ianm-log-viewer.admin.viewer.delete_label')}>
        <Button
          icon="fas fa-trash-alt"
          className="Button Button--icon Button--danger"
          onclick={() => {
            this.logFileState.deleteFile(fileName);
          }}
        />
      </Tooltip>,
      20
    );

    return items;
  }
}
