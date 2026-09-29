/** Keep in sync with resources/js/labels.js */
var typeLabels = {
  fault: 'Fault',
  provisioning: 'Provisioning',
  number_port: 'Number port',
};
var statusLabels = {
  open: 'Open',
  in_progress: 'In progress',
  waiting: 'Waiting',
  resolved: 'Resolved',
};
function label(map, code) {
  return (code && map[code]) || code || '—';
}
function priorityLabel(code) {
  if (!code) return '—';
  return code.charAt(0).toUpperCase() + code.slice(1);
}

function scrollToPageTop() {
  window.LegacyScroll.scrollToPageTop('page-heading');
}

/** Keep in sync with resources/js/api.js DESK_PAGE_SIZE */
var PER_PAGE = 100;
var currentPage = 1;
var lastPage = 1;

var tableSort;

function escapeHtml(text) {
  return $('<div/>').text(text == null ? '' : text).html();
}

function loadReport(page, scrollToTop) {
  if (typeof page === 'number') {
    currentPage = page;
  }
  if (scrollToTop) {
    scrollToPageTop();
  }
  $('#error').prop('hidden', true);
  $('#rows').empty();
  $('#meta').text('Loading…');

  $.getJSON('/legacy/ticket_summary.php', { page: currentPage, per_page: PER_PAGE })
    .done(function (payload) {
      var rows = payload.open_queue || [];
      currentPage = payload.current_page || currentPage;
      lastPage = payload.last_page || 1;
      var total = payload.total != null ? payload.total : rows.length;
      $('#meta').text(
        'Page ' + currentPage + ' / ' + lastPage +
        ' · ' + total + ' tickets' +
        ' · showing ' + rows.length
      );
      $('#page-prev').prop('disabled', currentPage <= 1);
      $('#page-next').prop('disabled', currentPage >= lastPage);
      var $body = $('#rows');
      rows.forEach(function (row) {
        $body.append(
          $('<tr/>').append(
            $('<td class="legacy-col-ref"/>').html('<span class="legacy-ref">' + escapeHtml(row.reference) + '</span>'),
            $('<td class="legacy-col-account legacy-hide-md"/>').text(row.account_name || '—'),
            $('<td class="legacy-col-type legacy-hide-lg"/>').text(label(typeLabels, row.type)),
            $('<td class="legacy-col-subject"/>').text(row.subject || '—'),
            $('<td class="legacy-col-priority legacy-hide-sm"/>').text(priorityLabel(row.priority)),
            $('<td class="legacy-col-status"/>').text(label(statusLabels, row.status))
          )
        );
      });
      if (!tableSort) {
        tableSort = LegacyTableSort.bindClientTable('#report-table', { scrollHeadingId: 'page-heading' });
      } else {
        tableSort.refresh();
      }
      if (scrollToTop) {
        requestAnimationFrame(scrollToPageTop);
      }
    })
    .fail(function () {
      $('#error').text('Could not load report from ticket_summary.php.').prop('hidden', false);
      $('#meta').text('');
    });
}

LegacyScroll.bindSectionScroll(document.getElementById('report-controls'), 'page-heading');
$('#reload').on('click', function () { loadReport(currentPage, true); });
$('#page-prev').on('click', function () {
  if (currentPage > 1) {
    loadReport(currentPage - 1, true);
  }
});
$('#page-next').on('click', function () {
  if (currentPage < lastPage) {
    loadReport(currentPage + 1, true);
  }
});
loadReport(1, true);
