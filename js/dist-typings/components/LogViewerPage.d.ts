import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import { type ExtensionPageAttrs } from 'flarum/admin/components/ExtensionPage';
import type { TabItem } from 'flarum/common/components/Tabs';
import type ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';
import LogFileState from '../state/LogFileState';
export default class LogViewerPage extends ExtensionPage {
    logState: LogFileState;
    oninit(vnode: Mithril.Vnode<ExtensionPageAttrs, this>): void;
    pageTabItems(): ItemList<TabItem>;
    pageTabContent(tab: string, vnode: Mithril.VnodeDOM<ExtensionPageAttrs, this>): Mithril.Children;
    logBrowser(): Mithril.Children;
}
