@echo off

REM -de- Gib hier den Pfad zu XGETTEXT an.
REM -en- Specify here the path to XGETTEXT.

D:\Programme\poEdit\bin\xgettext.exe --language=PHP --from-code=utf8 --output-dir=. --output=dawanda.pot --keyword=_e --keyword=__ --no-wrap ../dmke-dawanda.php

echo Fertig. Done.
pause
