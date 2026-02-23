import app from 'flarum/admin/app';
import Component, { ComponentAttrs } from 'flarum/common/Component';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import Button from 'flarum/common/components/Button';
import type Mithril from 'mithril';
import LogFileState from '../state/LogFileState';

interface LogFileViewerAttrs extends ComponentAttrs {
  state: LogFileState;
}

export default class LogFileViewer extends Component<LogFileViewerAttrs> {
  logState!: LogFileState;
  preEl: HTMLPreElement | null = null;
  // Track which file is currently rendered so we can detect a switch
  renderedPath: string | null = null;

  oninit(vnode: Mithril.Vnode<LogFileViewerAttrs, this>) {
    super.oninit(vnode);
    this.logState = this.attrs.state;
  }

  view() {
    if (this.logState.isLoading()) {
      return (
        <div className="LogViewerPage--No-File">
          <LoadingIndicator />
        </div>
      );
    }

    const file = this.logState.getFile();

    if (!file) {
      return (
        <div className="LogViewerPage--No-File">
          <p>{app.translator.trans('ianm-log-viewer.admin.viewer.no_file_selected')}</p>
        </div>
      );
    }

    const content: string = file?.data?.attributes?.content ?? '';
    const currentPath: string = file?.data?.attributes?.relativePath ?? '';

    return (
      <div className="LogViewerPage--fileContent">
        <div className="LogViewerPage--fileActions">
          <Button
            className="Button Button--icon Button--flat"
            icon="fas fa-arrow-down"
            title={app.translator.trans('ianm-log-viewer.admin.viewer.scroll_to_bottom')}
            onclick={() => this.scrollToBottom()}
          />
          <Button
            className="Button Button--icon Button--flat"
            icon="fas fa-arrow-up"
            title={app.translator.trans('ianm-log-viewer.admin.viewer.scroll_to_top')}
            onclick={() => this.scrollToTop()}
          />
        </div>
        <pre
          oncreate={(vnode: Mithril.VnodeDOM<{}, {}>) => {
            this.preEl = vnode.dom as HTMLPreElement;
            this.renderedPath = currentPath;
            this.scrollToBottom();
          }}
          onupdate={() => {
            if (this.renderedPath !== currentPath) {
              this.renderedPath = currentPath;
              this.scrollToBottom();
            }
          }}
        >
          {content}
        </pre>
      </div>
    );
  }

  scrollToBottom() {
    if (this.preEl) {
      this.preEl.scrollTop = this.preEl.scrollHeight;
    }
  }

  scrollToTop() {
    if (this.preEl) {
      this.preEl.scrollTop = 0;
    }
  }
}
