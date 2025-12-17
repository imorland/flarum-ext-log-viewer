import app from 'flarum/admin/app';

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

  loadLogFile(filename: string) {
    app
      .request({
        method: 'GET',
        url: app.forum.attribute('apiUrl') + '/logs/' + filename,
      })
      .then((result) => {
        this.file = result;
        m.redraw();
      });
  }

  getFile() {
    return this.file;
  }

  downloadFile(filename: string) {
    const url = app.forum.attribute('apiUrl') + '/logs/download/' + filename;
    window.open(url, '_blank');
  }

  deleteFile(filename: string) {
    if (!confirm(app.translator.trans('ianm-log-viewer.admin.viewer.confirm_delete'))) {
      return Promise.resolve();
    }

    return app
      .request({
        method: 'DELETE',
        url: app.forum.attribute('apiUrl') + '/logs/' + filename,
      })
      .then(() => {
        // Clear the currently selected file if it was deleted
        if (this.file && this.file.data.attributes.fileName === filename) {
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
