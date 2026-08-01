import template from './sw-cms-el-config-subcategories.html.twig';

const { Component, Mixin } = Shopware;

Component.register('sw-cms-el-config-subcategories', {
    template,

    inject: ['repositoryFactory'],

    mixins: [
        Mixin.getByName('cms-element')
    ],

    data() {
        return {
            categories: null,
            isLoadingCategories: false
        };
    },

    computed: {
        parentCategory: {
            get() {
                return this.element.config.parentCategory.value;
            },

            set(parentCategory) {
                this.element.config.parentCategory.value = parentCategory;
                this.$emit('element-update', this.element);
            }
        },

        displayMode: {
            get() {
                return this.element.config.displayMode.value;
            },

            set(displayMode) {
                this.element.config.displayMode.value = displayMode;
                this.$emit('element-update', this.element);
            }
        },

        columnsPerRow: {
            get() {
                return this.element.config.columnsPerRow.value;
            },

            set(columnsPerRow) {
                this.element.config.columnsPerRow.value = columnsPerRow;
                this.$emit('element-update', this.element);
            }
        },

        showCategoryImage: {
            get() {
                return this.element.config.showCategoryImage.value;
            },

            set(showCategoryImage) {
                this.element.config.showCategoryImage.value = showCategoryImage;
                this.$emit('element-update', this.element);
            }
        },

        showCategoryName: {
            get() {
                return this.element.config.showCategoryName.value;
            },

            set(showCategoryName) {
                this.element.config.showCategoryName.value = showCategoryName;
                this.$emit('element-update', this.element);
            }
        },

        categoryRepository() {
            return this.repositoryFactory.create('category');
        },

        categoryCriteria() {
            const criteria = new Shopware.Data.Criteria();
            criteria.addFilter(Shopware.Data.Criteria.equals('active', true));
            criteria.addFilter(Shopware.Data.Criteria.equals('visible', true));
            criteria.addSorting(Shopware.Data.Criteria.sort('level', 'ASC'));
            // CategoryDefinition hat kein `position`-Feld — nach Name sortieren.
            criteria.addSorting(Shopware.Data.Criteria.sort('name', 'ASC'));
            criteria.setLimit(500);

            return criteria;
        },

        displayModeOptions() {
            return [
                {
                    value: 'grid',
                    label: this.$tc('sw-cms.elements.subcategories.config.displayMode.grid')
                },
                {
                    value: 'list',
                    label: this.$tc('sw-cms.elements.subcategories.config.displayMode.list')
                }
            ];
        },

        columnsOptions() {
            return [
                { value: 1, label: '1' },
                { value: 2, label: '2' },
                { value: 3, label: '3' },
                { value: 4, label: '4' },
                { value: 5, label: '5' },
                { value: 6, label: '6' }
            ];
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        async createdComponent() {
            this.initElementConfig('subcategories');
            await this.loadCategories();
        },

        async loadCategories() {
            this.isLoadingCategories = true;

            try {
                const context = Object.assign(
                    {},
                    Shopware.Context.api,
                    { inheritance: true }
                );

                this.categories = await this.categoryRepository.search(this.categoryCriteria, context);
            } catch (error) {
                Shopware.Utils.debug.error('RcSubcategoriesCmsBlock: Error loading categories:', error);
            } finally {
                this.isLoadingCategories = false;
            }
        }
    }
});