# RcSubcategoriesCmsBlock

Shopware 6 Plugin — CMS-Block-Element zur Anzeige von Unterkategorien.

---

## Was das Plugin macht

Shopware bietet im Erlebniswelt-Editor keinen Standard-Block, der die Unterkategorien einer bestimmten Kategorie automatisch auflistet. Kategoriennavigationen müssen dadurch manuell gepflegt werden, was bei wachsenden Sortimenten aufwändig wird.

Dieses Plugin registriert ein neues CMS-Element `rc_subcategories`. Im Block-Editor kann eine Elternkategorie ausgewählt werden. Das Plugin lädt alle aktiven, sichtbaren (nicht-Link-)Unterkategorien dieser Kategorie inklusive ihrer Bilder und stellt sie dem Template als `SubcategoriesStruct` zur Verfügung. Die Sortierung erfolgt nach Position, dann alphabetisch.

---

## Voraussetzungen

- Shopware 6.7 oder 6.8
- PHP 8.2+

---

## Installation

```bash
php bin/console plugin:refresh
php bin/console plugin:install --activate RcSubcategoriesCmsBlock
php bin/console cache:clear
```

---

## Verwendung

1. Im Erlebniswelt-Editor eine Seite öffnen
2. Neuen Block hinzufügen → Element **Subcategories (Ruhrcoder)** wählen
3. Im Element-Konfigurator die gewünschte Elternkategorie auswählen
4. Speichern — die Unterkategorien werden automatisch geladen und dargestellt

Das Template befindet sich unter:

```
src/Resources/views/storefront/element/cms-element-subcategories.html.twig
```

---

## Update

```bash
php bin/console plugin:refresh
php bin/console plugin:update RcSubcategoriesCmsBlock
php bin/console cache:clear
```

---

## Entwicklung

```bash
composer install
composer quality   # cs-check + phpstan + test
```

---

Entwickelt von [Ruhrcoder](https://ruhrcoder.de)

<!-- TRIAGE-WORKFLOW: auto-managed by triage-deploy.ps1 -->
