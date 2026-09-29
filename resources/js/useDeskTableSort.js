import { reactive } from 'vue';

/** @param {Record<string, 'asc'|'desc'>} [initialDirectionByColumn] */
export function useDeskTableSort(defaultColumn = 'created_at', initialDirectionByColumn = {}) {
    const sort = reactive({
        by: defaultColumn,
        direction: initialDirectionByColumn[defaultColumn] ?? 'desc',
    });

    function toggle(column) {
        if (sort.by === column) {
            sort.direction = sort.direction === 'asc' ? 'desc' : 'asc';
        } else {
            sort.by = column;
            sort.direction = initialDirectionByColumn[column] ?? 'asc';
        }
    }

    function sortParams() {
        return { sort: sort.by, direction: sort.direction };
    }

    return { sort, toggle, sortParams };
}

/**
 * Client-side sort for small in-memory rows (e.g. quote line items).
 *
 * @template T
 * @param {import('vue').Ref<T[]>|T[]} rows
 * @param {import('vue').Reactive<{ by: string, direction: 'asc'|'desc' }>} sortState
 * @param {Record<string, (row: T) => string|number|null|undefined>} accessors
 */
export function sortRows(rows, sortState, accessors) {
    const list = Array.isArray(rows) ? rows : rows.value ?? [];
    const pick = accessors[sortState.by];
    if (!pick) {
        return list;
    }
    const dir = sortState.direction === 'asc' ? 1 : -1;
    return [...list].sort((a, b) => {
        const av = pick(a);
        const bv = pick(b);
        if (av == null && bv == null) {
            return 0;
        }
        if (av == null) {
            return 1;
        }
        if (bv == null) {
            return -1;
        }
        if (typeof av === 'number' && typeof bv === 'number') {
            return (av - bv) * dir;
        }
        return String(av).localeCompare(String(bv), undefined, { sensitivity: 'base' }) * dir;
    });
}
