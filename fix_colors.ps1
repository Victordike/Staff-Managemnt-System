$content = [System.IO.File]::ReadAllText('admin_dashboard.php')
$content = $content -Replace 'from-orange-500 to-red-600', 'from-blue-300 to-blue-700'
$content = $content -Replace 'from-red-500 to-rose-600', 'from-blue-400 to-blue-800'
$content = $content -Replace 'text-orange-100', 'text-blue-100'
$content = $content -Replace 'text-orange-200', 'text-blue-200'
$content = $content -Replace 'text-red-100', 'text-blue-100'
$content = $content -Replace 'text-red-200', 'text-blue-200'
$content = $content -Replace 'bg-orange-400', 'bg-blue-300'
$content = $content -Replace 'bg-red-400', 'bg-blue-400'
[System.IO.File]::WriteAllText('admin_dashboard.php', $content)
Write-Host 'Colors updated to blue!'
