# Worker Setup

## Required Runtime

The scan engine expects these tools to be available in the host PATH:

- `git`
- `php`
- `composer`
- `java`
- `dotnet`
- `dotnet-sonarscanner`
- `sonar-scanner`

Application runtime configuration:

- `QUEUE_CONNECTION`
- `SCAN_WORKSPACE_PATH`
- `SCAN_TIMEOUT_SECONDS`
- `SCAN_MAX_CONCURRENT_JOBS`

If `SCAN_WORKSPACE_PATH` is left blank, QualiOps falls back to `storage/app/scan-workspaces`.

## Ubuntu Setup

Example package and toolchain setup:

```bash
sudo apt update
sudo apt install -y git php-cli php-mysql php-xml php-mbstring composer unzip curl openjdk-17-jre
sudo apt install -y dotnet-sdk-8.0
dotnet tool install --global dotnet-sonarscanner
```

Install SonarScanner CLI:

```bash
curl -L -o sonar-scanner.zip https://binaries.sonarsource.com/Distribution/sonar-scanner-cli/sonar-scanner-cli-6.2.1.4610-linux-x64.zip
unzip sonar-scanner.zip
sudo mv sonar-scanner-* /opt/sonar-scanner
echo 'export PATH="/opt/sonar-scanner/bin:$HOME/.dotnet/tools:$PATH"' >> ~/.profile
source ~/.profile
```

## Windows Notes

- Install Git for Windows and ensure `git.exe` is in PATH.
- Install PHP and Composer, or use an existing XAMPP PHP path that is already in PATH.
- Install Java JRE or JDK and ensure `java.exe` is in PATH.
- Install the .NET SDK and verify `dotnet --version`.
- Install the SonarScanner .NET global tool:

```powershell
dotnet tool install --global dotnet-sonarscanner
```

- Install SonarScanner CLI and add its `bin` directory to PATH.

Restart the terminal after PATH updates.

## Queue Worker

Start a worker manually:

```bash
php artisan queue:work --queue=scans --tries=1 --timeout=1800
```

On Windows PowerShell:

```powershell
php artisan queue:work --queue=scans --tries=1 --timeout=1800
```

## Supervisor Example

Use Supervisor on Ubuntu for persistent workers:

```ini
[program:qualiops-scan-worker]
command=/usr/bin/php /var/www/qualiops/artisan queue:work --queue=scans --tries=1 --timeout=1800
directory=/var/www/qualiops
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/supervisor/qualiops-scan-worker.log
stopwaitsecs=3600
```

Apply the Supervisor configuration:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start qualiops-scan-worker:*
```

## Common Errors

`dotnet-sonarscanner` not found:
- Verify the global tool path is in PATH.
- On Linux this is usually `$HOME/.dotnet/tools`.

`sonar-scanner` not found:
- Verify the scanner CLI `bin` directory is in PATH.

`java` not found:
- Install a JRE or JDK and confirm `java -version`.

Queue remains pending:
- Confirm `php artisan queue:work --queue=scans` is running.
- Confirm `QUEUE_CONNECTION` matches the configured backend.
- Confirm the `jobs` table exists for database queue mode.

Workspace permission errors:
- Set `SCAN_WORKSPACE_PATH` to a writable directory.
- Confirm the web/worker user can create and delete directories there.

Git clone authentication failures:
- Re-test the saved Git connection in the UI.
- Confirm the token or SSH key is valid and has repository access.

Sonar authentication failures:
- Re-test the saved SonarQube connection in the UI.
- Confirm the token still has analysis permissions.
