export default class LogFileState {
    file: any;
    onRefresh: (() => void) | null;
    constructor();
    setRefreshCallback(callback: () => void): void;
    loadLogFile(filename: string): void;
    getFile(): any;
    downloadFile(filename: string): void;
    deleteFile(filename: string): Promise<void>;
}
