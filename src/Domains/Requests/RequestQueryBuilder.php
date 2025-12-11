<?php

declare(strict_types=1);

namespace RequestSuite\RequestPhpClient\Domains\Requests;

final class RequestQueryBuilder
{
    /**
     * @param array<string, mixed>|null $input
     * @return array<string, string|int|float|bool|array<int, string|int|float|bool>>|null
     */
    public function build(?array $input): ?array
    {
        if ($input === null) {
            return null;
        }

        $query = [];

        foreach ($input as $key => $value) {
            if ($value === null) {
                continue;
            }

            if (is_array($value)) {
                $filtered = array_values(array_filter($value, static fn ($item) => $item !== null));
                if ($filtered === []) {
                    continue;
                }

                $query[$key] = $filtered;
                continue;
            }

            if (is_scalar($value)) {
                $query[$key] = $value;
            }
        }

        return $query === [] ? null : $query;
    }
}
