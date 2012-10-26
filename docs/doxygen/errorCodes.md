Fehlercodes					{#errorCodes}
================

\tableofcontents

\section intro Einleitung
Einleitung...

\section errorCodesPHP PHP Fehlercodes

\section errorCodesAJAX AJAX Fehlercodes

- Allgemein 0xx
  + Rights 00x
    * 000 - Unzureichende Rechte

- mandatory-Errors 11x
  + 111 - Feld leer
  + 112 - Feld leer nach Bereinigung
  + 113 - Zwingende Zuordnung nicht erfüllt(Kategorien)
  + 114 - Zwingende Zuordnung nicht erfüllt(Schlagwörter)

- Format-Errors 2xx
  + Textfeld 20x
    * 201 - E-Mail - ungültige E-Mail Adresse
    * 202 - E-Mail - E-Mail Adresse existiert nicht(MX-Check)
    * 203 - Datum - ungültiges Datumsformat
    * 204 - numerisch - Feld enthält nicht numerische Zeichen
    * 205 - alphabetisch - Feld enthält nicht alphabetische Zeichen
    * 206 - Währung - ungültiges Währungsformat
    * 207 - alphanumerisch - Feld enthält ungültige Sonderzeichen
  + Textarea 21x
    * 211 - ungültige Zeichen
  + Select
  + Radio
  + File
  + Checkbox

- Kontextspezifisch 1xxy
  + Profilformular 101y
    * 1011 - Kennungsänderung - Kennung belegt
    * 1012 - Kennungsänderung - Kennung enthält Umlaute
    * 1013 - Kennungsänderung - Fehler in Authentifizierungsquelle

- Spezial-Errors 9xx
  + 901 - Feld stimmt nicht mit anderem überein