@extends('layouts.app')
@section('tieu_de', 'Lớp hạ tầng lưới điện')

@section('noi_dung')
<div class="pad ha-tang-page">
  <div class="ha-tang-heading">
    <div>
      <span class="ha-tang-eyebrow">DỮ LIỆU BẢN ĐỒ</span>
      <h1>Lớp hạ tầng</h1>
      <p>Quản lý các lớp dữ liệu hạ tầng đang hiển thị trên bản đồ.</p>
    </div>
    @if (auth()->user()->duocSua())
      <button type="button" class="btn primary" onclick="document.getElementById('dlgThem').showModal()">+ Nạp lớp mới</button>
    @endif
  </div>

  <section class="ha-tang-overview" aria-label="Tổng quan lớp hạ tầng">
    <article class="ha-tang-stat">
      <span>Tổng số lớp</span>
      <strong>{{ number_format($lop->count(), 0, ',', '.') }}</strong>
    </article>
    <article class="ha-tang-stat">
      <span>Đang hiển thị</span>
      <strong>{{ number_format($soLopDangBat, 0, ',', '.') }} <small>/ {{ number_format($lop->count(), 0, ',', '.') }}</small></strong>
    </article>
    <article class="ha-tang-stat">
      <span>Tổng đối tượng</span>
      <strong>{{ number_format($tongDoiTuong, 0, ',', '.') }}</strong>
    </article>
    <article class="ha-tang-stat">
      <span>Trạm biến áp</span>
      <strong>{{ number_format($tongTram, 0, ',', '.') }}</strong>
    </article>
  </section>

  <div class="sheet">
    <div class="sheet-hd ha-tang-list-heading">
      <div>
        <h2>Các lớp dữ liệu</h2>
        <p>Cây dữ liệu phân theo hiện trạng, quy hoạch và nhóm file đã nạp.</p>
      </div>
      <div class="ha-tang-tree-controls" aria-label="Điều khiển cây lớp">
        <button type="button" class="btn sm" id="haTangMoTatCa">Mở tất cả</button>
        <button type="button" class="btn sm" id="haTangThuGonTatCa">Thu gọn</button>
      </div>
    </div>
    <div class="sheet-bd" style="padding:0">
      @if ($errors->suaLop->any())
        <div class="loi" style="margin:12px 12px 0">{{ $errors->suaLop->first() }}</div>
      @endif
      <div class="ha-tang-toolbar" role="search" aria-label="Lọc lớp hạ tầng">
        <label class="ha-tang-search">
          <span class="sr-only">Tìm lớp hạ tầng</span>
          <input type="search" id="haTangTim" placeholder="Tìm theo tên lớp, nguồn hoặc tên file" autocomplete="off">
        </label>
        <label>
          <span class="sr-only">Lọc theo dạng hình học</span>
          <select id="haTangLoai">
            <option value="">Tất cả dạng lớp</option>
            <option value="diem">Điểm</option>
            <option value="duong">Đường</option>
            <option value="vung">Vùng</option>
          </select>
        </label>
        <label>
          <span class="sr-only">Lọc theo trạng thái hiển thị</span>
          <select id="haTangTrangThai">
            <option value="">Mọi trạng thái</option>
            <option value="1">Đang hiển thị</option>
            <option value="0">Đã tắt</option>
          </select>
        </label>
        <button type="button" class="btn sm" id="haTangBoLoc">Xoá lọc</button>
        <span class="ha-tang-result" id="haTangKetQua" aria-live="polite"></span>
      </div>
      @php
        $nhomTheoTinhTrang = [
            'hien-trang' => [
                'ten' => 'Hiện trạng',
                'lop' => $lop->filter(fn ($l) => ! $l->quy_hoach),
            ],
            'quy-hoach' => [
                'ten' => 'Quy hoạch',
                'lop' => $lop->filter(fn ($l) => (bool) $l->quy_hoach),
            ],
        ];
        $khoaNhom = function ($l) {
            if ($l->nhom) {
                return $l->nhom;
            }
            $goc = preg_replace('/ — (điểm|đường|vùng)$/u', '', $l->ten);

            return $goc !== $l->ten ? $goc : 'rieng-'.$l->id;
        };
      @endphp
      <div class="ha-tang-tree" id="haTangTree" aria-label="Cây lớp hạ tầng">
        @foreach ($nhomTheoTinhTrang as $maTinhTrang => $nhanh)
          @php
            $lopTheoNhom = $nhanh['lop']->groupBy($khoaNhom);
            $soLopBat = $nhanh['lop']->where('hien_thi', true)->count();
          @endphp
          <section class="ha-tang-branch ha-tang-branch-{{ $maTinhTrang }}" data-ha-tang-branch
                   data-nhanh="{{ $maTinhTrang }}" data-expanded="1"
                   data-branch-tim="{{ \Illuminate\Support\Str::lower($nhanh['ten']) }}">
            <button type="button" class="ha-tang-branch-toggle" aria-expanded="true" aria-controls="ha-tang-branch-{{ $maTinhTrang }}" aria-label="Thu gọn {{ $nhanh['ten'] }}">
              <span class="ha-tang-chevron" aria-hidden="true">▾</span>
              <span class="ha-tang-branch-title">
                <strong>{{ $nhanh['ten'] }}</strong>
              </span>
              <span class="ha-tang-branch-summary">
                <span>{{ number_format($nhanh['lop']->count(), 0, ',', '.') }} lớp</span>
                <span>{{ number_format($soLopBat, 0, ',', '.') }} đang hiển thị</span>
                <span>{{ number_format($nhanh['lop']->sum('doi_tuong_count'), 0, ',', '.') }} đối tượng</span>
              </span>
            </button>
            <div class="ha-tang-branch-content" id="ha-tang-branch-{{ $maTinhTrang }}">
              @if ($nhanh['lop']->isEmpty())
                <p class="ha-tang-empty-branch">
                  {{ $nhanh['ten'] === 'Quy hoạch' ? 'Chưa có lớp quy hoạch.' : 'Chưa có lớp hiện trạng.' }}
                </p>
              @else
                @foreach ($lopTheoNhom as $tenNhom => $dsCon)
                  @if ($dsCon->count() > 1)
                    @php $maNhom = $maTinhTrang.'-'.$dsCon->first()->id; @endphp
                    <section class="ha-tang-group" data-ha-tang-group data-branch="{{ $maTinhTrang }}"
                             data-nhom="{{ $maNhom }}" data-expanded="0"
                             data-nhom-tim="{{ \Illuminate\Support\Str::lower($tenNhom.' '.$dsCon->first()->nguon.' '.$dsCon->first()->ten_file) }}">
                      <button type="button" class="ha-tang-group-toggle" aria-expanded="false" aria-controls="ha-tang-group-{{ $maNhom }}" aria-label="Mở nhóm {{ $tenNhom }}">
                        <span class="ha-tang-chevron" aria-hidden="true">▸</span>
                        <span class="ha-tang-group-title">
                          <strong>{{ $tenNhom }}</strong>
                          <small>{{ $dsCon->first()->nguon ?: 'Nguồn chưa ghi' }}</small>
                        </span>
                        <span class="ha-tang-group-summary">
                          <span>{{ $dsCon->count() }} lớp</span>
                          <span>{{ $dsCon->where('hien_thi', true)->count() }} đang hiển thị</span>
                        </span>
                      </button>
                      <div class="ha-tang-group-content" id="ha-tang-group-{{ $maNhom }}">
                        @foreach ($dsCon as $l)
                          @include('lop-ha-tang._hang', ['l' => $l, 'maTinhTrang' => $maTinhTrang, 'conCua' => $maNhom])
                        @endforeach
                      </div>
                    </section>
                  @else
                    @include('lop-ha-tang._hang', ['l' => $dsCon->first(), 'maTinhTrang' => $maTinhTrang, 'conCua' => null])
                  @endif
                @endforeach
              @endif
            </div>
          </section>
        @endforeach
        @if ($lop->isNotEmpty())
          <div class="ha-tang-no-results" hidden>Không tìm thấy lớp nào khớp với bộ lọc.</div>
        @endif
      </div>
    </div>
  </div>
</div>

@if (auth()->user()->duocSua())
  <dialog id="dlgSua">
    <form method="post" id="formSua" enctype="multipart/form-data">
      @csrf @method('put')
      <div class="sheet-hd"><h2 style="font-size:14px">Sửa lớp hạ tầng</h2></div>
      <div class="sheet-bd">
        <div class="grid">
          <label class="f full">Tên lớp
            <input name="ten" id="suaTen" required maxlength="255">
          </label>
          <label class="f full">Nguồn dữ liệu
            <input name="nguon" id="suaNguon" maxlength="255">
          </label>
          <label class="f full">Thay file dữ liệu (để trống nếu chỉ đổi tên/nguồn/hiển thị)
            <input type="file" name="tep" accept=".geojson,.json,.kml,.kmz">
          </label>
          <label class="f">
            <span style="display:inline-flex;align-items:center;gap:5px">
              <input type="checkbox" id="suaDungMau"> Màu riêng (bỏ chọn thì tô theo cấp điện áp)
            </span>
            <input type="color" name="mau" id="suaMau" value="#5b6773" disabled>
          </label>
          <label class="f" id="suaBieuTuongBoc">Biểu tượng (chỉ áp dụng cho lớp dạng điểm)
            <select name="bieu_tuong" id="suaBieuTuong">
              <option value="">— Mặc định (chấm tròn) —</option>
              @foreach ($dsBieuTuong as $b)
                <option value="{{ $b->ma }}">{{ $b->ten }}</option>
              @endforeach
            </select>
          </label>
          <label class="f">
            <span id="suaKichThuocNhan">Kích thước (px)</span>
            <input type="number" name="kich_thuoc" id="suaKichThuoc" min="1" max="30" placeholder="Mặc định">
          </label>
          <label class="f full" style="display:flex;flex-direction:row;align-items:center;gap:6px">
            <input type="checkbox" name="quy_hoach" value="1" id="suaQuyHoach">
            Lớp quy hoạch (chưa xây) — vẽ nét đứt, mờ hơn hiện trạng
          </label>
        </div>
        <p class="hint">Chọn file để thay toàn bộ đối tượng của lớp này bằng dữ liệu mới, VD khi có bản cập nhật mới hơn.</p>
        <p class="hint">Màu/biểu tượng/kích thước để trống thì dùng mặc định: tô theo cấp điện áp, chấm tròn, cỡ theo loại đối tượng.</p>
      </div>
      <div class="sheet-ft">
        <button type="button" class="btn" onclick="document.getElementById('dlgSua').close()">Huỷ</button>
        <button class="btn primary">Lưu</button>
      </div>
    </form>
  </dialog>

  <dialog id="dlgThem">
    <form method="post" action="{{ route('lop-ha-tang.store') }}" enctype="multipart/form-data">
      @csrf
      <div class="sheet-hd"><h2 style="font-size:14px">Nạp lớp mới</h2></div>
      <div class="sheet-bd">
        @if ($errors->any())
          <div class="loi">{{ $errors->first() }}</div>
        @endif
        <div class="grid">
          <label class="f full">Tên lớp
            <input name="ten" value="{{ old('ten') }}" placeholder="VD: Lưới điện Quảng Trị" required>
          </label>
          <label class="f full">Nguồn dữ liệu
            <input name="nguon" value="{{ old('nguon') }}" placeholder="VD: OpenInfraMap, trích xuất 09/2026">
          </label>
          <label class="f full">File dữ liệu
            <input type="file" name="tep" accept=".geojson,.json,.kml,.kmz" required>
          </label>
        </div>
        <p class="hint">
          Nhận GeoJSON, KML và KMZ, tối đa 100 MB. Trạm biến áp được nhận diện theo thẻ
          <code>power=substation</code> của OpenStreetMap, hoặc theo tên có chứa “trạm biến áp”, “TBA”.
          Cấp điện áp đọc từ thẻ <code>voltage</code> (đơn vị vôn, tự quy đổi sang kV), nếu không có thì bắt
          từ tên.
        </p>
        <p class="hint">
          File có cả điểm (trạm...) lẫn đường (tuyến dây) sẽ tự tách thành nhiều lớp riêng theo
          tên lớp kèm “— điểm”/“— đường”/“— vùng”, để bật/tắt hiển thị độc lập từng loại.
        </p>
        <p class="hint">
          Dữ liệu lưới điện OpenStreetMap đủ ở cấp 220–500 kV, thưa ở 110 kV và lưới trung áp. Dùng để định vị
          và sàng lọc, không dùng làm số liệu trích dẫn trong văn bản.
        </p>
      </div>
      <div class="sheet-ft">
        <button type="button" class="btn" onclick="document.getElementById('dlgThem').close()">Huỷ</button>
        <button class="btn primary">Nạp lớp</button>
      </div>
    </form>
  </dialog>

@endif

@push('cuoi_trang')
<script>
(function () {
  var tim = document.getElementById('haTangTim');
  var loai = document.getElementById('haTangLoai');
  var trangThai = document.getElementById('haTangTrangThai');
  var ketQua = document.getElementById('haTangKetQua');
  var dongTrong = document.querySelector('.ha-tang-no-results');
  var lopLe = Array.prototype.slice.call(document.querySelectorAll('[data-lop-ha-tang]'));
  var nhanh = Array.prototype.slice.call(document.querySelectorAll('[data-ha-tang-branch]'));
  var nhom = Array.prototype.slice.call(document.querySelectorAll('[data-ha-tang-group]'));
  var chuanHoa = function (giaTri) { return (giaTri || '').toLocaleLowerCase('vi').trim(); };
  var coBoLoc = function () { return !!(chuanHoa(tim.value) || loai.value || trangThai.value); };

  function khopBoLoc(dong, tenNhomKhop, tenNhanhKhop) {
    var tuKhoa = chuanHoa(tim.value);
    var khopTen = !tuKhoa || tenNhomKhop || tenNhanhKhop
      || chuanHoa(dong.dataset.tim).indexOf(tuKhoa) !== -1;
    var khopLoai = !loai.value || dong.dataset.loai === loai.value;
    var khopTrangThai = !trangThai.value || dong.dataset.hienThi === trangThai.value;

    return khopTen && khopLoai && khopTrangThai;
  }

  function apDungBoLoc() {
    var soHienThi = 0;
    var dangLoc = coBoLoc();

    nhanh.forEach(function (dongNhanh) {
      var maNhanh = dongNhanh.dataset.nhanh;
      var cacDongNhanh = lopLe.filter(function (dong) { return dong.dataset.branch === maNhanh; });
      var cacNhom = nhom.filter(function (dong) { return dong.dataset.branch === maNhanh; });
      var tenNhanhKhop = chuanHoa(dongNhanh.dataset.branchTim).indexOf(chuanHoa(tim.value)) !== -1;
      var soKhopTrongNhanh = 0;
      cacNhom.forEach(function (dongNhom) {
        var maNhom = dongNhom.dataset.nhom;
        var cacDongCon = cacDongNhanh.filter(function (dong) { return dong.dataset.thuocNhom === maNhom; });
        var tenNhomKhop = chuanHoa(dongNhom.dataset.nhomTim).indexOf(chuanHoa(tim.value)) !== -1;
        var cacDongKhop = cacDongCon.filter(function (dong) {
          return khopBoLoc(dong, tenNhomKhop || tenNhanhKhop, tenNhanhKhop);
        });
        dongNhom.hidden = dangLoc && cacDongKhop.length === 0;
        dongNhom.dataset.filterOpen = dangLoc && cacDongKhop.length ? '1' : '0';
        cacDongCon.forEach(function (dong) {
          var khop = khopBoLoc(dong, tenNhomKhop || tenNhanhKhop, tenNhanhKhop);
          dong.hidden = !khop;
        });
        soKhopTrongNhanh += cacDongKhop.length;
      });

      var cacDongLe = cacDongNhanh.filter(function (dong) { return !dong.dataset.thuocNhom; });
      cacDongLe.forEach(function (dong) {
        var hien = khopBoLoc(dong, false, tenNhanhKhop);
        dong.hidden = !hien;
        soKhopTrongNhanh += hien ? 1 : 0;
      });

      dongNhanh.hidden = dangLoc && soKhopTrongNhanh === 0;
      dongNhanh.dataset.filterOpen = dangLoc && soKhopTrongNhanh ? '1' : '0';
      soHienThi += soKhopTrongNhanh;
    });

    if (ketQua) {
      ketQua.textContent = (dangLoc ? 'Phù hợp ' : 'Tổng cộng ') + soHienThi + '/' + lopLe.length + ' lớp';
    }
    if (dongTrong) {
      dongTrong.hidden = !dangLoc || soHienThi !== 0;
    }
  }

  nhanh.forEach(function (dongNhanh) {
    var nutMo = dongNhanh.querySelector('.ha-tang-branch-toggle');
    nutMo.addEventListener('click', function () {
      var dangMo = dongNhanh.dataset.expanded !== '1';
      dongNhanh.dataset.expanded = dangMo ? '1' : '0';
      nutMo.setAttribute('aria-expanded', dangMo ? 'true' : 'false');
      nutMo.setAttribute('aria-label', (dangMo ? 'Thu gọn ' : 'Mở ') + dongNhanh.querySelector('.ha-tang-branch-title strong').textContent);
      nutMo.querySelector('.ha-tang-chevron').textContent = dangMo ? '▾' : '▸';
      if (coBoLoc()) {
        dongNhanh.dataset.filterOpen = dangMo ? '1' : '0';
      } else {
        apDungBoLoc();
      }
    });
  });

  nhom.forEach(function (dongNhom) {
    var nutMo = dongNhom.querySelector('.ha-tang-group-toggle');
    nutMo.addEventListener('click', function () {
      var dangMo = dongNhom.dataset.expanded !== '1';
      dongNhom.dataset.expanded = dangMo ? '1' : '0';
      nutMo.setAttribute('aria-expanded', dangMo ? 'true' : 'false');
      nutMo.setAttribute('aria-label', (dangMo ? 'Thu gọn nhóm ' : 'Mở nhóm ') + dongNhom.querySelector('.ha-tang-group-title strong').textContent);
      nutMo.querySelector('.ha-tang-chevron').textContent = dangMo ? '▾' : '▸';
      if (coBoLoc()) {
        dongNhom.dataset.filterOpen = dangMo ? '1' : '0';
      } else {
        apDungBoLoc();
      }
    });
  });

  tim.addEventListener('input', apDungBoLoc);
  loai.addEventListener('change', apDungBoLoc);
  trangThai.addEventListener('change', apDungBoLoc);
  document.getElementById('haTangBoLoc').addEventListener('click', function () {
    tim.value = '';
    loai.value = '';
    trangThai.value = '';
    apDungBoLoc();
    tim.focus();
  });
  document.getElementById('haTangMoTatCa').addEventListener('click', function () {
    nhanh.concat(nhom).forEach(function (dong) {
      dong.dataset.expanded = '1';
      var nutMo = dong.querySelector('button');
      nutMo.setAttribute('aria-expanded', 'true');
      nutMo.querySelector('.ha-tang-chevron').textContent = '▾';
      if (coBoLoc()) {
        dong.dataset.filterOpen = '1';
      }
    });
    if (!coBoLoc()) {
      apDungBoLoc();
    }
  });
  document.getElementById('haTangThuGonTatCa').addEventListener('click', function () {
    nhanh.concat(nhom).forEach(function (dong) {
      dong.dataset.expanded = '0';
      var nutMo = dong.querySelector('button');
      nutMo.setAttribute('aria-expanded', 'false');
      nutMo.querySelector('.ha-tang-chevron').textContent = '▸';
      if (coBoLoc()) {
        dong.dataset.filterOpen = '0';
      }
    });
    if (!coBoLoc()) {
      apDungBoLoc();
    }
  });
  apDungBoLoc();

  @if (auth()->user()->duocSua())
    var dlgSua = document.getElementById('dlgSua');
    var suaMau = document.getElementById('suaMau');
    var suaDungMau = document.getElementById('suaDungMau');

    suaDungMau.addEventListener('change', function () {
      suaMau.disabled = ! this.checked;
    });

    Array.prototype.forEach.call(document.querySelectorAll('[data-sua]'), function (btn) {
      btn.addEventListener('click', function () {
        document.getElementById('formSua').action = btn.dataset.action;
        document.getElementById('suaTen').value = btn.dataset.ten;
        document.getElementById('suaNguon').value = btn.dataset.nguon;
        document.getElementById('suaBieuTuong').value = btn.dataset.bieuTuong || '';
        document.getElementById('suaKichThuoc').value = btn.dataset.kichThuoc || '';
        document.getElementById('suaQuyHoach').checked = btn.dataset.quyHoach === '1';

        suaDungMau.checked = !! btn.dataset.mau;
        suaMau.disabled = ! btn.dataset.mau;
        suaMau.value = btn.dataset.mau || '#5b6773';

        // Biểu tượng chỉ có ý nghĩa với lớp dạng điểm; ẩn đi cho lớp đường/vùng để đỡ rối
        document.getElementById('suaBieuTuongBoc').hidden = btn.dataset.loai !== 'diem';
        document.getElementById('suaKichThuocNhan').textContent = btn.dataset.loai === 'duong'
          ? 'Độ dày nét (px)' : 'Kích thước icon (px)';

        dlgSua.showModal();
      });
    });

    @if ($errors->any())
      document.getElementById('dlgThem').showModal();
    @endif
  @endif
})();
</script>
@endpush
@endsection
