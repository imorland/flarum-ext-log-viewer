import Component, { ComponentAttrs } from 'flarum/common/Component';
import type Mithril from 'mithril';
import LogFileState from '../state/LogFileState';
interface LogFileListAttrs extends ComponentAttrs {
    state: LogFileState;
}
export default class LogFileList extends Component<LogFileListAttrs> {
    oninit(vnode: Mithril.Vnode<LogFileListAttrs, this>): void;
    view(): JSX.Element;
}
export {};
