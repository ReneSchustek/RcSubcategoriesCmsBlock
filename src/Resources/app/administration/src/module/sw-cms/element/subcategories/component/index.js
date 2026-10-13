import template from './sw-cms-el-subcategories.html.twig';
import './sw-cms-el-subcategories.scss';

const { Component, Mixin } = Shopware;

Component.register('sw-cms-el-subcategories', {
    template,

    // Das Mixin `cms-element` stellt nur `cmsService` bereit — die Datenquelle muss die
    // Komponente selbst anfordern.
    inject: ['repositoryFactory'],

    mixins: [
        Mixin.getByName('cms-element')
    ],

    data() {
        return {
            subcategories: [],
            isLoading: false,
            hasLoadingError: false
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
                    this.hasLoadingError = false;
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
            this.hasLoadingError = false;

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
                // Ein technischer Ausfall darf nicht aussehen wie eine Kategorie ohne
                // Unterkategorien — sonst sucht der Redakteur den Fehler bei seinen Daten.
                this.hasLoadingError = true;
                this.subcategories = [];

                Shopware.Utils.debug.error(
                    `RcSubcategoriesCmsBlock: Unterkategorien zu ${this.parentCategoryId} nicht ladbar`,
                    error
                );
            } finally {
                this.isLoading = false;
            }
        },

        /**
         * Der Name kommt aus der Übersetzung, nicht aus dem Rohfeld: Ohne eigene Zeile in der
         * geladenen Sprache bleibt `name` leer, während `translated.name` auf die Systemsprache
         * zurückfällt. Der Rückfall auf `name` deckt Objekte ohne geladene Übersetzung ab.
         */
        getCategoryName(category) {
            return category.translated?.name ?? category.name;
        },

        getCategoryImageUrl(category) {
            if (category.media && category.media.url) {
                return category.media.url;
            }
            return null;
        }
    }
});
