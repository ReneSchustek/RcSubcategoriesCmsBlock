<?php

declare(strict_types=1);

namespace Ruhrcoder\RcSubcategoriesCmsBlock\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Vertragstest gegen die Verwaltungs-Komponenten.
 *
 * Eine Vue-Komponente kommt nur an einen Dienst, den sie über `inject` anfordert. Das Mixin
 * `cms-element` gibt ausschließlich `cmsService` weiter — wer `this.repositoryFactory` benutzt,
 * ohne sie zu erklären, greift auf `undefined` zu.
 *
 * Genau das war am 2026-08-18 die Ursache dafür, dass die Vorschau des Bausteins im
 * Erlebniswelten-Editor dauerhaft „Keine Unterkategorien gefunden" meldete: Der Zugriff warf,
 * bevor überhaupt eine Abfrage entstand — kein einziger Aufruf von `/api/search/category`, keine
 * Meldung in der Konsole. Der Fehler ist von außen nicht von einer leeren Kategorie zu
 * unterscheiden, deshalb hält ihn ein Test fest und nicht das Auge.
 */
final class AdministrationInjectionContractTest extends TestCase
{
    private const ADMINISTRATION_PATH = __DIR__ . '/../../src/Resources/app/administration/src';

    private const COMPONENT_PATH = self::ADMINISTRATION_PATH
        . '/module/sw-cms/element/subcategories/component/index.js';

    /**
     * Jede Datei, die den Dienst benutzt, muss ihn auch anfordern. Der Test läuft über den
     * ganzen Verwaltungsteil und greift damit auch bei einer neuen Komponente oder einer
     * Abschrift, an die beim Schreiben dieses Tests niemand gedacht hat.
     */
    public function testEveryComponentUsingTheRepositoryFactoryAlsoInjectsIt(): void
    {
        $verwender = [];
        $ohneInjektion = [];

        foreach ($this->administrationScripts() as $name => $inhalt) {
            if (!str_contains($inhalt, 'this.repositoryFactory')) {
                continue;
            }

            $verwender[] = $name;

            if (preg_match('/inject:\s*\[[^\]]*[\'"]repositoryFactory[\'"]/', $inhalt) !== 1) {
                $ohneInjektion[] = $name;
            }
        }

        self::assertNotSame([], $verwender, 'Keine Verwaltungs-Datei gefunden, die repositoryFactory benutzt');

        self::assertSame(
            [],
            $ohneInjektion,
            'Diese Dateien benutzen this.repositoryFactory, fordern den Dienst aber nicht über '
            . 'inject an — der Zugriff läuft damit auf undefined: ' . implode(', ', $ohneInjektion)
        );
    }

    /**
     * @return array<string, string> Pfad relativ zum Verwaltungsteil => Inhalt
     */
    private function administrationScripts(): array
    {
        $verzeichnis = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(self::ADMINISTRATION_PATH, \FilesystemIterator::SKIP_DOTS)
        );

        $dateien = [];

        /** @var \SplFileInfo $eintrag */
        foreach ($verzeichnis as $eintrag) {
            if ($eintrag->getExtension() !== 'js') {
                continue;
            }

            $inhalt = file_get_contents($eintrag->getPathname());
            self::assertNotFalse($inhalt, sprintf('%s konnte nicht gelesen werden', $eintrag->getPathname()));

            $name = str_replace('\\', '/', substr($eintrag->getPathname(), \strlen(self::ADMINISTRATION_PATH) + 1));
            $dateien[$name] = $inhalt;
        }

        return $dateien;
    }

    /**
     * Der Ausfall braucht einen eigenen Zustand. Ohne ihn steht im Editor derselbe Satz wie bei
     * einer Kategorie ohne Unterkategorien, und der Redakteur sucht den Fehler bei seinen Daten.
     */
    public function testTheComponentSeparatesALoadingErrorFromAnEmptyResult(): void
    {
        $inhalt = file_get_contents(self::COMPONENT_PATH);
        self::assertNotFalse($inhalt, 'component/index.js konnte nicht gelesen werden');

        self::assertStringContainsString(
            'hasLoadingError: false',
            $inhalt,
            'Die Komponente führt keinen eigenen Zustand für einen Ladefehler.'
        );

        self::assertMatchesRegularExpression(
            '/catch\s*\([^)]*\)\s*\{[^}]*this\.hasLoadingError\s*=\s*true/s',
            $inhalt,
            'Der catch-Block setzt den Fehlerzustand nicht — der Ausfall bleibt unsichtbar.'
        );
    }

    /**
     * Der Name kommt aus der Übersetzung. Derselbe Rückfall, den das Storefront-Template seit
     * dem 2026-08-17 beachtet, gilt im Editor genauso.
     */
    public function testTheComponentTemplateNeverReadsTheRawCategoryName(): void
    {
        $template = file_get_contents(
            self::ADMINISTRATION_PATH
            . '/module/sw-cms/element/subcategories/component/sw-cms-el-subcategories.html.twig'
        );
        self::assertNotFalse($template, 'sw-cms-el-subcategories.html.twig konnte nicht gelesen werden');

        preg_match_all('/(?<!translated\.)\bcategory\.name\b/', $template, $treffer);

        self::assertSame(
            [],
            $treffer[0],
            'Das Template der Vorschau liest das Rohfeld category.name statt des übersetzten Namens.'
        );
    }
}
