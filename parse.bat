@echo off
cd C:\wamp\bin\php\php5.4.3
:Start
cls
for /f %%a IN ('dir /b /s "C:\wamp\www\EtherRS\*.php" ') do php -l %%a
pause
goto Start