echo off
if x%1==x goto help
if %1==edit goto edit
set type=dev
if not x%2==x set type=%2
del found.txt
for %%i in (*%type%*) do find "%1" %%i >> found.txt
found.txt
set type=
goto done

:edit
notepad finder.bat
goto done

:help
echo finder string (no quotes) [type]

:done
