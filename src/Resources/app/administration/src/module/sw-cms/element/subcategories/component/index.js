import template from './sw-cms-el-subcategories.html.twig';
import './sw-cms-el-subcategories.scss';

const { Component, Mixin } = Shopware;

Component.register('sw-cms-el-subcategories', {
    template,

    mixins: [
        Mixin.getByName('cms-element')
    ],

    data() {
        return {
            subcategories: [],
            isLoading: false
        };
    },

    computed: {
        parentCategoryId() {
            return this.element.config.parentCategory.value;
        },

        displayMode() {
            return this.element.config.displayMode.value || 'grid';
        },

        columnsPerRow() {
            return this.element.config.columnsPerRow.value || 4;
        },

        showCategoryImage() {
            return this.element.config.showCategoryImage.value !== false;
        },

        showCategoryName() {
            return this.element.config.showCategoryName.value !== false;
        },

        gridColumns() {
            return `repeat(${this.columnsPerRow}, 1fr)`;
        }
    },

    watch: {
        parentCategoryId: {
            handler(newVal) {
                if (newVal) {
                    this.loadSubcategories();
                } else {
                    this.subcategories = [];
                }
            },
            immediate: true
        }
    },

    methods: {
        async loadSubcategories() {
            if (!this.parentCategoryId) {
                return;
            }

            this.isLoading = true;

            try {
                const context = Object.assign(
                    {},
                    Shopware.Context.api,
                    { inheritance: true }
                );

                const criteria = new Shopware.Data.Criteria();
                criteria.addFilter(Shopware.Data.Criteria.equals('parentId', this.parentCategoryId));
                criteria.addFilter(Shopware.Data.Criteria.equals('active', true));
                criteria.addFilter(Shopware.Data.Criteria.equals('visible', true));
                criteria.addAssociation('media');
                // CategoryDefinition hat kein `position`-Feld — nach Name sortieren.
                criteria.addSorting(Shopware.Data.Criteria.sort('name', 'ASC'));

                const categoryRepository = this.repositoryFactory.create('category');
                const result = await categoryRepository.search(criteria, context);

                this.subcategories = result;
            } catch (error) {
                Shopware.Utils.debug.error('RcSubcategoriesCmsBlock: Error loading subcategories:', error);
                this.subcategories = [];
            } finally {
                this.isLoading = false;
            }
        },

        getCategoryImageUrl(category) {
            if (category.media && category.media.url) {
                return category.media.url;
            }
            return null;
        }
    }
});