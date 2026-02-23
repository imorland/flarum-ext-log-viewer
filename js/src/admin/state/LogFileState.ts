import app from 'flarum/admin/app';

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
    const encodedId = encodeId(relativePath);
    app
      .request({
        method: 'GET',
        url: app.forum.attribute('apiUrl') + '/logs/' + encodedId + '?file=' + encodedId,
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
    const encodedId = encodeId(relativePath);
    const url = app.forum.attribute('apiUrl') + '/logs/download/' + encodedId + '?file=' + encodedId;
    window.open(url, '_blank');
  }

  deleteFile(relativePath: string) {
    if (!confirm(app.translator.trans('ianm-log-viewer.admin.viewer.confirm_delete'))) {
      return Promise.resolve();
    }

    const encodedId = encodeId(relativePath);

    return app
      .request({
        method: 'DELETE',
        url: app.forum.attribute('apiUrl') + '/logs/' + encodedId + '?file=' + encodedId,
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
