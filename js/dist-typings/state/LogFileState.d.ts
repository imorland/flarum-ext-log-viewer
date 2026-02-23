export default class LogFileState {
    file: any;
    onRefresh: (() => void) | null;
    constructor();
    setRefreshCallback(callback: () => void): void;
    loadLogFile(relativePath: string): void;
    getFile(): any;
    downloadFile(relativePath: string): void;
    deleteFile(relativePath: string): Promise<void>;
}
