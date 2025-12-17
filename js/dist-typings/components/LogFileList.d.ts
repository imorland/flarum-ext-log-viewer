import Component, { ComponentAttrs } from 'flarum/common/Component';
import type Mithril from 'mithril';
import LogFileState from '../state/LogFileState';
import LogFile from '../models/LogFile';
interface LogFileListAttrs extends ComponentAttrs {
    state: LogFileState;
}
export default class LogFileList extends Component<LogFileListAttrs> {
    loading: boolean;
    files: LogFile[];
    logState: LogFileState;
    oninit(vnode: Mithril.Vnode<LogFileListAttrs, this>): void;
    view(): JSX.Element;
    refresh(clear?: boolean): Promise<any>;
    loadResults(): Promise<import("flarum/common/Store").ApiResponseSingle<import("flarum/common/Model").default>>;
    parseResults(results: any): any;
}
export {};
