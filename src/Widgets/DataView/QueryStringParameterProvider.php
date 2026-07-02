<?php

declare(strict_types=1);

namespace App\Widgets\DataView;

use Yiisoft\Yii\DataView\Url\UrlParameterProviderInterface;
use Yiisoft\Yii\DataView\Url\UrlParameterType;

use function is_string;

final class QueryStringParameterProvider implements UrlParameterProviderInterface
{
    public function __construct(
        private readonly ?array $queryParameters = null,
    ) {}

    public function get(string $name, UrlParameterType $type): ?string
    {
        if ($type !== UrlParameterType::Query) {
            return null;
        }

        $queryParameters = $this->queryParameters ?? $_GET;
        $value = $queryParameters[$name] ?? null;

        return is_string($value) ? $value : null;
    }
}
