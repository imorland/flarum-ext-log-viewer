/// <reference types="mithril" />
import Component from 'flarum/common/Component';
export default class LogFileList extends Component {
    oninit(vnode: any): void;
    view(): JSX.Element;
    refresh(clear?: boolean): Promise<any>;
    loadResults(): Promise<import("flarum/common/Store").ApiResponseSingle<import("flarum/common/Model").default>>;
    parseResults(results: any): any;
}
