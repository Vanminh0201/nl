@extends('layouts.app')
@section('tieu_de', 'Lớp hạ tầng lưới điện')

@section('noi_dung')
<div class="pad">
  <div class="sheet">
    <div class="sheet-hd" style="display:flex;align-items:center;justify-content:space-between;gap:10px">
      <h2>Lớp hạ tầng đã nạp</h2>
      @if (auth()->user()->duocSua())
        <button type="button" class="btn primary sm" onclick="document.getElementById('dlgThem').showModal()">+ Nạp lớp mới</button>
      @endif
    </div>
    <div class="sheet-bd" style="padding:0">
      @if ($errors->suaLop->any())
        <div class="loi" style="margin:12px 12px 0">{{ $errors->suaLop->first() }}</div>
      @endif
      @php
        // Gộp các lớp cùng 1 lần nạp (cột nhom) thành 1 dòng cha, mở/thu gọn khi bấm.
        // Lớp nạp từ trước khi có cột nhom vẫn nhận ra nhau qua hậu tố "— điểm/đường/vùng".
        $nhomLop = $lop->groupBy(function ($l) {
            if ($l->nhom) {
                return $l->nhom;
            }
            $goc = preg_replace('/ — (điểm|đường|vùng)$/u', '', $l->ten);

            return $goc !== $l->ten ? $goc : 'rieng-'.$l->id;
        });
      @endphp
      <div class="tablewrap">
        <table>
          <thead>
            <tr><th>Tên lớp</th><th>Nguồn</th><th class="num">Đối tượng</th><th class="num">Trạm</th><th>Trạng thái</th><th></th></tr>
          </thead>
          <tbody>
          @forelse ($nhomLop as $tenNhom => $dsCon)
            @if ($dsCon->count() > 1)
              @php
                $maNhom = 'nhom-'.$dsCon->first()->id;
                $soDangBat = $dsCon->where('hien_thi', true)->count();
              @endphp
              <tr class="hang-nhom" data-mo-nhom="{{ $maNhom }}" style="cursor:pointer;background:#f4f6f4">
                <td><span class="mui-nhom">▸</span> <b>{{ $tenNhom }}</b> <span class="hint">({{ $dsCon->count() }} lớp con)</span></td>
                <td>{{ $dsCon->first()->nguon }}</td>
                <td class="num">{{ number_format($dsCon->sum('doi_tuong_count'), 0, ',', '.') }}</td>
                <td class="num">{{ number_format($dsCon->sum('so_tram'), 0, ',', '.') }}</td>
                <td>{{ $soDangBat }}/{{ $dsCon->count() }} đang bật</td>
                <td></td>
              </tr>
              @foreach ($dsCon as $l)
                @include('lop-ha-tang._hang', ['l' => $l, 'conCua' => $maNhom])
              @endforeach
            @else
              @include('lop-ha-tang._hang', ['l' => $dsCon->first()])
            @endif
          @empty
            <tr><td colspan="6" style="padding:26px;color:var(--ink-2)">
              Chưa nạp lớp nào. Bấm "Nạp lớp mới" để bắt đầu.
            </td></tr>
          @endforelse
          </tbody>
        </table>
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

  @push('cuoi_trang')
  <script>
  (function () {
    Array.prototype.forEach.call(document.querySelectorAll('[data-mo-nhom]'), function (hang) {
      hang.addEventListener('click', function () {
        var ma = hang.dataset.moNhom;
        var con = document.querySelectorAll('[data-thuoc-nhom="' + ma + '"]');
        var dangAn = con.length && con[0].hidden;
        Array.prototype.forEach.call(con, function (c) { c.hidden = ! dangAn; });
        hang.querySelector('.mui-nhom').textContent = dangAn ? '▾' : '▸';
      });
    });

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
  })();
  </script>
  @endpush
@endif
@endsection
