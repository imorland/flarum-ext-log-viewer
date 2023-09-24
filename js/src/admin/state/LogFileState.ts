import app from 'flarum/admin/app';
import LogFile from '../models/LogFile';

export default class LogFileState {
  private file: LogFile | null;

  constructor() {
    this.file = null;
  }

  async loadLogFile(filename: string): Promise<void> {
    try {
      const result = await app.request({
        method: 'GET',
        url: `${app.forum.attribute('apiUrl')}/logs/${filename}`,
      });

      this.file = result as LogFile;

      m.redraw();
    } catch (error) {
      console.error('Error loading log file:', error);
      // Handle or throw error as per your requirements
    }
  }

  getFile(): LogFile | null {
    return this.file;
  }

  downloadFile(fileName: string) {
    // Create the URL pointing to the backend endpoint
    const url = `${app.forum.attribute('apiUrl')}/logs/download/${fileName}`;

    // Use an anchor element to initiate the download
    const a = document.createElement('a');
    a.href = url;
    a.download = fileName; // Set the file name for the download
    document.body.appendChild(a);
    a.click();

    // Cleanup
    document.body.removeChild(a);
  }

  async deleteFile(fileName: string): Promise<void> {
    const isConfirmed = confirm('Are you sure you want to delete this file?');

    if (isConfirmed) {
      try {
        await app.request({
          method: 'DELETE',
          url: `${app.forum.attribute('apiUrl')}/logs/${fileName}`,
        });

        this.file = null;

        m.redraw();
      } catch (error) {
        console.error('Error deleting log file:', error);
      }
    }
  }
}
