export default class LogFileState {
    file: any;
    loading: boolean;
    onRefresh: (() => void) | null;
    constructor();
    setRefreshCallback(callback: () => void): void;
    loadLogFile(relativePath: string): void;
    getFile(): any;
    isLoading(): boolean;
    downloadFile(relativePath: string): void;
    deleteFile(relativePath: string): Promise<void>;
}
