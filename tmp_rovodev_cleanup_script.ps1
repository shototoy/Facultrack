# PowerShell script to clean up codebase
$files = @(
    "program.php",
    "director.php", 
    "faculty.php",
    "home.php",
    "index.php",
    "logout.php",
    "assets/js/program.js",
    "assets/js/director.js",
    "assets/js/faculty.js", 
    "assets/js/home.js",
    "assets/js/shared_modals.js",
    "assets/php/common_utilities.php",
    "assets/php/shared_modals.php",
    "assets/php/get_location.php",
    "assets/php/get_statistics.php", 
    "assets/php/handle_admin_actions.php",
    "assets/css/style.css",
    "assets/css/scheduling.css"
)

foreach ($file in $files) {
    if (Test-Path $file) {
        Write-Host "Cleaning $file..."
        
        # Read content
        $content = Get-Content $file -Raw
        
        # Remove console.log statements
        $content = $content -replace 'console\.log\([^)]*\);?\s*', ''
        $content = $content -replace 'console\.error\([^)]*\);?\s*', ''
        $content = $content -replace 'console\.warn\([^)]*\);?\s*', ''
        $content = $content -replace 'console\.debug\([^)]*\);?\s*', ''
        
        # Remove single line comments (// comments)
        $content = $content -replace '^\s*//.*$', ''
        $content = $content -replace '\s+//.*$', ''
        
        # Remove multi-line comments (/* */ comments) `
        $content = $content -replace '/\*[\s\S]*?\*/', ''
        
        # Remove excessive blank lines (more than 2 consecutive)
        $content = $content -replace '\n\s*\n\s*\n\s*\n+', "`n`n"
        $content = $content -replace '\r\n\s*\r\n\s*\r\n\s*\r\n+', "`r`n`r`n"
        
        # Remove trailing whitespace
        $content = $content -replace '\s+$', ''
        $content = $content -replace '^\s*\n', "`n"
        
        # Write back
        $content | Set-Content $file -NoNewline
        
        Write-Host "Cleaned $file"
    }
}

Write-Host "Cleanup completed!"