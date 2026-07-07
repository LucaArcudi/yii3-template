<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Shared\Services\RememberedUrlService;
use App\Shared\Services\WebActionService;
use Codeception\Test\Unit;
use HttpSoft\Message\ServerRequest;
use Stringable;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Session\SessionInterface;

use function http_build_query;
use function sprintf;

final class WebActionServiceTest extends Unit
{
    public function testGridUrlCreatorMergesAndCleansQueryParameters(): void
    {
        $service = new WebActionService(
            $this->urlGenerator(),
            new RememberedUrlService($this->session()),
        );

        $urlCreator = $service->gridUrlCreator(
            'user/index',
            ['name' => 'Ada', 'status' => ''],
            ['display' => 'grid'],
        );

        self::assertSame(
            'user/index:id=7?name=Ada&page=2&display=grid',
            $urlCreator(['id' => 7], ['page' => 2, 'status' => null]),
        );
        self::assertSame('-id', $service->sort([], '-id'));
        self::assertSame('name', $service->sort(['sort' => 'name'], '-id'));
        self::assertSame(403, $service->forbidden()->getStatusCode());
        self::assertSame(404, $service->notFound()->getStatusCode());
        self::assertSame(['/user'], $service->redirect('/user')->getHeader('Location'));
        self::assertSame(['/user/view/7'], $service->redirectToView('user', 7)->getHeader('Location'));
    }

    public function testViewNavigationRemembersCurrentUrlAndBackUrl(): void
    {
        $session = $this->session();
        $service = new WebActionService($this->urlGenerator(), new RememberedUrlService($session));
        $request = new ServerRequest(queryParams: ['_return' => '/user?page=2'], uri: '/user/view/7?tab=roles');

        $navigation = $service->viewNavigation('user', 7, $request, '/user');

        self::assertSame('/user/view/7?tab=roles', $navigation->currentUrl);
        self::assertSame('/user?page=2', $navigation->backUrl);
        self::assertSame(
            ['backUrl' => '/user?page=2', 'currentUrl' => '/user/view/7?tab=roles'],
            $navigation->parameters(),
        );
        self::assertSame('/user?page=2', $service->viewBackUrl('user', 7, '/user'));
        self::assertSame('/user', $service->createBackUrl('user', '/user'));
        self::assertSame('/user?page=2', $service->updateBackUrl('user', 7, '/user'));
        self::assertSame('/role', $service->rememberCreateBackUrl('role', new ServerRequest(uri: '/role/create'), '/role'));
        self::assertSame('/role', $service->rememberUpdateBackUrl('role', 3, new ServerRequest(uri: '/role/update/3'), '/role'));
        $service->remember('task.index.cards.filters', '/task?display=cards&status=1');
        self::assertSame('/task?display=cards&status=1', $service->previous('task.index.cards.filters'));
    }

    private function session(): SessionInterface
    {
        return new class implements SessionInterface {
            private array $data = [];

            public function get(string $key, $default = null): mixed
            {
                return $this->data[$key] ?? $default;
            }

            public function set(string $key, $value): void
            {
                $this->data[$key] = $value;
            }

            public function close(): void {}

            public function open(): void {}

            public function isActive(): bool
            {
                return true;
            }

            public function getId(): ?string
            {
                return 'test';
            }

            public function setId(string $sessionId): void {}

            public function regenerateId(): void {}

            public function discard(): void {}

            public function getName(): string
            {
                return 'test';
            }

            public function all(): array
            {
                return $this->data;
            }

            public function remove(string $key): void
            {
                unset($this->data[$key]);
            }

            public function has(string $key): bool
            {
                return isset($this->data[$key]);
            }

            public function pull(string $key, $default = null): mixed
            {
                $value = $this->data[$key] ?? $default;
                unset($this->data[$key]);

                return $value;
            }

            public function clear(): void
            {
                $this->data = [];
            }

            public function destroy(): void
            {
                $this->clear();
            }

            public function getCookieParameters(): array
            {
                return [];
            }
        };
    }

    private function urlGenerator(): UrlGeneratorInterface
    {
        return new class implements UrlGeneratorInterface {
            public function generate(
                string $name,
                array $arguments = [],
                array $queryParameters = [],
                ?string $hash = null,
            ): string {
                return sprintf(
                    '%s:%s?%s%s',
                    $name,
                    http_build_query($arguments),
                    http_build_query($queryParameters),
                    $hash === null ? '' : '#' . $hash,
                );
            }

            public function generateAbsolute(
                string $name,
                array $arguments = [],
                array $queryParameters = [],
                ?string $hash = null,
                ?string $scheme = null,
                ?string $host = null,
            ): string {
                return $this->generate($name, $arguments, $queryParameters, $hash);
            }

            public function generateFromCurrent(
                array $replacedArguments,
                array $queryParameters = [],
                ?string $hash = null,
                ?string $fallbackRouteName = null,
            ): string {
                return $this->generate((string) $fallbackRouteName, $replacedArguments, $queryParameters, $hash);
            }

            public function getUriPrefix(): string
            {
                return '';
            }

            public function setUriPrefix(string $name): void {}

            public function setDefaultArgument(string $name, bool|float|int|string|Stringable|null $value): void {}
        };
    }
}
