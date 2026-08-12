# Changelog

## [1.1.4] - 2026-08-10 — Backoffice-Übersetzungen werden wieder angemeldet

### Geändert

- **Die Übersetzungen im Backoffice werden wieder angemeldet.** Der bisherige Weg existiert in Shopware 6.7 nicht mehr; der Aufruf brach beim Laden der Administration ab, und alles danach wurde nicht mehr ausgeführt.

## [1.1.3] - 2026-07-20 — Anzeige-Schalter wirken im Storefront

> **Deployment:** `php bin/console cache:clear`.

### Behoben

- **„Kategoriebild anzeigen" / „Kategoriename anzeigen" wirken jetzt im Storefront:** Die beiden Schalter nutzten `…value|default(true)`. Twigs `default`-Filter greift bei jedem leeren Wert — und `false` gilt als leer — sodass ein ausgeschalteter Schalter fälschlich wieder auf `true` fiel und Bild/Name trotzdem gerendert wurden. Umgestellt auf Null-Coalescing (`?? true`), das nur bei null/undefined zurückfällt und ein bewusstes `false` erhält.

## [1.1.2] - 2026-06-28

> **Deployment:** `php bin/console plugin:update RcSubcategoriesCmsBlock && php bin/console cache:clear` + Admin-/Storefront-Asset-Build.

### Geändert

- **Sales-Channel-Scope:** Der `SubcategoriesResolver` nutzt jetzt das `sales_channel.category.repository` (`SalesChannelRepository`) mit dem `SalesChannelContext` statt des Admin-`category.repository` mit nacktem `Context`. Damit respektiert die Storefront-Abfrage die Sales-Channel-Sichtbarkeit, den Entry-Point-Scope und die Sprach-Inheritance der Kategorien.
- **Admin-Performance:** Das ungefilterte, unbegrenzte `collect()` der CMS-Element-Registrierung (`element/subcategories/index.js`) wurde entfernt. Es lud bei jedem CMS-Seitenaufbau alle aktiven/sichtbaren Kategorien, obwohl das Ergebnis nirgends konsumiert wurde — die Vorschau-Komponente lädt ihre Daten selbst (nach `parentId` gefiltert).

### Behoben

- **BFSG (WCAG 2.4.4):** Sind Bild UND Name deaktiviert, erhält der Kategorie-Link jetzt einen visuell versteckten Namen (`visually-hidden`), damit er einen zugänglichen Namen behält (vorher leerer/unzugänglicher Link).
- **BFSG:** Redundantes `title="{{ category.name }}"` an den Links entfernt (Duplikat des sichtbaren Namens).

### Hinweis

- Der irreführende `collect()`-Kommentar im PHP-Resolver wurde korrigiert (die FieldConfig ist auch in `collect()` verfügbar; eine CriteriaCollection lohnt erst beim Batchen mehrerer Slots).

## [1.1.1] - 2026-06-27

> **Deployment:** `php bin/console plugin:update RcSubcategoriesCmsBlock && php bin/console cache:clear` + Admin-/Storefront-Asset-Build.

### Behoben

- **Render-Crash behoben:** Das Element sortierte die Unterkategorien nach `position` — ein Feld, das die Category-Entity nicht hat → beim echten Rendern (und im Admin) eine `UnmappedFieldException`. Jetzt wird nach `name` sortiert (Resolver + beide Admin-Komponenten).
- **HTTP-Cache:** Die Element-Styles liegen jetzt in `Resources/app/storefront/src/scss/base.scss` (Theme-Build) statt als Inline-`<style>` im Template (das blockte den HTTP-Cache).
- **Keine 404-Kacheln:** Folder-Kategorien (ohne eigene Seite) werden nicht mehr verlinkt.
- **Robustheit:** `setLimit(100)` auf die Kategorie-Abfrage.
- **BFSG (WCAG 1.1.1):** Kategorie-Bild ist dekorativ (`alt=""`), wenn der Name sichtbar ist — kein Dreifach-Announce mehr.

### Hinweis

- Sales-Channel-Repository, Admin-`collect`-Limit und kleinere BFSG-Edge-Cases stehen noch aus.

## [1.1.0] - 2026-05-12

> **Deployment:** `php bin/console plugin:update RcSubcategoriesCmsBlock && php bin/console cache:clear`

### Behoben (kritische Funktions-Bugs aus v1.0.0)
- **DI-Service-Datei umbenannt:** `Resources/config/cms.xml` → `services.xml`. Shopware lädt automatisch nur `services.xml` aus `Resources/config/`, weshalb der `SubcategoriesResolver` bisher nie im DI-Container landete und der Storefront-Render des CMS-Blocks faktisch broken war
- **`collect()`-Signatur des `SubcategoriesResolver`** war nicht kompatibel mit `CmsElementResolverInterface` — PHP wirft `Fatal error` beim Klassen-Loading (`(CriteriaCollection, ResolverContext): void` → `(CmsSlotEntity, ResolverContext): ?CriteriaCollection`)
- **`CategoryEntity::TYPE_LINK`** existiert nicht — die Konstante gehört zu `CategoryDefinition`. Der Filter wurde durch `CategoryDefinition::TYPE_LINK` ersetzt
- `services.xml`: Service-ID des `SubcategoriesResolver` mit Vendor-Namespace-Prefix `Ruhrcoder\` (Konsistenz mit `extra.shopware-plugin-class` und Klassen-FQN)
- `enrich()`: explizite String-Prüfung der `parentCategory`-Slot-Konfiguration (statt impliziter `if (!$id)` mit Type-Coercion)

### Geändert
- `composer.json`: Shopware-6.8-Kompatibilität (`~6.8.0` ergänzt), expliziter `php >=8.2`-Constraint, `require-dev` (PHPUnit, PHPStan, PHP-CS-Fixer), `scripts.quality` (cs-check + phpstan + test), `authors`, `manufacturerLink`/`supportLink` von Platzhalter `example.com` auf `ruhrcoder.de` umgestellt
- PHP-Dateien: `declare(strict_types=1);` auf eigene Zeile (PSR-12-konform), Struct-Properties getrennt, Imports alphabetisch sortiert
- `SubcategoriesResolver`: PHPStan-Generic auf `EntityRepository<CategoryCollection>` ergänzt

### Hinzugefügt
- `phpunit.xml` und `.php-cs-fixer.php` (Voraussetzung für lokale Quality-Gates)
- `tests/TestBootstrap.php`
- 6 neue Unit-Tests für `SubcategoriesResolver` (Edge-Cases: keine Config, leerer String, falscher Typ, gültige ID, getType, collect-no-op)

### Bewusste Schulden (geplant für Phase 3.4)
- composer.json `name`: weiterhin `rc/subcategories-cms-block` statt `ruhrcoder/rc-subcategories-cms-block` — Änderung erfordert DB-Migration auf `composer_name` der `plugin`-Tabelle und wird sauber im Rahmen der RcCmsBlocks-Konsolidierung erledigt
- Inline-`<style>`-Block in `cms-element-subcategories.html.twig` (Zeile 105-238) — Auslagerung in SCSS erfordert komplettes Storefront-Build-Setup und wird im Rahmen der RcCmsBlocks-Konsolidierung sauber gesammelt
