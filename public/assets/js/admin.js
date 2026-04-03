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
