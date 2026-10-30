const { app, BrowserWindow, ipcMain, Menu } = require('electron');
const path = require('path');
const http = require('http');
const { spawn } = require('child_process');
const fs = require('fs');

let mainWindow = null;
let phpServerProcess = null;

const PRIMARY_URL = 'http://localhost/parking/index.php';
const FALLBACK_URL = 'http://127.0.0.1:8080/index.php';
let activeServerUrl = null;

function probeUrl(url, timeoutMs = 1200) {
    return new Promise((resolve) => {
        try {
            const req = http.get(url, { timeout: timeoutMs }, (res) => {
                if (res.statusCode >= 200 && res.statusCode < 400) {
                    resolve(true);
                } else {
                    resolve(false);
                }
            });
            req.on('timeout', () => {
                req.destroy();
                resolve(false);
            });
            req.on('error', () => {
                resolve(false);
            });
        } catch (e) {
            resolve(false);
        }
    });
}

function tryStartBuiltinPhpServer() {
    if (phpServerProcess) return;

    const possiblePhpPaths = [
        'C:\\xampp\\php\\php.exe',
        'C:\\php\\php.exe',
        'php'
    ];

    let phpBin = null;
    for (const p of possiblePhpPaths) {
        if (p === 'php' || fs.existsSync(p)) {
            phpBin = p;
            break;
        }
    }

    if (!phpBin) return;

    const projectRoot = path.resolve(__dirname, '..');
    try {
        phpServerProcess = spawn(phpBin, ['-S', '127.0.0.1:8080', '-t', projectRoot], {
            cwd: projectRoot,
            windowsHide: true,
            detached: false
        });

        phpServerProcess.on('error', (err) => {
            console.log('Builtin PHP server notice:', err.message);
            phpServerProcess = null;
        });

        phpServerProcess.on('exit', () => {
            phpServerProcess = null;
        });
    } catch (e) {
        phpServerProcess = null;
    }
}

async function connectToServer() {
    // 1. First check XAMPP Apache primary URL
    const primaryOk = await probeUrl(PRIMARY_URL);
    if (primaryOk) {
        activeServerUrl = PRIMARY_URL;
        if (mainWindow) {
            mainWindow.loadURL(PRIMARY_URL);
        }
        return true;
    }

    // 2. Try starting built-in PHP fallback server
    tryStartBuiltinPhpServer();
    await new Promise((r) => setTimeout(r, 600));

    // 3. Probe fallback URL
    const fallbackOk = await probeUrl(FALLBACK_URL);
    if (fallbackOk) {
        activeServerUrl = FALLBACK_URL;
        if (mainWindow) {
            mainWindow.loadURL(FALLBACK_URL);
        }
        return true;
    }

    // 4. If still unreachable, show offline instructions
    if (mainWindow) {
        mainWindow.loadFile(path.join(__dirname, 'offline.html'));
    }
    return false;
}

function createWindow() {
    mainWindow = new BrowserWindow({
        width: 1320,
        height: 860,
        minWidth: 1024,
        minHeight: 680,
        title: 'CampusPark – College Parking System',
        icon: path.join(__dirname, 'icon.ico'),
        backgroundColor: '#0d0e13',
        show: false,
        autoHideMenuBar: true,
        webPreferences: {
            preload: path.join(__dirname, 'preload.js'),
            nodeIntegration: false,
            contextIsolation: true,
            sandbox: false
        }
    });

    // Remove default menu bar for clean native app appearance
    Menu.setApplicationMenu(null);

    // Show window once ready to avoid white flash
    mainWindow.once('ready-to-show', () => {
        mainWindow.show();
    });

    // Global keyboard shortcuts within window
    mainWindow.webContents.on('before-input-event', (event, input) => {
        if (input.type !== 'keyDown') return;

        // F5 or Ctrl+R: Refresh
        if (input.key === 'F5' || (input.control && input.key.toLowerCase() === 'r')) {
            mainWindow.reload();
            event.preventDefault();
        }
        // F11: Fullscreen toggle
        if (input.key === 'F11') {
            mainWindow.setFullScreen(!mainWindow.isFullScreen());
            event.preventDefault();
        }
        // Ctrl+Shift+I: DevTools
        if (input.control && input.shift && input.key.toLowerCase() === 'i') {
            mainWindow.webContents.toggleDevTools();
            event.preventDefault();
        }
    });

    mainWindow.on('closed', () => {
        mainWindow = null;
    });

    // Initial server connection
    connectToServer();
}

ipcMain.on('retry-connection', async () => {
    await connectToServer();
});

app.whenReady().then(() => {
    createWindow();

    app.on('activate', () => {
        if (BrowserWindow.getAllWindows().length === 0) {
            createWindow();
        }
    });
});

app.on('window-all-closed', () => {
    if (phpServerProcess) {
        try {
            phpServerProcess.kill();
        } catch (e) {}
        phpServerProcess = null;
    }
    if (process.platform !== 'darwin') {
        app.quit();
    }
});

app.on('will-quit', () => {
    if (phpServerProcess) {
        try {
            phpServerProcess.kill();
        } catch (e) {}
        phpServerProcess = null;
    }
});

