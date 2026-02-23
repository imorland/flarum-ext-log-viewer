import app from 'flarum/admin/app';

// Encode a relative path as URL-safe base64 (base64url, RFC 4648 §5) so that
// paths containing '/' are safe to embed as a single URL path segment.
function encodeId(relativePath: string): string {
  return btoa(relativePath).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
}

export default class LogFileState {
  file: any;
  onRefresh: (() => void) | null;

  constructor() {
    this.file = null;
    this.onRefresh = null;
  }

  setRefreshCallback(callback: () => void) {
    this.onRefresh = callback;
  }

  loadLogFile(relativePath: string) {
    app
      .request({
        method: 'GET',
        url: app.forum.attribute('apiUrl') + '/logs/' + encodeId(relativePath),
      })
      .then((result) => {
        this.file = result;
        m.redraw();
      });
  }

  getFile() {
    return this.file;
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

        // Refresh the file list
        if (this.onRefresh) {
          this.onRefresh();
        }

        m.redraw();
      });
  }
}
