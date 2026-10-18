<?php

declare(strict_types=1);

namespace Ruhrcoder\RcSubcategoriesCmsBlock\Tests\Unit\Core\Content\Cms\DataResolver;

use PHPUnit\Framework\TestCase;
use Ruhrcoder\RcSubcategoriesCmsBlock\Core\Content\Cms\DataResolver\SubcategoriesResolver;
use Ruhrcoder\RcSubcategoriesCmsBlock\Core\Content\Cms\SalesChannel\Struct\SubcategoriesStruct;
use Ruhrcoder\RcSubcategoriesCmsBlock\RcSubcategoriesCmsBlock;
use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Shopware\Core\Content\Cms\DataResolver\FieldConfig;
use Shopware\Core\Content\Cms\DataResolver\FieldConfigCollection;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

final class SubcategoriesResolverTest extends TestCase
{
    public function testGetTypeReturnsCmsElementName(): void
    {
        $repository = $this->createMock(SalesChannelRepository::class);
        $resolver = new SubcategoriesResolver($repository);

        self::assertSame(RcSubcategoriesCmsBlock::CMS_ELEMENT_NAME, $resolver->getType());
        self::assertSame('subcategories', $resolver->getType());
    }

    public function testCollectReturnsNull(): void
    {
        $repository = $this->createMock(SalesChannelRepository::class);
        $resolver = new SubcategoriesResolver($repository);

        $slot = $this->createSlot(new FieldConfigCollection());
        $resolverContext = $this->createResolverContext();

        // collect() bereitet absichtlich keine Criteria vor — die parentCategory-ID ist erst in enrich() bekannt.
        self::assertNull($resolver->collect($slot, $resolverContext));
    }

    public function testEnrichWithMissingConfigSetsEmptyStruct(): void
    {
        $repository = $this->createMock(SalesChannelRepository::class);
        $repository->expects($this->never())->method('search');

        $resolver = new SubcategoriesResolver($repository);

        $slot = $this->createSlot(new FieldConfigCollection());
        $resolverContext = $this->createResolverContext();

        $resolver->enrich($slot, $resolverContext, new ElementDataCollection());

        $struct = $slot->getData();
        self::assertInstanceOf(SubcategoriesStruct::class, $struct);
        self::assertNull($struct->getParentCategoryId());
        self::assertCount(0, $struct->getSubcategories());
    }

    public function testEnrichWithEmptyStringParentCategoryIdSetsEmptyStruct(): void
    {
        $repository = $this->createMock(SalesChannelRepository::class);
        $repository->expects($this->never())->method('search');

        $resolver = new SubcategoriesResolver($repository);

        $slot = $this->createSlot($this->configWithParentCategoryValue(''));
        $resolverContext = $this->createResolverContext();

        $resolver->enrich($slot, $resolverContext, new ElementDataCollection());

        $struct = $slot->getData();
        self::assertInstanceOf(SubcategoriesStruct::class, $struct);
        self::assertNull($struct->getParentCategoryId());
    }

    public function testEnrichWithNonStringParentCategoryIdSetsEmptyStruct(): void
    {
        $repository = $this->createMock(SalesChannelRepository::class);
        $repository->expects($this->never())->method('search');

        $resolver = new SubcategoriesResolver($repository);

        // Defensiv: falls die Slot-Konfiguration einen falschen Typ liefert (z.B. Integer statt UUID-String)
        $slot = $this->createSlot($this->configWithParentCategoryValue(12345));
        $resolverContext = $this->createResolverContext();

        $resolver->enrich($slot, $resolverContext, new ElementDataCollection());

        $struct = $slot->getData();
        self::assertInstanceOf(SubcategoriesStruct::class, $struct);
        self::assertNull($struct->getParentCategoryId());
    }

    public function testEnrichWithValidParentCategoryIdLoadsCategoriesAndSetsStruct(): void
    {
        $parentId = '0123456789abcdef0123456789abcdef';

        $child = new CategoryEntity();
        $child->setId('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa');
        $child->setName('Kindkategorie');

        $collection = new CategoryCollection([$child]);

        $searchResult = $this->createMock(EntitySearchResult::class);
        $searchResult->method('getEntities')->willReturn($collection);

        $repository = $this->createMock(SalesChannelRepository::class);
        $repository->expects($this->once())
            ->method('search')
            ->with(
                $this->isInstanceOf(Criteria::class),
                $this->isInstanceOf(SalesChannelContext::class),
            )
            ->willReturn($searchResult);

        $resolver = new SubcategoriesResolver($repository);

        $slot = $this->createSlot($this->configWithParentCategoryValue($parentId));
        $resolverContext = $this->createResolverContext();

        $resolver->enrich($slot, $resolverContext, new ElementDataCollection());

        /** @var SubcategoriesStruct $struct */
        $struct = $slot->getData();
        self::assertInstanceOf(SubcategoriesStruct::class, $struct);
        self::assertSame($parentId, $struct->getParentCategoryId());
        self::assertCount(1, $struct->getSubcategories());
        self::assertSame($child, $struct->getSubcategories()->first());
    }

    /**
     * Was: Die Suchbedingungen, mit denen die Unterkategorien geholt werden.
     * Warum: Hier entscheidet sich, was auf der Seite landet. Fiele einer der Filter weg,
     *        zeigte die Kachelreihe Kategorien, die niemand sehen soll — abgeschaltete,
     *        unsichtbare oder solche ohne eigene Seite. Der Fehler wäre still: Es erschienen
     *        einfach mehr Kacheln, und von außen sieht niemand, dass sie dort nicht hingehören.
     */
    public function testTheCriteriaKeepHiddenCategoriesOut(): void
    {
        $parentId = '0123456789abcdef0123456789abcdef';
        $captured = $this->captureCriteria($parentId);

        $equals = [];
        $excludedTypes = [];
        foreach ($captured->getFilters() as $filter) {
            if ($filter instanceof EqualsFilter) {
                $equals[$filter->getField()] = $filter->getValue();

                continue;
            }

            if ($filter instanceof NotFilter) {
                foreach ($filter->getQueries() as $query) {
                    if ($query instanceof EqualsFilter && $query->getField() === 'type') {
                        $excludedTypes[] = $query->getValue();
                    }
                }
            }
        }

        self::assertSame($parentId, $equals['parentId'] ?? null, 'Ohne parentId käme der ganze Baum.');
        self::assertTrue($equals['active'] ?? null, 'Abgeschaltete Kategorien gehören nicht auf die Seite.');
        self::assertTrue($equals['visible'] ?? null, 'Unsichtbare ebenso wenig.');

        self::assertContains(
            CategoryDefinition::TYPE_LINK,
            $excludedTypes,
            'Verweis-Kategorien haben keine eigene Seite — die Kachel führte ins Leere.',
        );
        self::assertContains(CategoryDefinition::TYPE_FOLDER, $excludedTypes, 'Ordner-Kategorien ebenso.');
    }

    /**
     * Was: Bilder werden mitgeladen, es wird nach Namen sortiert, und die Zahl ist begrenzt.
     * Warum: Ohne die Verknüpfung holte jede Kachel ihr Bild einzeln nach — die N-plus-1-Falle.
     *        Ohne Sortierung wäre die Reihenfolge Zufall der Datenbank. Ohne Obergrenze zöge eine
     *        Kategorie mit tausend Kindern die Seite mit.
     *
     * Sortiert wird bewusst nach dem Namen: `CategoryDefinition` kennt kein Feld `position`, ein
     * Sortieren darauf wirft zur Laufzeit.
     */
    public function testTheCriteriaLoadMediaSortByNameAndCapTheAmount(): void
    {
        $captured = $this->captureCriteria('0123456789abcdef0123456789abcdef');

        self::assertArrayHasKey('media', $captured->getAssociations());

        $sortings = $captured->getSorting();
        self::assertCount(1, $sortings);
        self::assertSame('name', $sortings[0]->getField());
        self::assertSame(FieldSorting::ASCENDING, $sortings[0]->getDirection());

        self::assertSame(100, $captured->getLimit());
    }

    /**
     * Führt `enrich` aus und gibt die Suchbedingungen zurück, mit denen gesucht wurde.
     */
    private function captureCriteria(string $parentId): Criteria
    {
        $searchResult = $this->createMock(EntitySearchResult::class);
        $searchResult->method('getEntities')->willReturn(new CategoryCollection());

        $captured = null;
        $repository = $this->createMock(SalesChannelRepository::class);
        $repository->method('search')->willReturnCallback(
            function (Criteria $criteria) use (&$captured, $searchResult) {
                $captured = $criteria;

                return $searchResult;
            },
        );

        (new SubcategoriesResolver($repository))->enrich(
            $this->createSlot($this->configWithParentCategoryValue($parentId)),
            $this->createResolverContext(),
            new ElementDataCollection(),
        );

        self::assertInstanceOf(Criteria::class, $captured);

        return $captured;
    }

    private function createSlot(FieldConfigCollection $config): CmsSlotEntity
    {
        $slot = new CmsSlotEntity();
        $slot->setUniqueIdentifier('slot-1');
        $slot->setType(RcSubcategoriesCmsBlock::CMS_ELEMENT_NAME);
        $slot->setFieldConfig($config);

        return $slot;
    }

    private function configWithParentCategoryValue(mixed $value): FieldConfigCollection
    {
        $collection = new FieldConfigCollection();
        $collection->add(new FieldConfig('parentCategory', FieldConfig::SOURCE_STATIC, $value));

        return $collection;
    }

    private function createResolverContext(): ResolverContext
    {
        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $salesChannelContext->method('getContext')->willReturn(Context::createDefaultContext());

        $resolverContext = $this->createMock(ResolverContext::class);
        $resolverContext->method('getSalesChannelContext')->willReturn($salesChannelContext);

        return $resolverContext;
    }
}
