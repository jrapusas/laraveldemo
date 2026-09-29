/**
 * Client-side table sort for legacy pages (th[data-sort-col], tbody rows).
 */
(function (global) {
  function colIndex(th) {
    return Number(th.getAttribute('data-sort-col'));
  }

  function headerForCol(table, col) {
    return table.querySelector('th[data-sort-col="' + col + '"]');
  }

  function isNumericColumn(table, col) {
    var th = headerForCol(table, col);
    return th != null && th.getAttribute('data-sort-type') === 'number';
  }

  function cellText(row, col) {
    var cell = row.cells[col];
    return cell ? String(cell.textContent || '').trim() : '';
  }

  function cellNumber(row, col) {
    var raw = cellText(row, col).replace(/[^0-9.-]+/g, '');
    if (raw === '' || raw === '-') {
      return null;
    }
    var n = parseFloat(raw);
    return Number.isFinite(n) ? n : null;
  }

  function bindTable(table, options) {
    var opts = options || {};
    var sortCol = opts.defaultCol != null ? opts.defaultCol : 0;
    var sortDir = opts.defaultDir === 'desc' ? 'desc' : 'asc';
    var scrollTargetId = opts.scrollHeadingId || opts.scrollTargetId || null;

    function compareRows(a, b) {
      if (isNumericColumn(table, sortCol)) {
        var av = cellNumber(a, sortCol);
        var bv = cellNumber(b, sortCol);
        if (av == null && bv == null) {
          return 0;
        }
        if (av == null) {
          return 1;
        }
        if (bv == null) {
          return -1;
        }
        return av - bv;
      }
      return cellText(a, sortCol).localeCompare(cellText(b, sortCol), undefined, {
        sensitivity: 'base',
        numeric: true,
      });
    }

    function sortBody() {
      var tbody = table.tBodies[0];
      if (!tbody) {
        return;
      }
      var rows = Array.prototype.slice.call(tbody.rows);
      rows.sort(function (ra, rb) {
        var cmp = compareRows(ra, rb);
        return sortDir === 'asc' ? cmp : -cmp;
      });
      rows.forEach(function (row) {
        tbody.appendChild(row);
      });
    }

    function updateHeaders() {
      table.querySelectorAll('th[data-sort-col]').forEach(function (th) {
        var col = colIndex(th);
        var active = col === sortCol;
        th.setAttribute('aria-sort', active ? (sortDir === 'asc' ? 'ascending' : 'descending') : 'none');
        var icon = th.querySelector('.legacy-sort-glyph');
        if (icon) {
          icon.textContent = '';
        }
      });
    }

    function onHeaderClick(th) {
      var col = colIndex(th);
      if (Number.isNaN(col)) {
        return;
      }
      if (sortCol === col) {
        sortDir = sortDir === 'asc' ? 'desc' : 'asc';
      } else {
        sortCol = col;
        sortDir = 'asc';
      }
      updateHeaders();
      sortBody();
      if (scrollTargetId && global.LegacyScroll && global.LegacyScroll.scrollToPageTop) {
        global.LegacyScroll.scrollToPageTop(scrollTargetId);
      }
    }

    function onClick(e) {
      var th = e.target.closest('th[data-sort-col]');
      if (!th || !table.contains(th)) {
        return;
      }
      if (e.target.closest('.legacy-sort-btn') || !th.querySelector('.legacy-sort-btn')) {
        e.preventDefault();
        onHeaderClick(th);
      }
    }

    table.addEventListener('click', onClick);

    updateHeaders();

    return {
      refresh: sortBody,
      reset: function () {
        sortCol = opts.defaultCol != null ? opts.defaultCol : 0;
        sortDir = opts.defaultDir === 'desc' ? 'desc' : 'asc';
        updateHeaders();
        sortBody();
      },
      destroy: function () {
        table.removeEventListener('click', onClick);
      },
    };
  }

  function bindSelector(selector, options) {
    var table = typeof selector === 'string' ? document.querySelector(selector) : selector;
    if (!table) {
      return null;
    }
    return bindTable(table, options);
  }

  function serverSortKey(th) {
    return th.getAttribute('data-sort');
  }

  function updateServerHeaders(selector, sortState) {
    var table = typeof selector === 'string' ? document.querySelector(selector) : selector;
    if (!table) {
      return;
    }
    table.querySelectorAll('th[data-sort]').forEach(function (th) {
      var key = serverSortKey(th);
      var active = key === sortState.by;
      th.setAttribute(
        'aria-sort',
        active ? (sortState.dir === 'asc' ? 'ascending' : 'descending') : 'none'
      );
      var icon = th.querySelector('.legacy-sort-glyph');
      if (icon) {
        icon.textContent = '';
      }
    });
  }

  function bindServerSort(selector, sortState, onChange) {
    var table = typeof selector === 'string' ? document.querySelector(selector) : selector;
    if (!table) {
      return;
    }
    table.addEventListener('click', function (e) {
      var th = e.target.closest('th[data-sort]');
      if (!th || !table.contains(th)) {
        return;
      }
      if (th.querySelector('.legacy-sort-btn') && !e.target.closest('.legacy-sort-btn')) {
        return;
      }
      e.preventDefault();
      var key = serverSortKey(th);
      if (!key) {
        return;
      }
      if (sortState.by === key) {
        sortState.dir = sortState.dir === 'asc' ? 'desc' : 'asc';
      } else {
        sortState.by = key;
        var first = sortState.firstDir && sortState.firstDir[key];
        sortState.dir = first === 'desc' ? 'desc' : 'asc';
      }
      updateServerHeaders(table, sortState);
      onChange();
    });
    updateServerHeaders(table, sortState);
  }

  global.LegacyTableSort = {
    bind: bindSelector,
    bindClientTable: bindSelector,
    bindServerSort: bindServerSort,
    updateServerHeaders: updateServerHeaders,
  };
})(window);
