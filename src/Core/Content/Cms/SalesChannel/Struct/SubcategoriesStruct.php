<?php

declare(strict_types=1);

namespace Ruhrcoder\RcSubcategoriesCmsBlock\Core\Content\Cms\SalesChannel\Struct;

use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Framework\Struct\Struct;

final class SubcategoriesStruct extends Struct
{
    protected CategoryCollection $subcategories;

    protected ?string $parentCategoryId = null;

    public function __construct()
    {
        $this->subcategories = new CategoryCollection();
    }

    public function getSubcategories(): CategoryCollection
    {
        return $this->subcategories;
    }

    public function setSubcategories(CategoryCollection $subcategories): void
    {
        $this->subcategories = $subcategories;
    }

    public function getParentCategoryId(): ?string
    {
        return $this->parentCategoryId;
    }

    public function setParentCategoryId(?string $parentCategoryId): void
    {
        $this->parentCategoryId = $parentCategoryId;
    }

    public function getApiAlias(): string
    {
        return 'cms_subcategories';
    }
}
