<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Widgets\Menu;
use Codeception\Test\Unit;

final class MenuWidgetTest extends Unit
{
    public function testEmptyHeadersTogglesAndLinksWithoutUrlsAreHidden(): void
    {
        $html = Menu::render(
            [
                [
                    'id' => 1,
                    'name' => 'Admin',
                    'header' => true,
                    '_children' => [],
                ],
                [
                    'id' => 3,
                    'name' => 'Reports',
                    'toggle' => true,
                    '_children' => [],
                ],
                [
                    'id' => 4,
                    'name' => 'Invalid',
                    '_children' => [],
                ],
            ],
            '/',
        );

        self::assertSame('', $html);
    }

    public function testLinkWithChildrenKeepsItsOwnHref(): void
    {
        $html = Menu::render(
            [
                [
                    'id' => 1,
                    'name' => 'Reports',
                    'url' => '/reports',
                    '_children' => [
                        [
                            'id' => 2,
                            'name' => 'Sales',
                            'icon' => 'pe-7s-graph',
                            'url' => '/reports/sales',
                            '_children' => [],
                        ],
                    ],
                ],
            ],
            '/reports',
        );

        self::assertStringContainsString('href="/reports"', $html);
        self::assertStringNotContainsString('href="#"', $html);
        self::assertStringNotContainsString('Sales', $html);
    }

    public function testToggleOpensWhenChildLinkIsActive(): void
    {
        $html = Menu::render(
            [
                [
                    'id' => 1,
                    'name' => 'Reports',
                    'toggle' => true,
                    '_children' => [
                        [
                            'id' => 2,
                            'name' => 'Sales',
                            'icon' => 'pe-7s-graph',
                            'url' => '/reports/sales',
                            '_children' => [],
                        ],
                    ],
                ],
            ],
            '/reports/sales',
        );

        self::assertStringContainsString('Reports', $html);
        self::assertStringContainsString('Sales', $html);
        self::assertStringContainsString('metismenu-icon pe-7s-graph', $html);
        self::assertStringContainsString('<li class="mm-active">', $html);
        self::assertStringContainsString('aria-expanded="true"', $html);
        self::assertStringContainsString('class="mm-collapse mm-show"', $html);
    }

    public function testToggleIsInitiallyCollapsedWhenNoChildIsActive(): void
    {
        $html = Menu::render(
            [
                [
                    'id' => 1,
                    'name' => 'Reports',
                    'toggle' => true,
                    '_children' => [
                        [
                            'id' => 2,
                            'name' => 'Sales',
                            'url' => '/reports/sales',
                            '_children' => [],
                        ],
                    ],
                ],
            ],
            '/dashboard',
        );

        self::assertStringContainsString('Reports', $html);
        self::assertStringContainsString('Sales', $html);
        self::assertStringContainsString('aria-expanded="false"', $html);
        self::assertStringContainsString('class="mm-collapse"', $html);
        self::assertStringNotContainsString('mm-show', $html);
    }
}
