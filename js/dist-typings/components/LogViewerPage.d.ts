import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import { type ExtensionPageAttrs } from 'flarum/admin/components/ExtensionPage';
import type Mithril from 'mithril';
import LogFileState from '../state/LogFileState';
export default class LogViewerPage extends ExtensionPage {
    logState: LogFileState;
    oninit(vnode: Mithril.Vnode<ExtensionPageAttrs, this>): void;
    content(): JSX.Element;
}
