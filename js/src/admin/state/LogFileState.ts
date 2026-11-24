import app from 'flarum/admin/app';
import type LogFile from '../models/LogFile';

// Encode a relative path as URL-safe base64 (base64url, RFC 4648 §5) so that
// paths containing '/' are safe to embed as a single URL path segment.
function encodeId(relativePath: string): string {
  return btoa(relativePath).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
}

export default class LogFileState {
  file: any;
  loading: boolean;
  files: LogFile[] = [];
  listLoading: boolean = false;
  listLoaded: boolean = false;
  /** Where the open file was scrolled to, so leaving the Logs tab and coming back keeps the reader's place. */
  scrollTop: number | null = null;

  constructor() {
    this.file = null;
    this.loading = false;
  }

  loadList() {
    if (!this.listLoaded && !this.listLoading) this.refreshList();
  }

  refreshList() {
    this.listLoading = true;
    m.redraw();

    return app
      .request<{ data: any[] }>({
        method: 'GET',
        url: app.forum.attribute('apiUrl') + '/logs',
      })
      .then((result) => {
        const items = Array.isArray(result.data) ? result.data : [result.data];
        this.files = items.map((item) => app.store.pushObject(item) as LogFile);
      })
      .finally(() => {
        this.listLoading = false;
        this.listLoaded = true;
        m.redraw();
      });
  }

  loadLogFile(relativePath: string) {
    this.loading = true;
    m.redraw();

    app
      .request({
        method: 'GET',
        url: app.forum.attribute('apiUrl') + '/logs/' + encodeId(relativePath),
      })
      .then((result) => {
        this.file = result;
        this.scrollTop = null;
        this.loading = false;
        m.redraw();
      })
      .catch(() => {
        this.loading = false;
        m.redraw();
      });
  }

  getFile() {
    return this.file;
  }

  isLoading() {
    return this.loading;
  }

  downloadFile(relativePath: string) {
    const url = app.forum.attribute('apiUrl') + '/logs/download/' + encodeId(relativePath);
    window.open(url, '_blank');
  }

  deleteFile(relativePath: string) {
    if (!confirm(String(app.translator.trans('ianm-log-viewer.admin.viewer.confirm_delete')))) {
      return Promise.resolve();
    }

    return app
      .request({
        method: 'DELETE',
        url: app.forum.attribute('apiUrl') + '/logs/' + encodeId(relativePath),
      })
      .then(() => {
        // Clear the currently selected file if it was deleted
        if (this.file && this.file.data.attributes.relativePath === relativePath) {
          this.file = null;
        }

        return this.refreshList();
      });
  }
}
