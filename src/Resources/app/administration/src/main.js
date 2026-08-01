import './module/sw-cms/element/subcategories';

// Snippet-Registrierung
import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

const { Application } = Shopware;

Application.addInitializerDecorator('locale', (localeFactory) => {
    localeFactory.extend('de-DE', deDE);
    localeFactory.extend('en-GB', enGB);

    return localeFactory;
});