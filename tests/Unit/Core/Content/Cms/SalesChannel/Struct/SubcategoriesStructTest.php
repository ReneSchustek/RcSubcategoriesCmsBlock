<?php

declare(strict_types=1);

namespace Ruhrcoder\RcSubcategoriesCmsBlock\Tests\Unit\Core\Content\Cms\SalesChannel\Struct;

use PHPUnit\Framework\TestCase;
use Ruhrcoder\RcSubcategoriesCmsBlock\Core\Content\Cms\SalesChannel\Struct\SubcategoriesStruct;
use Shopware\Core\Content\Category\CategoryCollection;

final class SubcategoriesStructTest extends TestCase
{
    public function testDefaultSubcategoriesIsEmptyCollection(): void
    {
        $struct = new SubcategoriesStruct();

        $this->assertInstanceOf(CategoryCollection::class, $struct->getSubcategories());
        $this->assertCount(0, $struct->getSubcategories());
    }

    public function testDefaultParentCategoryIdIsNull(): void
    {
        $struct = new SubcategoriesStruct();

        $this->assertNull($struct->getParentCategoryId());
    }

    public function testSetAndGetParentCategoryId(): void
    {
        $struct = new SubcategoriesStruct();
        $struct->setParentCategoryId('abc123');

        $this->assertSame('abc123', $struct->getParentCategoryId());
    }

    public function testSetAndGetSubcategories(): void
    {
        $struct = new SubcategoriesStruct();
        $collection = new CategoryCollection();
        $struct->setSubcategories($collection);

        $this->assertSame($collection, $struct->getSubcategories());
    }

    public function testGetApiAlias(): void
    {
        $struct = new SubcategoriesStruct();

        $this->assertSame('cms_subcategories', $struct->getApiAlias());
    }
}
