import Component, { ComponentAttrs } from 'flarum/common/Component';
import type Mithril from 'mithril';
import LogFileState from '../state/LogFileState';
interface LogFileViewerAttrs extends ComponentAttrs {
    state: LogFileState;
}
export default class LogFileViewer extends Component<LogFileViewerAttrs> {
    logState: LogFileState;
    preEl: HTMLPreElement | null;
    renderedPath: string | null;
    oninit(vnode: Mithril.Vnode<LogFileViewerAttrs, this>): void;
    view(): JSX.Element;
    scrollToBottom(): void;
    scrollToTop(): void;
}
export {};
