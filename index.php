<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Quản lý hợp đồng vay</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <header><h1>Quản lý hợp đồng vay</h1></header>

  <section class="summary">
    <p>Tổng số tiền cho vay: <span id="totalLoanAmount">0</span> đ</p>
    <p>Tổng lãi: <span id="totalInterest">0</span> đ</p>
  </section>

  <section class="controls">
    <div>
      <input id="globalSearch" placeholder="Tìm kiếm (tên, sdt, ghi chú...)" />
      <button id="btnRefresh">Làm mới</button>
      <button id="btnChuadong">Chưa đóng</button>
      <select id="statusFilter">
        <option value="">Tất cả trạng thái</option>
        <option value="dang_vay">Đang vay</option>
        <option value="da_hoan_tat">Đã hoàn tất</option>
        <option value="no_xau">Nợ xấu</option>
      </select>
      <input type="date" id="startDate" title="Từ ngày" />
      <input type="date" id="endDate" title="Đến ngày" />
      <button id="btnSearchDateRange">Tìm kiếm</button>
      <button id="btnExportExcel">Xuất Excel</button>
      <button id="trash">Thùng rác</button>
    </div>
    <div class="actions-right">
      <div class="highlight-tools">
        <input type="color" id="highlightColor" value="#ffff00" title="Chọn màu tô" />
        <button id="btnHighlight">Tô màu</button>
        <button id="btnRemoveHighlight">Bỏ tô</button>
      </div>
      <button id="btnAdd">Thêm hợp đồng</button>
    </div>
  </section>

  <section class="table-wrap">
    <table id="dataTable">
      <thead>
        <tr class="header-row">
          <th data-field="ma_hd">STT</th>
          <th data-field="ngay_vay">Ngày vay</th>
          <th data-field="so_ngay">Số ngày</th>
          <th data-field="khach_hang">Khách hàng</th>
          <th data-field="sdt_kh">SDT</th>
          <th data-field="tien_vay">Tiền vay</th>
          <th data-field="tien_theo_ky">Tiền theo kỳ</th>
          <th data-field="tong_ghi">Tổng ghi</th>
          <th data-field="dong_lai">Đóng lãi</th>
          <th data-field="gia_han">Gia hạn</th>
          <th data-field="tat_toan">Tất toán</th>
          <th data-field="ghi_chu">Ghi chú</th>
          <th data-field="nguoi_gioi_thieu">Người GT</th>
          <th data-field="sdt_gt">SDT GT</th>
          <th data-field="trang_thai">Trạng thái</th>
          <th>Thao tác</th>
        </tr>
        <tr class="filter-row">
          <td></td>
          <td></td>
          <td></td>
          <td><input class="col-filter" data-filter="khach_hang" placeholder="..."></td>
          <td><input class="col-filter" data-filter="sdt_kh" placeholder="..."></td>
          <td></td>
          <td></td>
          <td></td>
          <td></td>
          <td></td>
          <td></td>
          <td></td>
          <td><input class="col-filter" data-filter="nguoi_gioi_thieu" placeholder="..."></td>
          <td></td>
          <td></td>
          <td></td>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </section>

  <!-- Modal Add -->
  <div id="modalAdd" class="modal hidden">
    <div class="modal-content">
      <h3>Thêm hợp đồng</h3>
      <form id="formAdd">
        <div class="form-grid">
          <label>Ngày vay<input name="ngay_vay" type="date" required></label>
          <label>Khách hàng<input name="khach_hang" type="text" required></label>
          <label>SDT<input name="sdt_kh" type="text"></label>
          <label>Tiền vay<input name="tien_vay" type="text" placeholder="0 đ"></label>
          <label>Tiền theo kỳ<input name="tien_theo_ky" type="text" placeholder="0 đ"></label>
          <label>Đóng lãi<input name="dong_lai" type="text" placeholder="0 đ"></label>
          <label>Người giới thiệu<input name="nguoi_gioi_thieu" type="text"></label>
          <label>SDT GT<input name="sdt_gt" type="text"></label>
          <input type="hidden" name="trang_thai" value="dang_vay">
        </div>
        <div class="modal-actions">
          <button type="button" id="btnCancelAdd">Hủy</button>
          <button type="submit">Lưu</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal Chi tiết -->
  <div id="modalChiTiet" class="modal hidden">
    <div class="modal-content">
      <button class="closeChiTiet">Đóng</button>
      <h3>Chi tiết hợp đồng #<span id="ctMa"></span></h3>
      <p>Họ tên: <strong id="ctHT"></strong> | SDT: <strong id="ctSDT"></strong></p>
      <p>Số tiền phải đóng: <strong id="ctTien"></strong></p>
      <div class="ct-tools">
        <input type="month" id="ctFilterMonth">
        <button id="ctAdd">Thêm tháng</button>
      </div>
      <table id="ctTable">
        <thead>
          <tr><th>Tháng</th><th>Trạng thái</th><th>Ghi chú</th><th>Xóa</th></tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>

  <div id="toastContainer" class="toast-container"></div>
  <script src="script.js" defer></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      updateSummary();
    });

    async function updateSummary() {
      try {
        const res = await fetch('api_chitiet.php?action=summary');
        const data = await res.json();
        document.getElementById('totalLoanAmount').textContent = parseInt(data.totalLoanAmount).toLocaleString('vi-VN');
        document.getElementById('totalInterest').textContent = parseInt(data.totalInterest).toLocaleString('vi-VN');
      } catch (error) {
        console.error('Error fetching summary:', error);
      }
    }

    document.getElementById('btnSearchDateRange')?.addEventListener('click', () => {
      const startDate = document.getElementById('startDate')?.value;
      const endDate = document.getElementById('endDate')?.value;

      const params = new URLSearchParams();
      params.set('action', 'list');
      if (startDate) params.set('start_date', startDate);
      if (endDate) params.set('end_date', endDate);

      fetch(`api_hopdong.php?${params.toString()}`)
        .then(res => res.json())
        .then(json => renderTable(json.data || []))
        .catch(e => console.error(e));
    });
  </script>
</body>
</html>
