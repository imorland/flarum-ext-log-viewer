import Model from 'flarum/common/Model';

export default class LogFile extends Model {
  id() {
    return Model.attribute<string>('id').call(this);
  }
  fileName() {
    return Model.attribute<string>('fileName').call(this);
  }

  fullPath() {
    return Model.attribute<string>('fullPath').call(this);
  }

  size() {
    return Model.attribute<number>('size').call(this);
  }

  formattedSize() {
    return Model.attribute<string>('formattedSize').call(this);
  }

  modified() {
    return Model.attribute('modified', Model.transformDate).call(this);
  }

  content() {
    return Model.attribute<string | null>('content').call(this);
  }
}
