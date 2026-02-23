import Extend from 'flarum/common/extenders';
import LogViewerPage from './components/LogViewerPage';
import LogFile from './models/LogFile';
import app from 'flarum/admin/app';

export default [
  new Extend.Store() //
    .add('logs', LogFile),

  new Extend.Admin() //
    .permission(
      () => ({
        icon: 'far fa-file-alt',
        label: app.translator.trans('ianm-log-viewer.admin.permissions.access_logfile_api'),
        permission: 'manageLogfiles',
      }),
      'view'
    )
    .page(LogViewerPage),
];
