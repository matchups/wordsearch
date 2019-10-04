echo off
if x%1==x goto help
if %1==edit goto edit
if not %1==loud goto go
shift
echo on
:go
set type=dev
set target=%1
if not x%2==xmulti goto type
set target=%1 %3
goto search
:type
if not x%2==x set type=%2
:search
del temp\found.txt
for %%i in (*%type%*) do find "%target%" %%i >> temp\found.txt
temp\found.txt
set type=
set target=
goto done

:edit
notepad finder.bat
goto done

:help
echo finder string (no quotes) [type]
echo finder string multi more-of-string

:done
