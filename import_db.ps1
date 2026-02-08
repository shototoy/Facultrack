$ErrorActionPreference = "Stop"

# Define path to mysql.exe explicitly
$mysqlExe = "C:\xampp\mysql\bin\mysql.exe"
if (-not (Test-Path $mysqlExe)) {
    Write-Error "mysql.exe not found at $mysqlExe"
    exit 1
}

# Ensure libs are found
$env:Path += ";C:\xampp\mysql\lib;C:\xampp\apache\bin"

# Ask user for the Public Connection URL
Write-Host "Create a connection to Railway MySQL." -ForegroundColor Cyan
$InputUrl = Read-Host "Paste your MySQL URL (e.g. mysql://root:pass@host:port/db)"

if ([string]::IsNullOrWhiteSpace($InputUrl)) {
    Write-Error "URL required."
    exit 1
}

# Parse URL
try {
    $Uri = [System.Uri]$InputUrl
    $User = $Uri.UserInfo.Split(':')[0]
    $Pass = $Uri.UserInfo.Split(':')[1]
    $HostName = $Uri.Host
    $Port = $Uri.Port
    $DbName = $Uri.AbsolutePath.TrimStart('/')
} catch {
    Write-Error "Invalid URL. Make sure it starts with mysql://"
    exit 1
}

Write-Host "Importing facultrack.sql to $HostName..." -ForegroundColor Cyan

# Prepare command
# Check if file exists
if (-not (Test-Path "facultrack.sql")) {
    Write-Error "facultrack.sql not found!"
    exit 1
}

# Construct arguments correctly for cmd /c
# We need to quote the password but ensure the executable path is handled
$cmdBlock = "`"$mysqlExe`" -h $HostName -P $Port -u $User -p`"$Pass`" $DbName < facultrack.sql"

# Execute
cmd /c $cmdBlock

if ($LASTEXITCODE -eq 0) {
    Write-Host "`nImport Successful!" -ForegroundColor Green
} else {
    Write-Host "`nImport Failed." -ForegroundColor Red
}
