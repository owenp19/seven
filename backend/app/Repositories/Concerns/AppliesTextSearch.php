<?php

namespace App\Repositories\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Applies the free-text search filter honouring `config('api.search.mode')`.
 *
 * - `like`     → `LIKE %term%` on every column. Portable, index-unfriendly,
 *                and the default because it works on every driver.
 * - `fulltext` → `MATCH ... AGAINST` in boolean mode, which can use a
 *                FULLTEXT index. Only honoured when the driver is MySQL and
 *                the index actually exists, so flipping the config can never
 *                turn a working query into a 500 on a database that has not
 *                been migrated yet; it degrades to `like` instead.
 *
 * Terms are truncated to the InnoDB minimum word length (4) because
 * MATCH...AGAINST silently drops shorter tokens, which would otherwise make
 * the two modes disagree on short searches.
 */
trait AppliesTextSearch
{
    /** InnoDB's default ft_min_word_len / ft_min_token_size. */
    private const FULLTEXT_MIN_WORD_LENGTH = 4;

    /**
     * @param  array<int, string>  $columns
     */
    protected function applyTextSearch(Builder $query, ?string $term, array $columns): Builder
    {
        $term = is_string($term) ? trim($term) : '';

        if ($term === '' || $columns === []) {
            return $query;
        }

        if ($this->canUseFullText($columns)) {
            $prepared = $this->prepareFullTextTerm($term);

            if ($prepared !== null) {
                return $query->whereRaw(
                    'MATCH (`'.implode('`, `', $columns).'`) AGAINST (? IN BOOLEAN MODE)',
                    [$prepared]
                );
            }
        }

        return $query->where(function (Builder $inner) use ($term, $columns) {
            foreach ($columns as $column) {
                $inner->orWhere($column, 'like', '%'.$term.'%');
            }
        });
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function canUseFullText(array $columns): bool
    {
        if (config('api.search.mode') !== 'fulltext') {
            return false;
        }

        if ($this->model->getConnection()->getDriverName() !== 'mysql') {
            return false;
        }

        foreach ($this->fullTextIndexes() as $index) {
            // A FULLTEXT index is named <table>_<col1>_<col2>_fulltext by the
            // migration helper; accept any index covering all the columns.
            $indexed = array_filter(explode('_', $index));

            if (count(array_intersect($columns, $indexed)) === count($columns)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    private function fullTextIndexes(): array
    {
        $connection = $this->model->getConnection();

        return $connection->getDriverName() === 'mysql'
            ? $connection->table('information_schema.STATISTICS')
                ->whereRaw('TABLE_SCHEMA = DATABASE()')
                ->where('TABLE_NAME', $this->model->getTable())
                ->where('INDEX_TYPE', 'FULLTEXT')
                ->distinct()
                ->pluck('INDEX_NAME')
                ->all()
            : [];
    }

    /**
     * Boolean-mode query: every token becomes a required `+token`. Returns
     * null when nothing survives the minimum word length, letting the caller
     * fall back to `like` instead of returning an empty page.
     */
    private function prepareFullTextTerm(string $term): ?string
    {
        $tokens = preg_split('/[\s,]+/u', $term, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $tokens = array_values(array_filter(
            array_map(fn (string $token) => trim($token, " \t\n\r\0\x0B,.;:!?()[]{}\"'"), $tokens),
            fn (string $token) => mb_strlen($token) >= self::FULLTEXT_MIN_WORD_LENGTH
        ));

        return $tokens === [] ? null : '+'.implode(' +', $tokens);
    }
}
