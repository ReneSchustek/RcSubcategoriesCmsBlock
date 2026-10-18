import './module/sw-cms/element/subcategories';

// Snippet-Registrierung
import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

/*
 * Übersetzungen werden über `Locale.extend` angemeldet. Einen Initializer-Dekorator für `locale`
 * gibt es in Shopware 6.7 nicht; wer ihn aufruft, wirft bei jedem Laden der Administration eine
 * Ausnahme. Die Übersetzungen kämen über die serverseitige Auslieferung trotzdem an, aber ein
 * roter Eintrag in der Konsole macht jede echte Meldung schwerer auffindbar.
 */
const { Locale } = Shopware;

Locale.extend('de-DE', deDE);
Locale.extend('en-GB', enGB);