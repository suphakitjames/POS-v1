@echo off
REM Add staff-only check to pos.php
powershell -Command "$file = 'public\pos.php'; $lines = Get-Content $file; $newLines = @(); $inserted = $false; foreach ($line in $lines) { $newLines += $line; if ($line -match 'AuthMiddleware::check' -and -not $inserted) { $newLines += ''; $newLines += '// POS ^<0xe0^>^<0xb8^>^<0xaa^>... Staff ^<0xe0^>^<0xb8^>... - Admin ...'; $newLines += 'if ($_SESSION[''role''] !== ''staff'') {'; $newLines += '    $_SESSION[''error''] = ''...POS... ....'';'; $newLines += '    redirect(''index.php'');'; $newLines += '    exit;'; $newLines += '}'; $inserted = $true; } } Set-Content $file -Value $newLines"
pause
