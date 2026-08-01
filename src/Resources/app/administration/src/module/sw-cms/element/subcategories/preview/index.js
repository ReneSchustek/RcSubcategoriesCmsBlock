import template from './sw-cms-el-preview-subcategories.html.twig';
import './sw-cms-el-preview-subcategories.scss';

const { Component } = Shopware;

Component.register('sw-cms-el-preview-subcategories', {
    template,

    computed: {
        assetFilter() {
            return Shopware.Filter.getByName('asset');
        }
    }
});