# RcSubcategoriesCmsBlock

Ein Shopware-6-Plugin mit einem CMS-Element, das Unterkategorien anzeigt.

---

## Was das Plugin macht

Im Erlebniswelt-Editor fehlt in Shopware ein Standard-Block, der die Unterkategorien einer bestimmten Kategorie von selbst auflistet. Kategoriennavigationen müssen deshalb von Hand gepflegt werden, und mit jedem wachsenden Sortiment wird das mühsamer.

Das Plugin registriert dafür ein neues CMS-Element `rc_subcategories`. Im Block-Editor wird eine Elternkategorie ausgewählt. Das Plugin lädt alle aktiven, sichtbaren Unterkategorien dieser Kategorie, die keine Links sind, samt ihren Bildern und reicht sie als `SubcategoriesStruct` ans Template weiter. Sortiert wird nach Position, danach alphabetisch.

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
4. Speichern, danach werden die Unterkategorien automatisch geladen und dargestellt

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
