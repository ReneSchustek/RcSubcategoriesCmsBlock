<?php

declare(strict_types=1);

namespace Ruhrcoder\RcSubcategoriesCmsBlock\Core\Content\Cms\DataResolver;

use Ruhrcoder\RcSubcategoriesCmsBlock\Core\Content\Cms\SalesChannel\Struct\SubcategoriesStruct;
use Ruhrcoder\RcSubcategoriesCmsBlock\RcSubcategoriesCmsBlock;
use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\DataResolver\CriteriaCollection;
use Shopware\Core\Content\Cms\DataResolver\Element\AbstractCmsElementResolver;
use Shopware\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

final class SubcategoriesResolver extends AbstractCmsElementResolver
{
    /**
     * Sales-Channel-scoped Repository: respektiert die Sichtbarkeit/den Sales-Channel-Scope der
     * Kategorien (z.B. Sales-Channel-Entry-Point, Sprach-Inheritance), anders als das Admin-Repo
     * `category.repository`, das ohne Storefront-Kontext alle Kategorien liefert.
     *
     * @param SalesChannelRepository<CategoryCollection> $categoryRepository
     */
    public function __construct(
        private readonly SalesChannelRepository $categoryRepository,
    ) {
    }

    public function getType(): string
    {
        return RcSubcategoriesCmsBlock::CMS_ELEMENT_NAME;
    }

    public function collect(CmsSlotEntity $slot, ResolverContext $resolverContext): ?CriteriaCollection
    {
        // Die parentCategory-ID liesse sich hier zwar über $slot->getFieldConfig() lesen, doch das
        // Element lädt genau eine To-Many-Abfrage. Eine CriteriaCollection in collect() lohnt sich
        // erst beim Batchen mehrerer Slots; für einen einzelnen Slot bleibt die Abfrage in enrich().
        return null;
    }

    public function enrich(CmsSlotEntity $slot, ResolverContext $resolverContext, ElementDataCollection $result): void
    {
        $config = $slot->getFieldConfig();
        $parentCategoryConfig = $config->get('parentCategory');
        $parentCategoryId = $parentCategoryConfig?->getValue();

        if (!\is_string($parentCategoryId) || $parentCategoryId === '') {
            $slot->setData(new SubcategoriesStruct());

            return;
        }

        $subcategories = $this->getSubcategories($parentCategoryId, $resolverContext->getSalesChannelContext());

        $struct = new SubcategoriesStruct();
        $struct->setSubcategories($subcategories);
        $struct->setParentCategoryId($parentCategoryId);

        $slot->setData($struct);
    }

    private function getSubcategories(string $parentCategoryId, SalesChannelContext $context): CategoryCollection
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('parentId', $parentCategoryId));
        $criteria->addFilter(new EqualsFilter('active', true));
        $criteria->addFilter(new EqualsFilter('visible', true));
        // Weder Link- noch Folder-Kategorien: beide haben keine eigene Navigationsseite und
        // würden sonst als 404-Kachel verlinkt.
        $criteria->addFilter(new NotFilter(NotFilter::CONNECTION_AND, [
            new EqualsFilter('type', CategoryDefinition::TYPE_LINK),
        ]));
        $criteria->addFilter(new NotFilter(NotFilter::CONNECTION_AND, [
            new EqualsFilter('type', CategoryDefinition::TYPE_FOLDER),
        ]));

        $criteria->addAssociation('media');
        // CategoryDefinition kennt KEIN `position`-Feld — ein Sort darauf wirft zur Laufzeit eine
        // UnmappedFieldException. Geschwister werden nach Name sortiert.
        $criteria->addSorting(new FieldSorting('name', FieldSorting::ASCENDING));
        $criteria->setLimit(100);

        /** @var CategoryCollection $categories */
        $categories = $this->categoryRepository->search($criteria, $context)->getEntities();

        return $categories;
    }
}
