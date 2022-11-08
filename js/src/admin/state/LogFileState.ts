import app from 'flarum/admin/app';

export default class LogFileState {
  constructor() {
    this.file = null;
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
}
