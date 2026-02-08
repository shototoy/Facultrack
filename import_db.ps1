param(
    [Parameter(Mandatory=$true)][string]$HostName,
    [Parameter(Mandatory=$true)][string]$UserName,
    [Parameter(Mandatory=$true)][string]$Password,
    [Parameter(Mandatory=$true)][string]$DatabaseName,
    [Parameter(Mandatory=$false)][int]$Port = 3306
)

$mysqlPath = "C:\xampp\mysql\bin\mysql.exe"

if (-not (Test-Path $mysqlPath)) {
    Write-Error "MySQL executable not found at $mysqlPath. Please update the script with the correct path."
    exit 1
}

Write-Host "Importing facultrack.sql to $HostName..."
& $mysqlPath -h $HostName -P $Port -u $UserName "-p$Password" $DatabaseName < facultrack.sql

if ($LASTEXITCODE -eq 0) {
    Write-Host "Database import successful!" -ForegroundColor Green
} else {
    Write-Error "Database import failed."
}
