import app from 'flarum/admin/app';
import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import { type ExtensionPageAttrs } from 'flarum/admin/components/ExtensionPage';
import type { TabItem } from 'flarum/common/components/Tabs';
import type ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';
import LogFileList from './LogFileList';
import LogFileViewer from './LogFileViewer';
import LogFileState from '../state/LogFileState';

export default class LogViewerPage extends ExtensionPage {
  logState!: LogFileState;

  oninit(vnode: Mithril.Vnode<ExtensionPageAttrs, this>) {
    super.oninit(vnode);
    this.logState = new LogFileState();
  }

  pageTabItems(): ItemList<TabItem> {
    const items = super.pageTabItems();

    items.add(
      'logs',
      {
        id: 'logs',
        label: app.translator.trans('ianm-log-viewer.admin.tabs.logs'),
        icon: 'far fa-file-alt',
      },
      100
    );

    return items;
  }

  pageTabContent(tab: string, vnode: Mithril.VnodeDOM<ExtensionPageAttrs, this>): Mithril.Children {
    if (tab === 'logs') return this.logBrowser();

    return super.pageTabContent(tab, vnode);
  }

  logBrowser(): Mithril.Children {
    const selectedFile = this.logState.getFile();
    const selectedFileName = selectedFile?.data?.attributes?.fileName as string | undefined;

    return (
      <div className="LogViewerPage">
        <div className="LogViewerPage--fileList">
          <h3>{app.translator.trans('ianm-log-viewer.admin.viewer.available_logs_heading')}</h3>
          <LogFileList state={this.logState} />
        </div>
        <div className="LogViewerPage--container">
          <div className="LogViewerPage--contentHeader">
            <h3>{selectedFileName ? selectedFileName : app.translator.trans('ianm-log-viewer.admin.viewer.file_contents_heading')}</h3>
          </div>
          <LogFileViewer state={this.logState} />
        </div>
      </div>
    );
  }
}
