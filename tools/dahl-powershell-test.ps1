Write-Host "Testing Dahl public models endpoint..." -ForegroundColor Cyan
curl.exe https://inference.dahl.global/v1/models

Write-Host "`nTesting Dahl token creation endpoint..." -ForegroundColor Cyan
curl.exe -X POST https://inference.dahl.global/tokens

Write-Host "`nIf you see 'Enable JavaScript and cookies to continue', your IP/environment is blocked by Cloudflare challenge for server-side API use." -ForegroundColor Yellow
