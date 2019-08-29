@Echo off
if x%1==xindex goto index
if x%1==xedit goto edit
if x%2==x goto help
set command=git mv
if %1==dev set command=copy

if not %1==base if not exist *%1* goto missing
if %1==base if not exist form.php goto missing

if %2==archive goto archive
if not %2==base if exist *%2* goto already
if %2==base if exist form.php goto alreadybase

if not %1==base set from=%1.
if %1==base set from=.

if not %2==base set to=%2.
if %2==base set to=.

Echo on
for %%i in (form index search cons conssubword consweights corpus parse results phrases utility asksaveresults dosaveresults ) do %command% %%i%from%php %%i%to%php
for %%i in (utility wizard  )  do %command% %%i%from%js %%i%to%js
%command% help%from%html help%to%html
@Echo off
goto done

:help
Echo Usage: vup [from type] [to type] (use "base" to specify a blank type for baseline or "archive" as a target for back)
Echo Example: vup dev back
goto done

:already
dir *%2*
goto done

:alreadybase
Echo "Base files already exist"
goto done

:missing
Echo No %1 files
goto done

:archive
echo on
set archdir=%DATE:~10,4%-%DATE:~4,2%-%DATE:~7,2%
mkdir archive\%archdir%
copy *%1* archive\%archdir%
del *%1*
copy future.html archive\%archdir%
echo off
goto done

:index
for %%i in (index*.php ) do notepad %%i
goto done

:edit
notepad vup.bat
goto done

:done
set from=
set to=
set archdir=
if not exist wizard* echo Time to remove wizard