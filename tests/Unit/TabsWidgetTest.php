<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Shared\Widgets\Tabs;
use Codeception\Test\Unit;

final class TabsWidgetTest extends Unit
{
    public function testRenderMarksRequestedTabActive(): void
    {
        $html = Tabs::render(
            [
                [
                    'id' => 'grid',
                    'label' => 'Tabella',
                    'content' => 'Grid content',
                ],
                [
                    'id' => 'cards',
                    'label' => 'Card',
                    'content' => 'Card content',
                    'url' => '/task?display=cards',
                    'active' => true,
                ],
            ],
            'task-tabs',
        );

        self::assertStringContainsString('id="task-tabs"', $html);
        self::assertStringContainsString('id="task-tabs-cards-tab"', $html);
        self::assertStringContainsString('data-bs-target="#task-tabs-cards"', $html);
        self::assertStringContainsString('data-tab-id="cards"', $html);
        self::assertStringContainsString('data-tab-url="/task?display=cards"', $html);
        self::assertStringContainsString('class="nav-link active"', $html);
        self::assertStringContainsString('Card content', $html);
    }
}
