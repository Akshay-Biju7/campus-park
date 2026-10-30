using System;
using System.Diagnostics;
using System.IO;
using System.Net;
using System.Windows.Forms;

namespace CampusParkLauncher
{
    static class Program
    {
        [STAThread]
        static void Main()
        {
            string baseDir = AppDomain.CurrentDomain.BaseDirectory;
            string primaryUrl = "http://localhost/parking/index.php";
            string fallbackUrl = "http://127.0.0.1:8080/index.php";
            string targetUrl = primaryUrl;

            // 1. Check if Electron executable exists
            string electronExe = Path.Combine(baseDir, @"desktop\node_modules\electron\dist\electron.exe");
            string desktopDir = Path.Combine(baseDir, "desktop");

            if (File.Exists(electronExe) && Directory.Exists(desktopDir))
            {
                ProcessStartInfo psi = new ProcessStartInfo();
                psi.FileName = electronExe;
                psi.Arguments = "\"" + desktopDir + "\"";
                psi.WorkingDirectory = desktopDir;
                psi.UseShellExecute = false;
                Process.Start(psi);
                return;
            }

            // 2. Check if Apache primary URL is accessible
            bool serverOk = CheckUrl(primaryUrl);
            if (!serverOk)
            {
                // Try fallback or launch PHP built-in server if php.exe is found
                string phpExe = @"C:\xampp\php\php.exe";
                if (File.Exists(phpExe))
                {
                    try
                    {
                        ProcessStartInfo phpPsi = new ProcessStartInfo();
                        phpPsi.FileName = phpExe;
                        phpPsi.Arguments = "-S 127.0.0.1:8080 -t \"" + baseDir + "\"";
                        phpPsi.WorkingDirectory = baseDir;
                        phpPsi.WindowStyle = ProcessWindowStyle.Hidden;
                        phpPsi.CreateNoWindow = true;
                        phpPsi.UseShellExecute = false;
                        Process.Start(phpPsi);
                        System.Threading.Thread.Sleep(800);
                        if (CheckUrl(fallbackUrl))
                        {
                            targetUrl = fallbackUrl;
                            serverOk = true;
                        }
                    }
                    catch { }
                }
            }

            // 3. Find modern Edge or Chrome to launch in standalone App Mode
            string edgePath = @"C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe";
            if (!File.Exists(edgePath))
            {
                edgePath = @"C:\Program Files\Microsoft\Edge\Application\msedge.exe";
            }
            string chromePath = @"C:\Program Files\Google\Chrome\Application\chrome.exe";

            string browserExe = File.Exists(edgePath) ? edgePath : (File.Exists(chromePath) ? chromePath : null);

            if (browserExe != null)
            {
                ProcessStartInfo appPsi = new ProcessStartInfo();
                appPsi.FileName = browserExe;
                appPsi.Arguments = "--app=\"" + targetUrl + "\" --window-size=1320,860";
                appPsi.UseShellExecute = false;
                Process.Start(appPsi);
            }
            else
            {
                Process.Start(targetUrl);
            }
        }

        static bool CheckUrl(string url)
        {
            try
            {
                HttpWebRequest req = (HttpWebRequest)WebRequest.Create(url);
                req.Timeout = 1200;
                req.Method = "HEAD";
                using (HttpWebResponse res = (HttpWebResponse)req.GetResponse())
                {
                    return ((int)res.StatusCode >= 200 && (int)res.StatusCode < 400);
                }
            }
            catch
            {
                return false;
            }
        }
    }
}

