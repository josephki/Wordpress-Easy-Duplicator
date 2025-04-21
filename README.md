# WP Easy Post Duplicator

![Version](https://img.shields.io/badge/version-3.0-blue)
![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-green)
![License](https://img.shields.io/badge/license-GPL--2.0%2B-red)

Ein leistungsstarkes, benutzerfreundliches WordPress-Plugin zum Duplizieren von Beiträgen, Seiten und benutzerdefinierten Post-Typen mit einem Klick.

## 🚀 Funktionen

- **Einfaches Duplizieren** von Beiträgen, Seiten und benutzerdefinierten Post-Typen
- **Komplettes Kopieren** aller Metadaten, Taxonomien (Kategorien, Tags, etc.) und Featured Images
- **Elegantes Design** mit Button direkt über dem "In den Papierkorb verschieben"-Button
- **Intuitives Feedback** durch Ausgrauen des Buttons nach Klick
- **Multi-Editor-Unterstützung** für sowohl Classic Editor als auch Gutenberg
- **Mehrsprachig** mit deutscher und englischer Übersetzung
- **Intelligente Erkennung** von Konflikten mit anderen Duplikations-Plugins
- **Spezielle Unterstützung** für Crocoblock Custom Post Types

## 📋 Voraussetzungen

- WordPress 5.0 oder höher
- PHP 7.0 oder höher

## 💻 Installation

### Manuelle Installation

1. Laden Sie die ZIP-Datei des Plugins herunter
2. Gehen Sie in Ihrem WordPress-Dashboard zu *Plugins → Neu hinzufügen*
3. Klicken Sie auf *Plugin hochladen*
4. Wählen Sie die heruntergeladene ZIP-Datei aus und klicken Sie auf *Jetzt installieren*
5. Aktivieren Sie das Plugin nach der Installation

### Aus dem Quellcode

1. Klonen Sie dieses Repository oder laden Sie den Quellcode herunter
2. Erstellen Sie den Ordner `wp-easy-post-duplicator` in Ihrem `/wp-content/plugins/`-Verzeichnis
3. Kopieren Sie alle Dateien in diesen Ordner
4. Aktivieren Sie das Plugin im WordPress-Dashboard

## 🔧 Verwendung

Nach der Aktivierung haben Sie mehrere Möglichkeiten, einen Beitrag zu duplizieren:

### In der Beitragsübersicht

Bewegen Sie den Mauszeiger über einen Beitrag, und klicken Sie auf den Link "Duplizieren".

![Duplizieren-Link in der Beitragsübersicht](https://via.placeholder.com/500x100/f5f5f5/222222?text=Duplizieren+Link+in+Beitragsübersicht)

### Im Editor

Im Bearbeitungsbildschirm sehen Sie den "Beitrag duplizieren"-Button direkt über dem "In den Papierkorb verschieben"-Button.

![Duplizieren-Button im Editor](https://via.placeholder.com/250x150/f5f5f5/222222?text=Duplizieren+Button+im+Editor)

### Was passiert beim Duplizieren?

Wenn Sie auf den "Duplizieren"-Button oder -Link klicken:

1. Es wird eine exakte Kopie des Beitrags erstellt
2. Der Titel erhält den Zusatz "(Kopie)"
3. Die Kopie wird als Entwurf erstellt
4. Alle Metadaten, Taxonomien und Featured Images werden übernommen
5. Sie werden automatisch zum Editor der neuen Kopie weitergeleitet

## ⚠️ Besonderheit bei Crocoblock Custom Post Types

Bei Crocoblock Custom Post Types wird eine spezielle Warnung angezeigt, da hier besondere Vorsicht geboten ist:

![Crocoblock-Warnung](https://via.placeholder.com/400x120/f5f5f5/d63638?text=Crocoblock+Warnung)

**Wichtig:** Bei Crocoblock Custom Post Types sollten Sie immer die Kopie speichern, bevor Sie zur Listenansicht zurückkehren, um Fehler zu vermeiden.

## 🌍 Mehrsprachigkeit

Das Plugin ist vollständig übersetzbar und wird mit deutscher und englischer Übersetzung ausgeliefert. Um weitere Sprachen hinzuzufügen:

1. Verwenden Sie die `wp-easy-post-duplicator.pot`-Datei im `languages`-Verzeichnis als Vorlage
2. Erstellen Sie mit einem Übersetzungstool wie Poedit eine neue Übersetzung
3. Speichern Sie die Dateien im `languages`-Verzeichnis

## 🔄 Kompatibilität mit anderen Plugins

Das Plugin prüft automatisch auf Konflikte mit anderen Duplikations-Plugins:

- Bei Aktivierung werden bekannte Duplikations-Plugins erkannt
- Konflikte mit Admin and Site Enhancements (ASENHA) werden intelligent behandelt
- Bei Konflikten wird eine Warnung angezeigt, um doppelte Funktionalität zu vermeiden

## 📜 Lizenz

Dieses Plugin ist unter der GPLv2-Lizenz (oder später) veröffentlicht.

## 🙌 Mitwirkende

- Entwickelt von Joseph Kisler - Webwerkstatt
- Verbessert mit Nutzerfeedback aus echten Anwendungsfällen

## 📝 Changelog

### Version 3.0
- Internationalisierung für mehrsprachige Unterstützung
- Verbesserte Crocoblock CPT-Erkennung und -Warnungen
- Visuelle Verbesserungen und Feedback-Elemente

### Version 2.0
- Unterstützung für Gutenberg-Editor
- Ausgrauen des Buttons nach Klick
- Verbesserte Meta-Daten-Handhabung

### Version 1.0
- Erstveröffentlichung
- Grundlegende Duplizierungsfunktionalität
- Classic Editor Unterstützung
