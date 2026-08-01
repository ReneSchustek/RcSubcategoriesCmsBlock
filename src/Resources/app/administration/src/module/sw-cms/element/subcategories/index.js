import './component';
import './config';
import './preview';

const { Application } = Shopware;

Application.getContainer('service').cmsService.registerCmsElement({
    name: 'subcategories',
    label: 'sw-cms.elements.subcategories.label',
    component: 'sw-cms-el-subcategories',
    configComponent: 'sw-cms-el-config-subcategories',
    previewComponent: 'sw-cms-el-preview-subcategories',
    // Block-Kategorie auf Commerce setzen
    category: 'commerce',
    defaultConfig: {
        parentCategory: {
            source: 'static',
            value: null
        },
        displayMode: {
            source: 'static',
            value: 'grid'
        },
        columnsPerRow: {
            source: 'static',
            value: 4
        },
        showCategoryImage: {
            source: 'static',
            value: true
        },
        showCategoryName: {
            source: 'static',
            value: true
        }
    }
    // Bewusst kein collect(): die alte Variante lud bei jedem CMS-Seitenaufbau ALLE aktiven/sichtbaren
    // Kategorien ungefiltert und unbegrenzt, obwohl das Ergebnis nirgends konsumiert wurde. Die
    // Vorschau-Komponente lädt ihre Unterkategorien selbst (nach parentId gefiltert, siehe
    // component/index.js); die Storefront-Daten liefert der PHP-Resolver via enrich(). Ein globaler
    // Pre-Load ist daher reine Last ohne Nutzen.
});