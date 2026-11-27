import type LogFile from '../models/LogFile';
export default class LogFileState {
    file: any;
    loading: boolean;
    files: LogFile[];
    listLoading: boolean;
    listLoaded: boolean;
    /** Numbers each list request, so an answer overtaken by a later refresh is dropped. */
    protected listRequest: number;
    /** Where the open file was scrolled to, so leaving the Logs tab and coming back keeps the reader's place. */
    scrollTop: number | null;
    constructor();
    loadList(): void;
    /**
     * Never rejects: a failed request has already shown Flarum's error alert, and
     * leaves `listLoaded` false so the list can be loaded again.
     */
    refreshList(): Promise<void>;
    loadLogFile(relativePath: string): void;
    getFile(): any;
    isLoading(): boolean;
    downloadFile(relativePath: string): void;
    deleteFile(relativePath: string): Promise<void>;
}
