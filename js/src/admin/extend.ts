import Extend from 'flarum/common/extenders';
import LogViewerPage from './components/LogViewerPage';
import LogFile from './models/LogFile';
import app from 'flarum/admin/app';

export default [
  new Extend.Store() //
    .add('logs', LogFile),

  new Extend.Admin() //
    .page(LogViewerPage)
    .tab(() => ({
      id: 'settings',
      label: app.translator.trans('ianm-log-viewer.admin.tabs.settings'),
      icon: 'fas fa-sliders-h',
    }))
    .setting(() => ({
      setting: 'ianm-log-viewer.purge-days',
      type: 'number',
      min: 0,
      max: 365,
      required: true,
      label: app.translator.trans('ianm-log-viewer.admin.settings.purge-days'),
      help: app.translator.trans('ianm-log-viewer.admin.settings.purge-days-help'),
    }))
    .setting(() => ({
      setting: 'ianm-log-viewer.max-file-size',
      type: 'number',
      min: 0,
      max: 100,
      required: true,
      label: app.translator.trans('ianm-log-viewer.admin.settings.max-file-size'),
      help: app.translator.trans('ianm-log-viewer.admin.settings.max-file-size-help'),
    }))
    .permission(
      () => ({
        icon: 'far fa-file-alt',
        label: app.translator.trans('ianm-log-viewer.admin.permissions.access_logfile_api'),
        permission: 'manageLogfiles',
      }),
      'moderate'
    ),
];
