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
for %%i in (form index search cons conssubword consweights corpus parse results phrases utility asksaveresults dosaveresults askaccesssharedlist askdeletelist askdeleteword asklistproperties askrenamelist asksharelist catsuggest doaccesssharedlist dodeletelist dodeleteword dolistproperties dorenamelist dosharelist catcss styles usersuggest wordcss wordsuggest ) do %command% %%i%from%php %%i%to%php
for %%i in (askloadquery askrenamequery asksavequery asksharequery dorenamequery dosavequery dosharequery thirdparty ) do %command% %%i%from%php %%i%to%php
for %%i in (utility )  do %command% %%i%from%js %%i%to%js
for %%i in (help helpmanage ) do %command% %%i%from%html %%i%to%html
%command% styles%from%css styles%to%css
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
set datestamp=%DATE:~10,4%-%DATE:~4,2%-%DATE:~7,2%
del *%1*
copy future.html archive\future_%datestamp%
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
set datestamp=
