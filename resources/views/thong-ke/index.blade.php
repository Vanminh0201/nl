@extends('layouts.app')
@section('tieu_de', 'Thống kê dự án công nghiệp')

@section('noi_dung')
<div class="pad">
  <form method="get" class="thanhloc">
    <label class="grow">Từ khoá
      <input type="search" name="tu_khoa" value="{{ is_scalar(request('tu_khoa')) ? request('tu_khoa') : '' }}">
    </label>
    <label>Loại hình
      <select name="loai_hinh">
        <option value="">Tất cả</option>
        @foreach ($dsLoaiHinh as $ma => $l)
          <option value="{{ $ma }}" @selected(request('loai_hinh') === $ma)>{{ $l->ten }}</option>
        @endforeach
      </select>
    </label>
    <label>Trạng thái
      <select name="trang_thai">
        <option value="">Tất cả</option>
        @foreach ($dsTrangThai as $ten => $t)
          <option value="{{ $ten }}" @selected(request('trang_thai') === $ten)>{{ $ten }}</option>
        @endforeach
      </select>
    </label>
    <button class="btn primary">Lọc</button>
    <a href="{{ route('thong-ke') }}" class="btn">Bỏ lọc</a>
    <a href="{{ route('xuat.excel', request()->query()) }}" class="btn">Xuất Excel</a>
  </form>

  <div class="kpis">
    <div class="kpi"><b>{{ number_format($tong->so_du_an, 0, ',', '.') }}</b><span>dự án trong danh mục</span></div>
    <div class="kpi"><b>{{ number_format($tong->tong_von, 0, ',', '.') }}</b><span>tỷ đồng vốn đăng ký</span></div>
    <div class="kpi"><b>{{ number_format($tong->tong_cong_suat, 0, ',', '.') }}</b><span>MW tổng công suất</span></div>
    <div class="kpi"><b>{{ number_format($tong->tong_dien_tich, 0, ',', '.') }}</b><span>ha diện tích</span></div>
    <div class="kpi"><b style="color:var(--ok)">{{ $tong->dang_hoat_dong }}</b><span>dự án đã hoạt động</span></div>
    <div class="kpi"><b style="color:var(--danger)">{{ $tong->cham_tien_do }}</b><span>chậm tiến độ hoặc tạm dừng</span></div>
    <div class="kpi"><b style="color:var(--warn)">{{ $tong->thieu_toa_do }}</b><span>chưa gắn toạ độ</span></div>
  </div>

  <div class="tk-luoi">
    <div class="panel tk-panel">
      <h3>Số dự án theo loại hình</h3>
      <div class="tk-khung tk-khung-donut"><canvas id="bd-loai-hinh-so-luong"></canvas></div>
    </div>
    <div class="panel tk-panel">
      <h3>Số dự án theo trạng thái</h3>
      <div class="tk-khung tk-khung-donut"><canvas id="bd-trang-thai"></canvas></div>
    </div>
    <div class="panel tk-panel">
      <h3>Công suất theo loại hình (MW)</h3>
      <div class="tk-khung"><canvas id="bd-cong-suat"></canvas></div>
    </div>
    <div class="panel tk-panel">
      <h3>Vốn đăng ký theo loại hình (tỷ đồng)</h3>
      <div class="tk-khung"><canvas id="bd-von"></canvas></div>
    </div>
    <div class="panel tk-panel tk-panel-rong">
      <h3>Số dự án theo địa bàn</h3>
      <p class="hint">Dự án có nhiều xã/phường được tính tại từng địa bàn đã chọn; không cộng các cột địa bàn thành tổng dự án.</p>
      <div class="tk-khung tk-khung-cao"><canvas id="bd-dia-ban"></canvas></div>
    </div>
    <div class="panel tk-panel">
      <h3>Số dự án còn thiếu từng loại hồ sơ</h3>
      <div class="tk-khung"><canvas id="bd-ho-so-thieu"></canvas></div>
    </div>
  </div>
</div>
@endsection

@push('cuoi_trang')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.5.1/chart.umd.min.js"></script>
<script>
(function () {
  if (typeof Chart === 'undefined') return;
  Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
  Chart.defaults.color = '#4a5866';

  var duLieuLoaiHinh = @json($theoLoaiHinh->values());
  var duLieuCongSuat = @json($theoLoaiHinhCongSuat->values());
  var duLieuTrangThai = @json($theoTrangThai->values());
  var duLieuDiaBan = @json($theoDiaBan->values());
  var duLieuVon = @json($theoLoaiHinh->filter(fn ($r) => $r['von'] > 0)->values());
  var duLieuHoSo = @json($hoSoThieu->values());

  function tronDonut(canvasId, ds, khoa, donVi) {
    var el = document.getElementById(canvasId);
    if (!el || !ds.length) { if (el) elKhongDuLieu(el); return; }
    new Chart(el, {
      type: 'doughnut',
      data: {
        labels: ds.map(function (r) { return r.nhan; }),
        datasets: [{
          data: ds.map(function (r) { return r[khoa]; }),
          backgroundColor: ds.map(function (r) { return r.mau || '#5d6b61'; }),
          borderColor: '#fff', borderWidth: 2
        }]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
          legend: { position: 'right', labels: { boxWidth: 11, padding: 10, font: { size: 12 } } },
          tooltip: { callbacks: { label: function (ctx) {
            return ctx.label + ': ' + ctx.parsed.toLocaleString('vi-VN') + (donVi || '');
          } } }
        }
      }
    });
  }

  function veCotNgang(canvasId, ds, khoa, donVi, mauChung) {
    var el = document.getElementById(canvasId);
    if (!el || !ds.length) { if (el) elKhongDuLieu(el); return; }
    new Chart(el, {
      type: 'bar',
      data: {
        labels: ds.map(function (r) { return r.nhan; }),
        datasets: [{
          data: ds.map(function (r) { return r[khoa]; }),
          backgroundColor: ds.map(function (r) { return r.mau || mauChung; }),
          borderRadius: 3, maxBarThickness: 22
        }]
      },
      options: {
        indexAxis: 'y', responsive: true, maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: { callbacks: { label: function (ctx) {
            return ctx.parsed.x.toLocaleString('vi-VN') + (donVi || '');
          } } }
        },
        scales: {
          x: { beginAtZero: true, grid: { color: '#e2e6e1' }, ticks: { callback: function (v) { return v.toLocaleString('vi-VN'); } } },
          y: { grid: { display: false } }
        }
      }
    });
  }

  function elKhongDuLieu(el) {
    var p = document.createElement('p');
    p.className = 'hint'; p.style.margin = '0'; p.textContent = 'Chưa có dữ liệu.';
    el.replaceWith(p);
  }

  tronDonut('bd-loai-hinh-so-luong', duLieuLoaiHinh, 'so_luong');
  tronDonut('bd-trang-thai', duLieuTrangThai, 'so_luong');
  veCotNgang('bd-cong-suat', duLieuCongSuat, 'cong_suat', ' MW', '#1f4b6e');
  veCotNgang('bd-von', duLieuVon, 'von', ' tỷ', '#1f4b6e');
  veCotNgang('bd-dia-ban', duLieuDiaBan, 'so_luong', '', '#1f4b6e');
  veCotNgang('bd-ho-so-thieu', duLieuHoSo, 'so_luong', '', '#96701a');
})();
</script>
@endpush
