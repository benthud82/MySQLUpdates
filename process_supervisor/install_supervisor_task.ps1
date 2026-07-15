#Requires -RunAsAdministrator
[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'
$taskBoot = 'PyProcessSupervisor_Boot'
$taskWatch = 'PyProcessSupervisor_Watchdog'
$baseDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$scriptPath = Join-Path $baseDir 'process_supervisor.py'

$pythonCommand = Get-Command python -ErrorAction Stop
$pythonExe = $pythonCommand.Source
if ([string]::IsNullOrWhiteSpace($pythonExe) -or $pythonExe -match 'WindowsApps') {
    throw 'A real Python installation was not found on PATH. Windows Store aliases are not supported.'
}

Write-Host "Using Python: $pythonExe"
Write-Host "Supervisor:   $scriptPath"
& $pythonExe --version
if ($LASTEXITCODE -ne 0) { throw 'The selected Python executable did not start.' }

& $pythonExe -c "import mysql.connector, schedule, pytz; print('Required Python imports: OK')"
if ($LASTEXITCODE -ne 0) {
    throw "Required modules are missing. Install them with: `"$pythonExe`" -m pip install mysql-connector-python schedule pytz"
}

Write-Host 'Running read-only production preflight...'
& $pythonExe $scriptPath --preflight --expect-prod
if ($LASTEXITCODE -ne 0) { throw 'Preflight failed. No Scheduled Tasks were changed.' }

$action = New-ScheduledTaskAction -Execute $pythonExe -Argument ('"{0}"' -f $scriptPath) -WorkingDirectory $baseDir
$principal = New-ScheduledTaskPrincipal -UserId 'SYSTEM' -LogonType ServiceAccount -RunLevel Highest
$settings = New-ScheduledTaskSettingsSet `
    -ExecutionTimeLimit ([TimeSpan]::Zero) `
    -MultipleInstances IgnoreNew `
    -StartWhenAvailable `
    -RestartCount 3 `
    -RestartInterval (New-TimeSpan -Minutes 1) `
    -AllowStartIfOnBatteries `
    -DontStopIfGoingOnBatteries

$bootTrigger = New-ScheduledTaskTrigger -AtStartup
$watchTrigger = New-ScheduledTaskTrigger -Once -At (Get-Date).AddMinutes(1) `
    -RepetitionInterval (New-TimeSpan -Minutes 15)

Register-ScheduledTask -TaskName $taskBoot -Action $action -Trigger $bootTrigger `
    -Principal $principal -Settings $settings -Force | Out-Null
Register-ScheduledTask -TaskName $taskWatch -Action $action -Trigger $watchTrigger `
    -Principal $principal -Settings $settings -Force | Out-Null

Start-ScheduledTask -TaskName $taskBoot
Start-Sleep -Seconds 3

Write-Host ''
Write-Host 'Scheduled Tasks installed:'
Get-ScheduledTask -TaskName $taskBoot, $taskWatch |
    Select-Object TaskName, State, @{Name='Execute';Expression={$_.Actions.Execute}}, @{Name='Arguments';Expression={$_.Actions.Arguments}} |
    Format-Table -AutoSize
Write-Host 'Open /nahsi/admin_processes.php and verify a fresh supervisor process heartbeat.'
