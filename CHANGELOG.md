# Changelog

## [1.2.0] - 2026-10-13: Farben und Ecken vom Theme, Farben je Baustein

> **Deployment:** `plugin:update RcSubcategoriesCmsBlock`, `theme:compile`, `cache:clear`.

### Geändert

- **Keine eigene Palette mehr.** Radius, Rahmen, Text und Flächen kommen aus dem Theme. Vorher brachte der
  Stilbogen eigene Grautöne und 8 Pixel Radius mit und fiel damit in Design 2027 heraus. Im bisherigen Theme
  ändert sich wenig: Der Radius bleibt bei 8 Pixeln, die Grautöne werden die des Themes.

### Hinzugefügt

- **Farben im Baustein:** Textfarbe, in der Listenansicht auch Rahmen und Hintergrund der Karten. Leer heißt
  Theme-Farbe; die Angabe gilt nur für diesen Baustein.

## [1.1.8] - 2026-08-19: Die Einstellung des Bausteins zeigt endlich ihre Werte

### Behoben

- **Die beiden Schalter „Kategoriebild anzeigen" und „Kategoriename anzeigen" waren unsichtbar.**
  Sie standen als `sw-field` in der Vorlage, und diese Komponente gibt es seit Shopware 6.7 nicht
  mehr. Unbekannte Komponenten bleiben still leer. Damit ließ sich weder das Bild noch der Name
  abschalten; beide sind in der Vorgabe an und blieben es.
- **Hauptkategorie, Anzeigemodus und Spalten pro Reihe standen leer da**, obwohl Werte hinterlegt
  waren. Die Vorlage band mit `v-model`, die Felder führen ihren Wert aber über `value`. Das Feld
  bekam nichts zu sehen und meldete nichts.
- Ein Vertragstest hält beides fest: ein entferntes Bauteil in einer Vorlage und ein Feld, das mit
  `v-model` statt `v-model:value` gebunden ist.

Die Storefront war nie betroffen: Sie liest die gespeicherte Einstellung direkt.

## [1.1.7] - 2026-08-18: Die Vorschau im Editor zeigt endlich die Kacheln

### Behoben

- **Die Vorschau des Bausteins blieb im Erlebniswelten-Editor immer leer.** Dort stand dauerhaft
  „Keine Unterkategorien gefunden", auch wenn die eingestellte Hauptkategorie sehr wohl
  Unterkategorien hatte und die Storefront sie als Kacheln zeigte. Die anzeigende Komponente
  benutzte den Dienst für den Datenzugriff, ohne ihn anzufordern; der Zugriff lief damit ins
  Leere, bevor überhaupt eine Abfrage entstand. Nachgemessen: kein einziger Aufruf der
  Kategorie-Suche. Jetzt wird der Dienst angefordert, und die Vorschau zeigt, was der Baustein
  später auch in der Storefront zeigt.

- **Ein Ausfall sah aus wie eine leere Kategorie.** Beide Fälle führten zu demselben Satz, und
  ein Fehler wurde nur still weggeschrieben. Wer den Baustein einrichtete, suchte den Fehler
  deshalb bei seinen Daten. Ein Ladefehler hat jetzt einen eigenen, deutlich abgesetzten Hinweis.

- **Auch im Editor stand unter den Kacheln kein Name**, wenn die Kategorie in der geladenen
  Sprache keine eigene Übersetzung hat. Dieselbe Berichtigung wie in der Storefront (1.1.6),
  jetzt auch in der Vorschau und in den Alternativtexten der Bilder.

### Intern

- Ein Vertragstest hält fest, dass jede Komponente des Verwaltungsteils den Datenzugriff auch
  anfordert, und dass Ladefehler und leeres Ergebnis getrennt bleiben. Vor der Berichtigung
  schlug er fehl.

## [1.1.6] - 2026-08-17: Der Baustein lässt sich wieder einsetzen, die Kacheln tragen wieder Namen

### Behoben

- **Unter den Kacheln stand kein Name.** Der Name einer Kategorie ist ein übersetztes Feld; gelesen
  wurde das Rohfeld. In einer Sprache, in der die Kategorie keine eigene Übersetzung hat, blieb es
  leer, und die Kacheln zeigten nur ihr Bild. Betroffen war damit auch der Alternativtext des Bildes
  und, mit Bild und Name abgeschaltet, der Name für die Sprachausgabe: Der Verweis hatte dann gar
  keinen (WCAG 2.4.4). Jetzt wird die Übersetzung gelesen, mit Rückfall über die Sprachkette.

- **Der Baustein war im Erlebniswelten-Editor nicht benutzbar.** An seiner Stelle stand „Element
  could not be load"; einsetzen ließ er sich gar nicht erst. Ursache waren die fehlenden gebauten
  Dateien des Backoffice-Teils. Ohne sie kündigt Shopware das Bündel nicht an und lädt es nie,
  und das ohne Ausnahme, ohne fehlgeschlagene Anfrage und ohne Eintrag in der Konsole. In der Storefront
  erschien der Baustein die ganze Zeit normal, weshalb der Ausfall von außen nicht zu sehen war.
  Wer den Verwaltungsbereich nach der Installation neu baut, hat ihn nie bemerkt.

## [1.1.5] - 2026-08-12: Die Auswahl der Kacheln ist jetzt festgenagelt

### Neu

- **Prüfungen für die Bedingungen, nach denen Unterkategorien geholt werden.** Bisher war nur
  geprüft, dass überhaupt gesucht wird. Fiele eine der Bedingungen weg, zeigte die Kachelreihe
  Kategorien, die niemand sehen soll (abgeschaltete, unsichtbare oder solche ohne eigene Seite),
  und von außen sähe es nach „so viele Unterkategorien gibt es eben" aus. Geprüft sind jetzt auch
  das Mitladen der Bilder, die Sortierung nach Namen und die Obergrenze.

## [1.1.4] - 2026-08-10: Backoffice-Übersetzungen werden wieder angemeldet

### Geändert

- **Die Übersetzungen im Backoffice werden wieder angemeldet.** Der bisherige Weg existiert in Shopware 6.7 nicht mehr; der Aufruf brach beim Laden der Administration ab, und alles danach wurde nicht mehr ausgeführt.

## [1.1.3] - 2026-07-20: Anzeige-Schalter wirken im Storefront

> **Deployment:** `php bin/console cache:clear`.

### Behoben

- **„Kategoriebild anzeigen" und „Kategoriename anzeigen" wirken jetzt im Storefront.** Die beiden Schalter nutzten `…value|default(true)`. Der `default`-Filter von Twig greift bei jedem leeren Wert, und `false` gilt als leer. Ein ausgeschalteter Schalter fiel deshalb fälschlich wieder auf `true`, und Bild und Name wurden trotzdem gerendert. Jetzt steht dort Null-Coalescing (`?? true`). Das fällt nur bei null/undefined zurück und lässt ein bewusstes `false` stehen.

## [1.1.2] - 2026-06-28

> **Deployment:** `php bin/console plugin:update RcSubcategoriesCmsBlock && php bin/console cache:clear` + Admin-/Storefront-Asset-Build.

### Geändert

- **Sales-Channel-Scope:** Der `SubcategoriesResolver` nutzt jetzt das `sales_channel.category.repository` (`SalesChannelRepository`) mit dem `SalesChannelContext` statt des Admin-`category.repository` mit nacktem `Context`. Damit respektiert die Storefront-Abfrage die Sales-Channel-Sichtbarkeit, den Entry-Point-Scope und die Sprach-Inheritance der Kategorien.
- **Admin-Performance:** Das ungefilterte, unbegrenzte `collect()` der CMS-Element-Registrierung (`element/subcategories/index.js`) wurde entfernt. Es lud bei jedem Aufbau einer CMS-Seite alle aktiven und sichtbaren Kategorien, obwohl niemand das Ergebnis verwendete. Die Vorschau-Komponente lädt ihre Daten ohnehin selbst (nach `parentId` gefiltert).

### Behoben

- **BFSG (WCAG 2.4.4):** Sind Bild UND Name deaktiviert, erhält der Kategorie-Link jetzt einen visuell versteckten Namen (`visually-hidden`), damit er einen zugänglichen Namen behält (vorher leerer/unzugänglicher Link).
- **BFSG:** Redundantes `title="{{ category.name }}"` an den Links entfernt (Duplikat des sichtbaren Namens).

### Hinweis

- Der irreführende `collect()`-Kommentar im PHP-Resolver wurde korrigiert (die FieldConfig ist auch in `collect()` verfügbar; eine CriteriaCollection lohnt erst beim Batchen mehrerer Slots).

## [1.1.1] - 2026-06-27

> **Deployment:** `php bin/console plugin:update RcSubcategoriesCmsBlock && php bin/console cache:clear` + Admin-/Storefront-Asset-Build.

### Behoben

- **Render-Crash behoben:** Das Element sortierte die Unterkategorien nach `position`, einem Feld, das die Category-Entity gar nicht hat. Beim echten Rendern (und im Admin) folgte eine `UnmappedFieldException`. Jetzt wird nach `name` sortiert, im Resolver und in beiden Admin-Komponenten.
- **HTTP-Cache:** Die Element-Styles liegen jetzt in `Resources/app/storefront/src/scss/base.scss` (Theme-Build) statt als Inline-`<style>` im Template (das blockte den HTTP-Cache).
- **Keine 404-Kacheln:** Folder-Kategorien (ohne eigene Seite) werden nicht mehr verlinkt.
- **Robustheit:** `setLimit(100)` auf die Kategorie-Abfrage.
- **BFSG (WCAG 1.1.1):** Das Kategorie-Bild ist dekorativ (`alt=""`), wenn der Name sichtbar ist. Der Name wird damit nicht mehr dreifach angesagt.

### Hinweis

- Sales-Channel-Repository, Admin-`collect`-Limit und kleinere BFSG-Edge-Cases stehen noch aus.

## [1.1.0] - 2026-05-12

> **Deployment:** `php bin/console plugin:update RcSubcategoriesCmsBlock && php bin/console cache:clear`

### Behoben (kritische Funktions-Bugs aus v1.0.0)
- **DI-Service-Datei umbenannt:** `Resources/config/cms.xml` → `services.xml`. Shopware lädt automatisch nur `services.xml` aus `Resources/config/`, weshalb der `SubcategoriesResolver` bisher nie im DI-Container landete und der CMS-Block in der Storefront praktisch nicht renderte
- **`collect()`-Signatur des `SubcategoriesResolver`** passte nicht zu `CmsElementResolverInterface`, PHP warf beim Laden der Klasse einen `Fatal error` (`(CriteriaCollection, ResolverContext): void` → `(CmsSlotEntity, ResolverContext): ?CriteriaCollection`)
- **`CategoryEntity::TYPE_LINK`** existiert nicht, die Konstante gehört zu `CategoryDefinition`. Der Filter wurde durch `CategoryDefinition::TYPE_LINK` ersetzt
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
- composer.json `name`: weiterhin `rc/subcategories-cms-block` statt `ruhrcoder/rc-subcategories-cms-block`. Eine Änderung braucht eine DB-Migration auf `composer_name` der `plugin`-Tabelle und wird sauber im Rahmen der RcCmsBlocks-Konsolidierung erledigt
- Inline-`<style>`-Block in `cms-element-subcategories.html.twig` (Zeile 105-238). Die Auslagerung in SCSS setzt ein komplettes Storefront-Build-Setup voraus und wird im Rahmen der RcCmsBlocks-Konsolidierung gesammelt erledigt
