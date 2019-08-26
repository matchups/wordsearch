del temp\* /y
for %%i in (*.txt *.bat *.sql test* cred* ) do copy "%%i" temp
for %%i in (*.txt *.bat *.sql test* cred* ) do del "%%i"