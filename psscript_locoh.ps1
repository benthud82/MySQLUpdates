Set-Location -Path D:\xampp\htdocs\MySQLUpdates

[int]$hour = get-date -format HH
while (($hour -lt 3) -or !($hour -gt 20)) {
    [int]$hour = get-date -format HH
	Start-Sleep -s 10
	cmd.exe /c 'refresh_loc_oh.bat' 
}
