<?php

declare(strict_types=1);

namespace Ruhrcoder\RcSubcategoriesCmsBlock\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Vertragstest gegen das Storefront-Template.
 *
 * Der Name einer Kategorie ist ein übersetztes Feld. `category.name` liefert nur dann etwas,
 * wenn die aktive Sprache eine eigene Übersetzungszeile mit Inhalt hat; der Rückfall über die
 * Sprachkette steht in `category.translated.name`. Genau daran blieben am 2026-08-17 im Demoshop
 * alle fünf Kacheln namenlos, während Hauptmenü und Brotkrumenpfad derselben Seite die Namen
 * zeigten.
 *
 * Zwei der betroffenen Stellen sind keine Kosmetik: Sind Bild und Name abgeschaltet, ist der
 * `visually-hidden`-Name das Einzige, was dem Verweis einen zugänglichen Namen gibt (WCAG 2.4.4).
 * Bleibt er leer, hat der Verweis keinen.
 */
final class TwigTemplateContractTest extends TestCase
{
    private const TEMPLATE_PATH = __DIR__
        . '/../../src/Resources/views/storefront/element/cms-element-subcategories.html.twig';

    private static function template(): string
    {
        $content = file_get_contents(self::TEMPLATE_PATH);
        self::assertNotFalse($content, 'cms-element-subcategories.html.twig konnte nicht gelesen werden');

        return $content;
    }

    public function testTemplateNeverReadsTheRawCategoryName(): void
    {
        // `category.name` ohne vorangestelltes `translated`. Der Test sucht bewusst nach dem
        // Muster und nicht nach einer festen Zeilenzahl: Er soll auch dann greifen, wenn jemand
        // eine neue Stelle hinzufügt.
        preg_match_all('/(?<!translated\.)\bcategory\.name\b/', self::template(), $treffer);

        self::assertSame(
            [],
            $treffer[0],
            'Das Template liest das Rohfeld category.name. In einer Sprache ohne eigene '
            . 'Übersetzungszeile bleibt der Name damit leer.'
        );
    }

    public function testBothDisplayModesShowTheTranslatedName(): void
    {
        // Raster und Liste — beide Darstellungen zeigen den Namen in einem eigenen Feld.
        $treffer = preg_match_all(
            '/subcategory-name">\s*\{\{ category\.translated\.name \}\}/',
            self::template()
        );

        self::assertSame(
            2,
            $treffer,
            'Raster und Liste müssen den Namen je einmal aus der Übersetzung lesen.'
        );
    }

    public function testHiddenNamesKeepTheLinkAccessible(): void
    {
        self::assertSame(
            2,
            substr_count(self::template(), '<span class="visually-hidden">{{ category.translated.name }}</span>'),
            'Sind Bild und Name abgeschaltet, braucht der Verweis den Namen für die Sprachausgabe.'
        );
    }

    public function testImageAlternativeTextUsesTheTranslatedName(): void
    {
        self::assertSame(
            2,
            substr_count(self::template(), "alt=\"{{ showCategoryName ? '' : category.translated.name }}\""),
            'Der Alternativtext des Bildes springt ein, wenn der Name nicht angezeigt wird.'
        );
    }
}
