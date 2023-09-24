import app from 'flarum/admin/app';
import LogViewerPage from './components/LogViewerPage';
import LogFile from './models/LogFile';

app.initializers.add('ianm-log-viewer', () => {
  app.store.models.log = LogFile;

  app.extensionData
    .for('ianm-log-viewer')
    .registerPermission(
      {
        icon: 'far fa-file-alt',
        label: app.translator.trans('ianm-log-viewer.admin.permissions.access_logfile_api'),
        permission: 'readLogfiles',
      },
      'view'
    )
    .registerPermission(
      {
        icon: 'far fa-trash-alt',
        label: app.translator.trans('ianm-log-viewer.admin.permissions.delete_logfile_api'),
        permission: 'deleteLogfiles',
      },
      'moderate'
    )
    .registerPage(LogViewerPage);
});
