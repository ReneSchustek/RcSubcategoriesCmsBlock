<?php

declare(strict_types=1);

namespace Ruhrcoder\RcSubcategoriesCmsBlock\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Vertragstest gegen die Vorlagen des Verwaltungsteils.
 *
 * Öffnet sich die Einstellung des Bausteins, ist aber leer, wo Werte stehen müssten, gibt es zwei
 * Ursachen, beide ohne jede Meldung:
 *
 * 1. `sw-field` kennt Shopware 6.7 nicht. Eine unbekannte Komponente bleibt als
 *    unaufgelöste Marke im Baum stehen, und die beiden Schalter „Kategoriebild anzeigen" und
 *    „Kategoriename anzeigen" sind schlicht nicht da.
 * 2. `v-model` schreibt `modelValue`. Die Felder der Verwaltung nehmen ihren Wert aber über die
 *    Eigenschaft `value` entgegen und geben ihn über `update:value` heraus. Das Feld sieht dann
 *    nichts und meldet nichts: Hauptkategorie, Anzeigemodus und Spaltenzahl stehen leer da,
 *    obwohl Werte hinterlegt sind. Richtig ist `v-model:value`.
 *
 * Beides ist nur im laufenden Erlebniswelten-Editor zu sehen. Deshalb hält es dieser Test fest.
 */
final class AdministrationTemplateContractTest extends TestCase
{
    private const ADMINISTRATION_PATH = __DIR__ . '/../../src/Resources/app/administration/src';

    /**
     * Komponenten, die Shopware 6.7 nicht kennt, samt Nachfolger für die Fehlermeldung.
     */
    private const REMOVED_COMPONENTS = [
        'sw-field' => 'sw-switch-field, sw-text-field, sw-textarea-field (je nach Typ)',
    ];

    /**
     * Felder, die ihren Wert über `value` führen — hier ist `v-model` allein zu wenig.
     */
    private const VALUE_BOUND_COMPONENTS = [
        'sw-entity-single-select',
        'sw-single-select',
        'sw-switch-field',
        'sw-text-field',
        'sw-textarea-field',
    ];

    public function testNoTemplateUsesAComponentThatShopwareRemoved(): void
    {
        $offenders = [];

        foreach ($this->administrationTemplates() as $name => $content) {
            foreach (self::REMOVED_COMPONENTS as $component => $successor) {
                if (preg_match('/<' . preg_quote($component, '/') . '[\s>]/', $content) === 1) {
                    $offenders[] = $name . ': <' . $component . '> → ' . $successor;
                }
            }
        }

        self::assertSame(
            [],
            $offenders,
            "Vorlage benutzt eine Komponente, die es im Verzeichnis der Verwaltung nicht gibt:\n"
            . implode("\n", $offenders)
        );
    }

    public function testEveryFieldBindsItsValueAndNotTheModelValue(): void
    {
        $offenders = [];

        foreach ($this->administrationTemplates() as $name => $content) {
            foreach ($this->fieldTags($content) as $tag) {
                if (preg_match('/\sv-model="/', $tag['markup']) === 1) {
                    $offenders[] = sprintf(
                        '%s: <%s> bindet mit v-model statt v-model:value — das Feld sieht den Wert nie',
                        $name,
                        $tag['component']
                    );
                }
            }
        }

        self::assertSame(
            [],
            $offenders,
            "Feld bekommt seinen Wert nicht:\n" . implode("\n", $offenders)
        );
    }

    /**
     * Gegenprobe: Die Einstellung muss die fünf Felder auch wirklich führen — sonst wäre der
     * Test auch dann grün, wenn jemand sie entfernt.
     */
    public function testElementConfigBindsAllFiveSettings(): void
    {
        $template = $this->elementConfigTemplate();

        foreach (['parentCategory', 'displayMode', 'columnsPerRow', 'showCategoryImage', 'showCategoryName'] as $setting) {
            self::assertStringContainsString(
                'v-model:value="' . $setting . '"',
                $template,
                'Einstellung ' . $setting . ' ist nicht an ihr Feld gebunden.'
            );
        }

        self::assertSame(
            2,
            substr_count($template, '<sw-switch-field'),
            'Die Einstellung braucht beide Schalter — Kategoriebild und Kategoriename.'
        );
    }

    /**
     * @return list<array{component: string, markup: string}>
     */
    private function fieldTags(string $content): array
    {
        $tags = [];

        foreach (self::VALUE_BOUND_COMPONENTS as $component) {
            $pattern = '/<' . preg_quote($component, '/') . '\b.*?>/s';

            if (preg_match_all($pattern, $content, $matches) === 0) {
                continue;
            }

            foreach ($matches[0] as $markup) {
                $tags[] = ['component' => $component, 'markup' => $markup];
            }
        }

        return $tags;
    }

    private function elementConfigTemplate(): string
    {
        $path = self::ADMINISTRATION_PATH
            . '/module/sw-cms/element/subcategories/config/sw-cms-el-config-subcategories.html.twig';

        $content = file_get_contents($path);
        self::assertNotFalse($content, 'Vorlage der Einstellung nicht lesbar: ' . $path);

        return $content;
    }

    /**
     * @return array<string, string> Dateiname => Inhalt
     */
    private function administrationTemplates(): array
    {
        $directory = new \RecursiveDirectoryIterator(self::ADMINISTRATION_PATH);
        $templates = [];

        foreach (new \RecursiveIteratorIterator($directory) as $file) {
            if (!$file->isFile() || !str_ends_with($file->getFilename(), '.html.twig')) {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            self::assertNotFalse($content);

            $templates[$file->getFilename()] = $content;
        }

        self::assertNotEmpty($templates, 'Kein Verwaltungs-Template gefunden — der Test misst sonst nichts.');

        return $templates;
    }
}
