// script.js
const HOP_API = 'api_hopdong.php';
const CT_API = 'api_chitiet.php';

let sortBy = 'ma_hd';
let sortDir = 'DESC';
let filters = {};
let globalQ = '';
let onlyChuaDong = false;
let usingTrash = false;
let currentMaHd = null;

document.addEventListener('DOMContentLoaded', () => {
  initUI();
  loadTable();
});

function initUI() {
  // Global Search
  const globalSearch = document.getElementById('globalSearch');
  if (globalSearch) {
    globalSearch.addEventListener('input', e => {
      globalQ = e.target.value.trim();
      debounce(loadTable, 300)();
    });
  }

  // Trash Toggle
  const btnTrash = document.getElementById('trash');
  if (btnTrash) {
    btnTrash.addEventListener('click', () => {
      usingTrash = !usingTrash;
      btnTrash.classList.toggle('active', usingTrash);
      btnTrash.textContent = usingTrash ? 'Quay lại' : 'Thùng rác';
      loadTable();
    });
  }

  // Refresh
  document.getElementById('btnRefresh')?.addEventListener('click', () => {
    globalQ = '';
    if (globalSearch) globalSearch.value = '';
    document.querySelectorAll('.col-filter').forEach(i => i.value = '');
    filters = {};
    onlyChuaDong = false;
    document.getElementById('btnChuadong')?.classList.remove('active');
    loadTable();
  });

  // "Chưa đóng" Toggle
  document.getElementById('btnChuadong')?.addEventListener('click', e => {
    onlyChuaDong = !onlyChuaDong;
    e.target.classList.toggle('active', onlyChuaDong);
    loadTable();
  });

  // Status Filter
  document.getElementById('statusFilter')?.addEventListener('change', e => {
    const val = e.target.value;
    if (val) filters['trang_thai'] = val;
    else delete filters['trang_thai'];
    loadTable();
  });

  // Column Filters
  document.querySelectorAll('.col-filter').forEach(inp => {
    inp.addEventListener('input', () => {
      const f = inp.dataset.filter;
      if (inp.value) filters[f] = inp.value;
      else delete filters[f];
      debounce(loadTable, 300)();
    });
  });

  // Modals
  document.getElementById('btnAdd')?.addEventListener('click', () => toggleAdd(true));
  document.getElementById('btnCancelAdd')?.addEventListener('click', () => toggleAdd(false));
  document.getElementById('formAdd')?.addEventListener('submit', onAddSubmit);

  // Auto-format currency on Add Form
  const moneyInputs = ['tien_vay', 'tien_theo_ky', 'dong_lai'];
  moneyInputs.forEach(name => {
    const inp = document.querySelector(`#formAdd [name="${name}"]`);
    if (inp) {
      inp.addEventListener('input', () => { inp.value = formatVNDInput(inp.value); });
    }
  });

  // Close Detail
  document.querySelector('.closeChiTiet')?.addEventListener('click', () => {
    toggleChiTiet(false);
    loadTable();
  });

  document.getElementById('ctAdd')?.addEventListener('click', onAddChiTiet);
  document.getElementById('ctFilterMonth')?.addEventListener('input', () => loadChiTiet(currentMaHd));

  // Highlight Tools
  document.getElementById('btnHighlight')?.addEventListener('click', () => {
    const color = document.getElementById('highlightColor')?.value || '#ffff00';
    applyHighlight(color);
  });
  document.getElementById('btnRemoveHighlight')?.addEventListener('click', removeHighlight);

  // Sorting
  document.querySelectorAll('.header-row th[data-field]').forEach(th => {
    th.addEventListener('click', () => {
      const f = th.dataset.field;
      if (sortBy === f) sortDir = (sortDir === 'ASC') ? 'DESC' : 'ASC';
      else { sortBy = f; sortDir = 'ASC'; }

      document.querySelectorAll('.header-row th').forEach(h => h.classList.remove('sort-asc', 'sort-desc'));
      th.classList.add(sortDir === 'ASC' ? 'sort-asc' : 'sort-desc');
      loadTable();
    });
  });

  // Export
  document.getElementById('btnExportExcel')?.addEventListener('click', () => {
    const params = new URLSearchParams({
      trang_thai: document.getElementById('statusFilter')?.value || '',
      start_date: document.getElementById('startDate')?.value || '',
      end_date: document.getElementById('endDate')?.value || ''
    });
    window.location.href = 'export_excel_chitiet.php?' + params.toString();
  });

  // Added event listeners for date range search functionality
  document.getElementById('startDate')?.addEventListener('change', () => {
    loadTableWithDateRange();
  });
  document.getElementById('endDate')?.addEventListener('change', () => {
    loadTableWithDateRange();
  });

  function loadTableWithDateRange() {
    const startDate = document.getElementById('startDate')?.value;
    const endDate = document.getElementById('endDate')?.value;

    const params = new URLSearchParams();
    params.set('action', 'list');
    params.set('trash', usingTrash ? '1' : '0');
    if (sortBy) params.set('sort_by', sortBy);
    if (sortDir) params.set('sort_dir', sortDir);
    if (globalQ) params.set('q', globalQ);
    if (onlyChuaDong) params.set('chua_dong', '1');
    if (Object.keys(filters).length) params.set('filters', JSON.stringify(filters));
    if (startDate) params.set('start_date', startDate);
    if (endDate) params.set('end_date', endDate);

    fetch(`${HOP_API}?${params.toString()}`)
        .then(res => res.json())
        .then(json => renderTable(json.data || []))
        .catch(e => console.error(e));
  }
}

async function loadTable() {
  const params = new URLSearchParams();
  params.set('action', 'list');
  params.set('trash', usingTrash ? '1' : '0');
  if (sortBy) params.set('sort_by', sortBy);
  if (sortDir) params.set('sort_dir', sortDir);
  if (globalQ) params.set('q', globalQ);
  if (onlyChuaDong) params.set('chua_dong', '1');
  if (Object.keys(filters).length) params.set('filters', JSON.stringify(filters));

  try {
    const res = await fetch(`${HOP_API}?${params.toString()}`);
    const json = await res.json();
    renderTable(json.data || []);
  } catch (e) { console.error(e); }
}

function renderTable(rows) {
  const tbody = document.querySelector('#dataTable tbody');
  if (!tbody) return;
  tbody.innerHTML = '';

  rows.forEach((r, i) => {
    const tr = document.createElement('tr');
    tr.dataset.ma = r.ma_hd;

    // Highlight row logic for dates
    let colorStyle = '';
    if (r.ngay_vay) {
      const d = new Date(r.ngay_vay).getDate();
      const today = new Date().getDate();
      if (d === today) colorStyle = 'color:red; font-weight:bold;';
      else if (d === (today + 1)) colorStyle = 'color:orange; font-weight:bold;';
    }

    tr.innerHTML = `
      <td>${i + 1}</td>
      <td class="editable" data-field="ngay_vay" style="${colorStyle}">${r.ngay_vay || ''}</td>
      <td>${r.so_ngay || ''}</td>
      <td class="editable" data-field="khach_hang">${escapeHtml(r.khach_hang)}</td>
      <td class="editable" data-field="sdt_kh">${r.sdt_kh || ''}</td>
      <td class="editable money" data-field="tien_vay" data-value="${r.tien_vay}">${formatVND(r.tien_vay)}</td>
      <td class="editable money" data-field="tien_theo_ky" data-value="${r.tien_theo_ky}">${formatVND(r.tien_theo_ky)}</td>
      <td class="editable secret money" data-field="tong_ghi" data-value="${r.tong_ghi}">${formatVND(r.tong_ghi)}</td>
      <td class="editable money" data-field="dong_lai" data-value="${r.dong_lai}">${formatVND(r.dong_lai)}</td>
      <td class="editable" data-field="gia_han">${r.gia_han || ''}</td>
      <td class="editable money" data-field="tat_toan" data-value="${r.tat_toan}">${formatVND(r.tat_toan)}</td>
      <td class="editable" data-field="ghi_chu">${escapeHtml(r.ghi_chu)}</td>
      <td class="editable" data-field="nguoi_gioi_thieu">${escapeHtml(r.nguoi_gioi_thieu)}</td>
      <td class="editable" data-field="sdt_gt">${r.sdt_gt || ''}</td>
      <td class="editable" data-field="trang_thai">${renderTrangThai(r.trang_thai)}</td>
      <td class="col-actions">
        ${usingTrash
        ? `<button class="btn-restore" onclick="onRestore(${r.ma_hd})">Thu</button>`
        : `<button class="btn-delete" onclick="onDelete(${r.ma_hd})">Xóa</button>`}
        <button class="btn-detail" onclick="openChiTiet(${r.ma_hd})">Chi tiết</button>
      </td>
    `;

    // Secret field logic
    tr.querySelector('.secret')?.addEventListener('dblclick', function () {
      this.classList.remove('secret');
    });

    // Editable cells
    tr.querySelectorAll('.editable').forEach(td => {
      td.addEventListener('dblclick', () => onCellDblClick(td));
    });

    tbody.appendChild(tr);
  });
}

function onCellDblClick(td) {
  const field = td.dataset.field;
  const tr = td.closest('tr');
  const isCompleted = tr.querySelector('[data-field="trang_thai"]')?.textContent.includes('Hoàn tất');

  // Không cho sửa Tổng ghi (tính toán tự động) hoặc khi trạng thái đã Hoàn tất (trừ trường Trạng thái)
  if (field === 'tong_ghi' || (isCompleted && field !== 'trang_thai')) return;

  if (field === 'trang_thai') {
    const current = td.textContent.trim();
    const select = document.createElement('select');
    select.innerHTML = `
      <option value="dang_vay">Đang vay</option>
      <option value="da_hoan_tat">Đã hoàn tất</option>
      <option value="no_xau">Nợ xấu</option>
    `;
    select.value = current.includes('Hoàn tất') ? 'da_hoan_tat' : (current.includes('Nợ xấu') ? 'no_xau' : 'dang_vay');
    td.innerHTML = '';
    td.appendChild(select);
    select.focus();
    select.addEventListener('blur', () => updateField(td, select.value, true));
    select.addEventListener('change', () => updateField(td, select.value, true));
  } else if (td.classList.contains('money')) {
    const current = td.dataset.value || '0';
    const input = document.createElement('input');
    input.value = formatVND(current);
    td.innerHTML = '';
    td.appendChild(input);
    input.focus();
    input.addEventListener('input', () => { input.value = formatVNDInput(input.value); });
    input.addEventListener('blur', () => updateField(td, getRawNumber(input.value)));
    input.addEventListener('keydown', e => { if (e.key === 'Enter') input.blur(); });
  } else if (!['so_ngay'].includes(field)) {
    td.contentEditable = true;
    td.focus();
    td.addEventListener('blur', () => updateField(td, td.textContent.trim()), { once: true });
    td.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); td.blur(); } });
  }
}

async function updateField(td, value, isStatus = false) {
  if (td.isContentEditable) td.contentEditable = false;
  const field = td.dataset.field;
  const ma = td.closest('tr').dataset.ma;

  const fd = new FormData();
  fd.append('action', 'update');
  fd.append('ma_hd', ma);
  fd.append('field', field);
  fd.append('value', value);

  try {
    const res = await fetch(HOP_API, { method: 'POST', body: fd });
    const json = await res.json();
    if (json.error) showToast(json.error, 'error');

    if (isStatus) td.innerHTML = renderTrangThai(value);
    else if (td.classList.contains('money')) {
      td.dataset.value = value;
      td.textContent = formatVND(value);
    } else {
      td.textContent = value;
    }

    if (field === 'ngay_vay' || field === 'tat_toan') loadTable();
  } catch (e) { console.error(e); }
}

async function onDelete(ma) {
  if (!confirm('Xóa hợp đồng này?')) return;
  const fd = new FormData(); fd.append('action', 'delete'); fd.append('ma_hd', ma);
  await fetch(HOP_API, { method: 'POST', body: fd });
  loadTable();
}

async function onRestore(ma) {
  if (!confirm('Khôi phục hợp đồng này?')) return;
  const fd = new FormData(); fd.append('action', 'restore'); fd.append('ma_hd', ma);
  await fetch(HOP_API, { method: 'POST', body: fd });
  loadTable();
}

// --- Detail Modal ---
async function openChiTiet(ma) {
  currentMaHd = ma;
  toggleChiTiet(true);
  document.getElementById('ctMa').textContent = ma;

  // Load contract info
  const res = await fetch(`api_chitiet_hd.php?ma_hd=${ma}`);
  const data = await res.json();
  document.getElementById('ctHT').textContent = data.khach_hang || '';
  document.getElementById('ctSDT').textContent = data.sdt_kh || '';
  document.getElementById('ctTien').textContent = formatVND(data.tien_theo_ky || 0);

  loadChiTiet(ma);
}

async function loadChiTiet(ma) {
  const month = document.getElementById('ctFilterMonth').value;
  const params = new URLSearchParams({ action: 'list', ma_hd: ma });
  const res = await fetch(`${CT_API}?${params.toString()}`);
  const json = await res.json();
  let rows = json.data || [];
  if (month) rows = rows.filter(r => r.thang?.startsWith(month));
  renderChiTiet(rows);
}

function renderChiTiet(rows) {
  const tbody = document.querySelector('#ctTable tbody');
  if (!tbody) return;
  tbody.innerHTML = '';
  rows.forEach(r => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td class="ct-edit" data-field="thang" data-mact="${r.mact}">${r.thang}</td>
      <td class="ct-status" data-field="trang_thai" data-mact="${r.mact}">${renderCtTrangThai(r.trang_thai)}</td>
      <td class="ct-edit" data-field="ghi_chu" data-mact="${r.mact}">${escapeHtml(r.ghi_chu)}</td>
      <td><button onclick="onCtDelete(${r.mact})">Xóa</button></td>
    `;
    tr.querySelectorAll('.ct-edit, .ct-status').forEach(td => {
      td.addEventListener('dblclick', () => onCtDblClick(td));
    });
    tbody.appendChild(tr);
  });
}

function onCtDblClick(td) {
  const field = td.dataset.field;
  if (field === 'trang_thai') {
    const sel = document.createElement('select');
    sel.innerHTML = `<option value="chua_thu">Chưa thu</option><option value="da_thu">Đã thu</option><option value="qua_han">Quá hạn</option>`;
    sel.value = td.textContent.includes('Đã') ? 'da_thu' : (td.textContent.includes('Quá') ? 'qua_han' : 'chua_thu');
    td.innerHTML = ''; td.appendChild(sel); sel.focus();
    sel.onblur = () => updateCtField(td, sel.value, true);
  } else {
    td.contentEditable = true; td.focus();
    td.onblur = () => updateCtField(td, td.textContent.trim());
  }
}

async function updateCtField(td, val, isStatus = false) {
  if (td.isContentEditable) td.contentEditable = false;
  const fd = new FormData();
  fd.append('action', 'update');
  fd.append('mact', td.dataset.mact);
  fd.append('field', td.dataset.field);
  fd.append('value', val);
  await fetch(CT_API, { method: 'POST', body: fd });
  if (isStatus) td.innerHTML = renderCtTrangThai(val);
  else td.textContent = val;
}

async function onAddChiTiet() {
  const month = document.getElementById('ctFilterMonth').value;
  const fd = new FormData();
  fd.append('action', 'create');
  fd.append('ma_hd', currentMaHd);
  if (month) fd.append('thang', month + '-01');
  const res = await fetch(CT_API, { method: 'POST', body: fd });
  const json = await res.json();
  if (json.success) {
    showToast('Thêm tháng mới thành công!');
    loadChiTiet(currentMaHd);
  } else {
    showToast('Lỗi: ' + json.error, 'error');
  }
}

async function onCtDelete(mact) {
  if (!confirm('Xóa?')) return;
  const fd = new FormData(); fd.append('action', 'delete'); fd.append('mact', mact);
  await fetch(CT_API, { method: 'POST', body: fd });
  loadChiTiet(currentMaHd);
}

// --- Utils ---
function toggleAdd(s) { document.getElementById('modalAdd')?.classList.toggle('hidden', !s); }
function toggleChiTiet(s) { document.getElementById('modalChiTiet')?.classList.toggle('hidden', !s); }

async function onAddSubmit(e) {
  e.preventDefault();
  const form = e.target;
  const fd = new FormData(form);

  // Validate Phone
  const phonePattern = /^[0-9.+ ]*$/;
  const sdt_kh = (fd.get('sdt_kh') || '').trim();
  const sdt_gt = (fd.get('sdt_gt') || '').trim();

  if (sdt_kh && !phonePattern.test(sdt_kh)) {
    showToast('Số điện thoại khách hàng không đúng định dạng!', 'error');
    return;
  }
  if (sdt_gt && !phonePattern.test(sdt_gt)) {
    showToast('Số điện thoại người giới thiệu không đúng định dạng!', 'error');
    return;
  }

  fd.append('action', 'create');
  ['tien_vay', 'tien_theo_ky', 'dong_lai'].forEach(k => fd.set(k, getRawNumber(fd.get(k))));
  const res = await fetch(HOP_API, { method: 'POST', body: fd });
  const json = await res.json();
  if (json.success) {
    showToast('Thêm hợp đồng thành công!');
    toggleAdd(false);
    form.reset();
    loadTable();
  } else {
    showToast('Lỗi: ' + json.error, 'error');
  }
}

function showToast(msg, type = 'success') {
  const container = document.getElementById('toastContainer');
  if (!container) return;
  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  toast.textContent = msg;
  container.appendChild(toast);
  setTimeout(() => toast.remove(), 3000);
}

function renderTrangThai(s) {
  const map = { dang_vay: ['orange', 'Đang vay'], da_hoan_tat: ['green', 'Hoàn tất'], no_xau: ['red', 'Nợ xấu'] };
  const [c, l] = map[s] || ['gray', s];
  return `<span style="color:${c};font-weight:bold">${l}</span>`;
}

function renderCtTrangThai(s) {
  const map = { chua_thu: ['orange', 'Chưa thu'], da_thu: ['green', 'Đã thu'], qua_han: ['red', 'Quá hạn'] };
  const [c, l] = map[s] || ['gray', s];
  return `<span style="color:${c};font-weight:bold">${l}</span>`;
}

function formatVND(v) { return new Intl.NumberFormat('vi-VN').format(v || 0) + ' đ'; }
function formatVNDInput(v) { return (v.replace(/\D/g, '')).replace(/\B(?=(\d{3})+(?!\d))/g, ".") + ' đ'; }
function getRawNumber(v) { return Number(v.toString().replace(/\D/g, '')) || 0; }
function escapeHtml(t) { return (t || '').toString().replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m])); }
function debounce(f, ms = 200) { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => f(...a), ms); }; }

function applyHighlight(color) {
  const sel = window.getSelection();
  if (!sel.rangeCount) return;
  const range = sel.getRangeAt(0);
  const span = document.createElement('span');
  span.style.backgroundColor = color;
  span.className = 'text-highlight';
  try { range.surroundContents(span); } catch (e) { span.appendChild(range.extractContents()); range.insertNode(span); }
}

function removeHighlight() {
  const sel = window.getSelection();
  let node = sel.anchorNode;
  if (node?.nodeType === 3) node = node.parentNode;
  while (node && node.tagName !== 'BODY') {
    if (node.classList.contains('text-highlight')) {
      const p = node.parentNode;
      while (node.firstChild) p.insertBefore(node.firstChild, node);
      p.removeChild(node);
      break;
    }
    node = node.parentNode;
  }
}
