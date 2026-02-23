import Component, { ComponentAttrs } from 'flarum/common/Component';
import type Mithril from 'mithril';
import LogFileState from '../state/LogFileState';
import LogFile from '../models/LogFile';
interface LogFileListItemAttrs extends ComponentAttrs {
    file: LogFile;
    state: LogFileState;
}
export default class LogFileListItem extends Component<LogFileListItemAttrs> {
    file: LogFile;
    logState: LogFileState;
    loading: boolean;
    oninit(vnode: Mithril.Vnode<LogFileListItemAttrs, this>): void;
    view(): JSX.Element;
    setFile(relativePath: string): void;
    downloadFile(relativePath: string): void;
    deleteFile(relativePath: string): void;
}
export {};
