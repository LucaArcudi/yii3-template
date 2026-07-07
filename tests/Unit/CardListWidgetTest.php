<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Shared\Widgets\DataView\CardList;
use Codeception\Test\Unit;
use Yiisoft\Data\Reader\Iterable\IterableDataReader;

final class CardListWidgetTest extends Unit
{
    public function testRenderHidesSummaryWhenThereAreNoResults(): void
    {
        $html = CardList::render(
            title: 'Task a card',
            reader: new IterableDataReader([]),
            itemRenderer: static fn(array|object $row): string => '<article>unused</article>',
            urlCreator: static fn(int $page): string => '/task?display=cards&page=' . $page,
        );

        self::assertStringContainsString('Nessun elemento trovato con i filtri correnti.', $html);
        self::assertStringNotContainsString('Visualizzando', $html);
        self::assertStringNotContainsString('0-0', $html);
        self::assertStringNotContainsString('app-card-list__footer', $html);
    }

    public function testRenderShowsSummaryWhenThereAreResults(): void
    {
        $html = CardList::render(
            title: 'Task a card',
            reader: new IterableDataReader([
                ['id' => 1, 'title' => 'Deploy'],
                ['id' => 2, 'title' => 'Review'],
            ]),
            itemRenderer: static fn(array|object $row): string => '<article>' . $row['title'] . '</article>',
            urlCreator: static fn(int $page): string => '/task?display=cards&page=' . $page,
        );

        self::assertStringContainsString('Visualizzando <strong>1-2</strong> di <strong>2</strong>', $html);
    }
}
