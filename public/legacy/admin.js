/** Keep in sync with resources/js/labels.js */
const typeLabels = {
  fault: 'Fault',
  provisioning: 'Provisioning',
  number_port: 'Number port',
};
const statusLabels = {
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
function ticketUrl(id) {
  return '/tickets/' + id;
}
function cellText(text, extraClass) {
  const $td = $('<td/>').text(text);
  if (extraClass) {
    $td.addClass(extraClass);
  }
  return $td;
}

function scrollToPageTop() {
  window.LegacyScroll.scrollToPageTop('page-heading');
}

/** Keep in sync with resources/js/api.js DESK_PAGE_SIZE */
const PER_PAGE = 100;
let currentPage = 1;
let lastPage = 1;
const SORT_FIRST_DIR = {
  created_at: 'desc',
  reference: 'asc',
  account: 'asc',
  type: 'asc',
  subject: 'asc',
  priority: 'asc',
  status: 'asc',
};
const sortState = {
  by: 'created_at',
  dir: 'desc',
  firstDir: SORT_FIRST_DIR,
};

function loadQueue(page, scrollToTop) {
  if (typeof page === 'number') {
    currentPage = page;
  }
  if (scrollToTop) {
    scrollToPageTop();
  }
  $('#error').prop('hidden', true);
  $('#rows').empty();
  $('#stats').text('Loading…');
  const params = { per_page: PER_PAGE, page: currentPage };
  const status = $('#filter-status').val();
  const type = $('#filter-type').val();
  const q = $('#filter-q').val();
  if (status) params.status = status;
  if (type) params.type = type;
  if (q) params.q = q;
  params.sort = sortState.by;
  params.direction = sortState.dir;
  $.getJSON('/api/v1/tickets', params)
    .done(function (payload) {
      const pageMeta = payload.data;
      const rows = pageMeta.data || [];
      currentPage = pageMeta.current_page || currentPage;
      lastPage = pageMeta.last_page || 1;
      const $body = $('#rows').empty();
      rows.forEach(function (t) {
        const pri = t.priority === 'urgent' ? 'urgent' : '';
        const href = ticketUrl(t.id);
        const $ref = $('<td class="legacy-col-ref"/>').append(
          $('<a class="legacy-link legacy-ref"/>')
            .attr('href', href)
            .attr('target', '_blank')
            .attr('rel', 'noopener noreferrer')
            .text(t.reference)
        );
        const $subject = $('<td class="legacy-col-subject"/>').append(
          $('<a class="legacy-subject-link"/>')
            .attr('href', href)
            .attr('target', '_blank')
            .attr('rel', 'noopener noreferrer')
            .text(t.subject)
        );
        const $open = $('<td class="legacy-col-open"/>').append(
          $('<a class="legacy-link"/>')
            .attr('href', href)
            .attr('target', '_blank')
            .attr('rel', 'noopener noreferrer')
            .text('Open')
        );
        $body.append(
          $('<tr/>').append(
            $ref,
            cellText(t.account ? t.account.name : '—', 'legacy-col-account legacy-hide-md'),
            cellText(label(typeLabels, t.type), 'legacy-col-type legacy-hide-lg'),
            $subject,
            cellText(priorityLabel(t.priority), pri ? 'legacy-col-priority legacy-hide-sm legacy-urgent' : 'legacy-col-priority legacy-hide-sm'),
            cellText(label(statusLabels, t.status), 'legacy-col-status'),
            $open
          )
        );
      });
      $('#stats').text(
        'Page ' + currentPage + ' / ' + lastPage +
        ' · ' + (pageMeta.total || rows.length) + ' tickets' +
        ' · showing ' + rows.length
      );
      $('#page-prev').prop('disabled', currentPage <= 1);
      $('#page-next').prop('disabled', currentPage >= lastPage);
      LegacyTableSort.updateServerHeaders('#ticket-queue table', sortState);
      if (scrollToTop) {
        requestAnimationFrame(scrollToPageTop);
      }
    })
    .fail(function (xhr) {
      $('#error').text('Could not load queue: ' + (xhr.responseJSON?.message || xhr.statusText)).prop('hidden', false);
    });
}

function applyFilters() {
  currentPage = 1;
  loadQueue(1, false);
}

function resetFilters() {
  $('#filter-status').val('');
  $('#filter-type').val('');
  $('#filter-q').val('');
  currentPage = 1;
  loadQueue(1, false);
}

LegacyScroll.bindSectionScroll(document.getElementById('queue-controls'), 'page-heading');
LegacyScroll.bindSectionScroll(document.querySelector('.legacy-pager'), 'page-heading');

LegacyTableSort.bindServerSort('#ticket-queue table', sortState, function () {
  currentPage = 1;
  loadQueue(1, true);
});
LegacyTableSort.updateServerHeaders('#ticket-queue table', sortState);

$('#reload').on('click', function () { loadQueue(currentPage, false); });
$('#reset-filters').on('click', resetFilters);
$('#filter-status, #filter-type').on('change', applyFilters);
$('#filter-q').on('keyup', function (e) { if (e.key === 'Enter') applyFilters(); });
$('#page-prev').on('click', function () {
  if (currentPage > 1) loadQueue(currentPage - 1, true);
});
$('#page-next').on('click', function () {
  if (currentPage < lastPage) loadQueue(currentPage + 1, true);
});
loadQueue(1, true);
