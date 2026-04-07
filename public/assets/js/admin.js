/**
 * admin.js — Scripts partagés BackOffice
 * Utilisé par toutes les pages admin (listes, modals, pagination)
 */

// ══════════════════════════════════════
// 1. MODALS
// ══════════════════════════════════════

function openModal(id) {
  document.getElementById(id).classList.add('open')
}

function closeModal(id) {
  document.getElementById(id).classList.remove('open')
}

// ══════════════════════════════════════
// 4. TRI DES COLONNES
// Activé sur <table data-sortable="true">
// Chaque <th data-sort="string|number|date"> devient triable.
// ══════════════════════════════════════

function parseDateFr(str) {
  var p = str.trim().split('/')
  if (p.length === 3) return new Date(p[2], p[1] - 1, p[0])
  return new Date(0)
}

function sortTableByCol(tbody, colIndex, type, dir) {
  var rows = Array.from(tbody.querySelectorAll('tr[data-index]'))
  rows.sort(function (a, b) {
    var aCell = a.cells[colIndex]
    var bCell = b.cells[colIndex]
    var aVal = aCell ? aCell.textContent.trim() : ''
    var bVal = bCell ? bCell.textContent.trim() : ''
    var cmp = 0
    if (type === 'number') {
      cmp = (parseFloat(aVal) || 0) - (parseFloat(bVal) || 0)
    } else if (type === 'date') {
      cmp = parseDateFr(aVal) - parseDateFr(bVal)
    } else {
      cmp = aVal.localeCompare(bVal, 'fr', { sensitivity: 'base' })
    }
    return dir === 'asc' ? cmp : -cmp
  })
  rows.forEach(function (r) { tbody.appendChild(r) })
}

function initSorting(table) {
  if (!table || table.dataset.sortable !== 'true') return
  var tbody = table.querySelector('tbody')
  if (!tbody) return

  table.querySelectorAll('th[data-sort]').forEach(function (th) {
    var icon = document.createElement('i')
    icon.className = 'mdi mdi-unfold-more-horizontal sort-icon'
    th.appendChild(icon)
    th.dataset.sortDir = 'none'

    th.addEventListener('click', function () {
      var dir = th.dataset.sortDir === 'asc' ? 'desc' : 'asc'

      // Reset tous les autres th
      table.querySelectorAll('th[data-sort]').forEach(function (other) {
        other.dataset.sortDir = 'none'
        other.classList.remove('th-sorted')
        var ico = other.querySelector('.sort-icon')
        if (ico) ico.className = 'mdi mdi-unfold-more-horizontal sort-icon'
      })

      th.dataset.sortDir = dir
      th.classList.add('th-sorted')
      var ico = th.querySelector('.sort-icon')
      if (ico) ico.className = 'mdi mdi-arrow-' + (dir === 'asc' ? 'up' : 'down') + ' sort-icon'

      sortTableByCol(tbody, th.cellIndex, th.dataset.sort, dir)
    })
  })
}

// ══════════════════════════════════════
// 5. SUPPRESSION EN MASSE
// Activé sur <table data-bulk-delete="true"
//   data-bulk-delete-url="/admin/.../delete-bulk"
//   data-bulk-csrf-token="...">
// Chaque <tr data-index="N" data-id="X"> reçoit une checkbox.
// ══════════════════════════════════════

function initBulkDelete(table) {
  if (!table || table.dataset.bulkDelete !== 'true') return
  var deleteUrl = table.dataset.bulkDeleteUrl
  var csrfToken = table.dataset.bulkCsrfToken
  var tbody = table.querySelector('tbody')
  var theadRow = table.querySelector('thead tr')
  if (!tbody || !theadRow || !deleteUrl) return

  // --- Checkbox "tout sélectionner" dans le thead ---
  var thCheck = document.createElement('th')
  thCheck.style.cssText = 'width:40px;padding-right:0;'
  var checkAll = document.createElement('input')
  checkAll.type = 'checkbox'
  checkAll.className = 'bulk-checkbox'
  checkAll.title = 'Tout sélectionner'
  thCheck.appendChild(checkAll)
  theadRow.insertBefore(thCheck, theadRow.firstChild)

  // --- Checkbox par ligne de données ---
  tbody.querySelectorAll('tr[data-index]').forEach(function (tr) {
    var td = document.createElement('td')
    td.style.cssText = 'width:40px;padding-right:0;'
    var cb = document.createElement('input')
    cb.type = 'checkbox'
    cb.className = 'bulk-row-check bulk-checkbox'
    cb.dataset.id = tr.dataset.id || ''
    td.appendChild(cb)
    tr.insertBefore(td, tr.firstChild)
  })

  // --- Ajuster colspan des séparateurs ---
  tbody.querySelectorAll('tr[data-separator]').forEach(function (tr) {
    var td = tr.querySelector('td[colspan]')
    if (td) td.setAttribute('colspan', parseInt(td.getAttribute('colspan')) + 1)
  })

  // --- Barre d'actions flottante ---
  var bar = document.createElement('div')
  bar.className = 'bulk-action-bar'
  bar.innerHTML =
    '<span class="bulk-count"></span>' +
    '<button type="button" class="bulk-clear-btn">Désélectionner tout</button>' +
    '<button type="button" class="btn-danger bulk-delete-submit-btn">' +
      '<i class="mdi mdi-trash-can"></i> Supprimer la sélection' +
    '</button>'
  document.body.appendChild(bar)

  var countEl = bar.querySelector('.bulk-count')
  var clearBtn = bar.querySelector('.bulk-clear-btn')
  var deleteBtn = bar.querySelector('.bulk-delete-submit-btn')

  // --- Modal de confirmation générique ---
  var modal = document.createElement('div')
  modal.className = 'modal-overlay'
  modal.innerHTML =
    '<div class="modal-box">' +
      '<div class="modal-icon"><i class="mdi mdi-trash-can-outline"></i></div>' +
      '<div class="modal-title bulk-modal-title"></div>' +
      '<div class="modal-text">Cette action est irréversible.</div>' +
      '<div class="modal-actions">' +
        '<button type="button" class="btn-ghost bulk-modal-cancel">Annuler</button>' +
        '<button type="button" class="btn-danger bulk-modal-confirm">' +
          '<i class="mdi mdi-trash-can"></i> Supprimer' +
        '</button>' +
      '</div>' +
    '</div>'
  document.body.appendChild(modal)

  var modalTitle = modal.querySelector('.bulk-modal-title')
  var modalCancel = modal.querySelector('.bulk-modal-cancel')
  var modalConfirm = modal.querySelector('.bulk-modal-confirm')

  function getChecked() {
    return Array.from(table.querySelectorAll('.bulk-row-check:checked'))
  }

  function updateBar() {
    var checked = getChecked()
    var n = checked.length
    if (n > 0) {
      countEl.textContent = n + ' élément' + (n > 1 ? 's' : '') + ' sélectionné' + (n > 1 ? 's' : '')
      bar.classList.add('visible')
    } else {
      bar.classList.remove('visible')
    }
    // Sync checkAll
    var allVisible = Array.from(table.querySelectorAll('tr[data-index]:not(.page-hidden) .bulk-row-check'))
    checkAll.indeterminate = n > 0 && n < allVisible.length
    checkAll.checked = n > 0 && n === allVisible.length
  }

  // Écouter les changements de checkbox
  tbody.addEventListener('change', function (e) {
    if (e.target.classList.contains('bulk-row-check')) updateBar()
  })

  checkAll.addEventListener('change', function () {
    var visible = table.querySelectorAll('tr[data-index]:not(.page-hidden) .bulk-row-check')
    visible.forEach(function (cb) { cb.checked = checkAll.checked })
    updateBar()
  })

  clearBtn.addEventListener('click', function () {
    table.querySelectorAll('.bulk-row-check').forEach(function (cb) { cb.checked = false })
    checkAll.checked = false
    bar.classList.remove('visible')
  })

  deleteBtn.addEventListener('click', function () {
    var n = getChecked().length
    modalTitle.textContent = 'Supprimer ' + n + ' élément' + (n > 1 ? 's' : '') + ' ?'
    modal.classList.add('open')
  })

  modalCancel.addEventListener('click', function () { modal.classList.remove('open') })
  modal.addEventListener('click', function (e) { if (e.target === modal) modal.classList.remove('open') })

  modalConfirm.addEventListener('click', function () {
    modal.classList.remove('open')
    var ids = getChecked().map(function (cb) { return cb.dataset.id })

    var form = document.createElement('form')
    form.method = 'POST'
    form.action = deleteUrl
    form.style.display = 'none'

    var tokenInput = document.createElement('input')
    tokenInput.type = 'hidden'
    tokenInput.name = '_token'
    tokenInput.value = csrfToken
    form.appendChild(tokenInput)

    ids.forEach(function (id) {
      var input = document.createElement('input')
      input.type = 'hidden'
      input.name = 'ids[]'
      input.value = id
      form.appendChild(input)
    })

    document.body.appendChild(form)
    form.submit()
  })
}

document.addEventListener('DOMContentLoaded', () => {
  // Fermeture des modals en cliquant sur l'overlay
  document.querySelectorAll('.modal-overlay').forEach((overlay) => {
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) overlay.classList.remove('open')
    })
  })

  // ══════════════════════════════════════
  // 2. FLASH MESSAGES — fermeture auto
  // ══════════════════════════════════════

  document.querySelectorAll('.flash-close').forEach((btn) => {
    btn.addEventListener('click', () => btn.parentElement.remove())
  })

  // Init bulk delete AVANT sorting (le bulk ajoute une colonne qui décale les cellIndex)
  var bulkTables = document.querySelectorAll('table[data-bulk-delete]')
  bulkTables.forEach(function (table) { initBulkDelete(table) })

  var sortableTables = document.querySelectorAll('table[data-sortable]')
  sortableTables.forEach(function (table) { initSorting(table) })

  // ══════════════════════════════════════
  // 3. PAGINATION & RECHERCHE
  // Utilisée sur les pages de liste.
  // Nécessite dans le HTML :
  //   - un <tbody id="[prefix]-tbody"> avec des <tr data-index="N">
  //   - un <select id="per-page-select">
  //   - un <input id="search-input">
  //   - un <div id="pagination-info">
  //   - un <div id="pagination-controls">
  // ══════════════════════════════════════

  const tbody      = document.querySelector('tbody[id$="-tbody"]')
  const infoEl     = document.getElementById('pagination-info')
  const ctrlEl     = document.getElementById('pagination-controls')
  const perPageSel = document.getElementById('per-page-select')
  const searchEl   = document.getElementById('search-input')

  if (!tbody || !infoEl || !ctrlEl || !perPageSel || !searchEl) return

  let page  = 1
  let perPg = parseInt(perPageSel.value)
  let query = ''

  function allRows() {
    return [...tbody.querySelectorAll('tr[data-index]')]
  }

  function separators() {
    return [...tbody.querySelectorAll('tr[data-separator]')]
  }

  function filteredRows() {
    return allRows().filter((r) => query === '' || r.textContent.toLowerCase().includes(query))
  }

  function mkBtn(content, onClick, disabled = false, active = false, isDots = false) {
    if (isDots) {
      const s = document.createElement('span')
      s.className = 'page-dots'
      s.textContent = '…'
      return s
    }
    const b = document.createElement('button')
    b.className = 'page-btn' + (active ? ' active' : '')
    b.innerHTML = content
    if (disabled) b.setAttribute('disabled', true)
    else b.addEventListener('click', onClick)
    return b
  }

  function render() {
    const rows  = filteredRows()
    const total = rows.length
    const pages = Math.max(1, Math.ceil(total / perPg))
    if (page > pages) page = pages

    const start = (page - 1) * perPg
    const end   = start + perPg

    // Masquer toutes les lignes de données
    allRows().forEach((r) => r.classList.add('page-hidden'))

    // Afficher les lignes de la page courante
    const visibleRows = rows.slice(start, end)
    visibleRows.forEach((r) => r.classList.remove('page-hidden'))

    // Gérer les séparateurs de mois (si présents)
    separators().forEach((sep) => {
      const month = sep.nextElementSibling ? sep.nextElementSibling.dataset.month : null
      const hasVisible = visibleRows.some((r) => r.dataset.month === month)
      sep.style.display = (hasVisible && query === '') ? '' : 'none'
    })

    // Info pagination
    const from = total ? start + 1 : 0
    const to   = Math.min(end, total)
    const noun = tbody.dataset.noun ?? 'élément'
    const nounPlural = tbody.dataset.nounPlural ?? (noun + 's')
    infoEl.innerHTML = `Affichage de <strong>${from}–${to}</strong> sur <strong>${total}</strong> ${total > 1 ? nounPlural : noun}`

    // Contrôles pagination
    ctrlEl.innerHTML = ''
    ctrlEl.appendChild(mkBtn('<i class="mdi mdi-chevron-left"></i>', () => { page--; render() }, page === 1))

    const delta = 2
    let last = null
    for (let p = 1; p <= pages; p++) {
      const show = p === 1 || p === pages || (p >= page - delta && p <= page + delta)
      if (!show) {
        if (last !== null && last !== -1) { ctrlEl.appendChild(mkBtn('', null, false, false, true)); last = -1 }
        continue
      }
      ctrlEl.appendChild(mkBtn(p, (function(pg) { return () => { page = pg; render() } })(p), false, p === page))
      last = p
    }

    ctrlEl.appendChild(mkBtn('<i class="mdi mdi-chevron-right"></i>', () => { page++; render() }, page === pages))
  }

  searchEl.addEventListener('input',    () => { query = searchEl.value.toLowerCase(); page = 1; render() })
  perPageSel.addEventListener('change', () => { perPg = parseInt(perPageSel.value); page = 1; render() })

  render()
})
