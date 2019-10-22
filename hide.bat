del temp\* /y
for %%i in (*.txt *.bat *.sql test* cred* fut* .git* ) do copy "%%i" temp
for %%i in (*.txt *.bat *.sql test* cred* fut* .git* ) do del "%%i"