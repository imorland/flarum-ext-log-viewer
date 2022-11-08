import Model from 'flarum/common/Model';
export default class LogFile extends Model {
    fileName(): string;
    fullPath(): string;
    size(): number;
    modified(): Date | null | undefined;
    content(): string | null;
}
