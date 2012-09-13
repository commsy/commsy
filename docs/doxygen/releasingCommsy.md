Einen CommSy Release Eerstellen					{#releasingCommsy}
================

\tableofcontents

\section intro Einleitung
Diese Seite beschreibt die nötigen Schritte um Commsy zu deployen, bzw. eine bestehende Installation zu updaten.

\section indexing Indizierung

\section crons Crons

\section js JavaScript

\subsection overview Ein Überblick
Seit Version 7 ist es in CommSy möglich eine minimierte Variante des Javascript Sources auszuliefern. Dies geschah durch setzten des
`$c_minimized_js` Flags. Mit Version 8.0 wurden die Möglichkeiten erheblich erweitert.

Das verwendete JavaScript - abgesehen von einigen 3rd-Party Komponenten - liegt in unkomprierter und kommentierter Form unterhalb von
`htdocs/js/src/` vor. Dies umfasst die komplette Dojo API, das cbtree-Modul und den CommSy Code. Der Code lässt sich auf drei Möglichkeiten
einbetten:

1. Als reiner Source - (source)
2. Als komprimierter und optimierter Source - (build)
3. Als komprimiert und optimierter Source, der in wenige Dateien zusammengefasst wird - (layer)

Entsprechend der Sortierung der Liste sinkt der Bedarf an Dateien und Dateigröße, der übertragen werden muss und beschleunigt so die Ausführung
beachtlich.

\subsection config Konfiguration

Mit welcher Variante der Code eingebunden werden soll, lässt sich über das Flag `$c_js_mode`, dessen Defaultwert auf "source" gesetzt ist, angeben.
Die möglichen Werte ergeben sich aus der obrigen List.

\subsection building Buildprozess

Um den vorhanden Source zu optimieren und/oder diesen in Layern zu organisieren muss ein Buildprozess angestoßen werden. Dafür muss das Skript
`htdocs/js/build.sh`, bzw. unter Windows `htdocs/js/build.bat` aufgerufen werden. Dies kapselt den Buildprozess und kopiert den Source um Probleme mit vorhanden CVS-Verzeichnissen zu
umgehen, stellt aber den ursprünglichen Zustand wieder her. Sofern bei der entsprechenden Installation die build- oder layer-Methode benutzt wird
ist es zwingend erforderlich, dass der Source korrekt gebaut wird, da ansonstent alle JavaScript-Funktionen davon betroffen sind.