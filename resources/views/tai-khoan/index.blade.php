@extends('layouts.app')
@section('tieu_de', 'Người dùng')

@section('noi_dung')
<div class="pad">
  <div class="sheet">
    <div class="sheet-hd" style="display:flex;align-items:center;justify-content:space-between;gap:10px">
      <h2>Danh sách người dùng</h2>
      <button type="button" class="btn primary sm" onclick="document.getElementById('dlgThem').showModal()">+ Thêm người dùng</button>
    </div>
    <div class="sheet-bd" style="padding:12px">
      @if ($errors->any())
        <div class="loi" style="margin-bottom:12px">{{ $errors->first() }}</div>
      @endif

      <h3 style="font-size:13px;margin:0 0 8px">Các loại người dùng</h3>
      <div class="chips" style="margin-bottom:16px">
        @foreach ($vaiTro as $ma => $ten)
          <span class="tag" title="{{ [
            'quan_tri' => 'Toàn quyền: thêm/sửa/xoá dự án, quản lý lớp hạ tầng, danh mục và người dùng.',
            'nhap_lieu' => 'Thêm/sửa dự án và lớp hạ tầng, không xoá được và không quản lý danh mục/người dùng.',
            'xem' => 'Chỉ xem danh sách, bản đồ, thống kê — không thêm/sửa/xoá được gì.',
            'chu_dau_tu' => 'Chỉ thấy (các) dự án được gán riêng cho mình và nộp báo cáo tuần cho các dự án đó.',
          ][$ma] ?? '' }}">
            <i style="background:var(--primary)"></i>{{ $ten }}
          </span>
        @endforeach
      </div>

      <div class="tablewrap">
        <table>
          <thead>
            <tr><th>Tên</th><th>Thư điện tử</th><th>Vai trò</th><th>Quyền xem</th><th>Đơn vị</th><th>Trạng thái</th><th></th></tr>
          </thead>
          <tbody>
          @forelse ($dsTaiKhoan as $t)
            <tr>
              <td><b>{{ $t->name }}</b>{{ $t->id === auth()->id() ? ' (bạn)' : '' }}</td>
              <td>{{ $t->email }}</td>
              <td>{{ $t->ten_vai_tro }}</td>
              <td style="min-width:260px">
                @if ($t->vai_tro === 'quan_tri')
                  <span class="hint" style="margin:0">Toàn quyền</span>
                @else
                  <div class="pq-chips">
                    @foreach ($t->dsQuyenXem() as $ma)
                      @continue($ma === 'tat_ca_du_an' && $t->vai_tro !== 'chu_dau_tu')
                      <span class="pq-chip">{{ $mucXem[$ma]['ten'] }}</span>
                    @endforeach
                  </div>
                  <div class="hint" style="margin:3px 0 0">{{ $t->laQuyenTuyChinh() ? 'Tuỳ chỉnh riêng' : 'Mặc định theo vai trò' }}</div>
                @endif
              </td>
              <td>
                {{ $t->don_vi }}
                @if ($t->vai_tro === 'chu_dau_tu' && ! $t->da_duyet && $t->du_an_cho_duyet)
                  <div class="hint" style="margin-top:2px">
                    Yêu cầu gán:
                    {{ collect($t->du_an_cho_duyet)->map(fn ($id) => $duAnChoDuyet->get($id)?->ten ?? "Dự án #{$id} (không còn khả dụng)")->join(', ') }}
                  </div>
                @endif
                @if ($t->vai_tro === 'chu_dau_tu' && $t->duAn->isNotEmpty())
                  <div class="hint" style="margin-top:2px">Dự án: {{ $t->duAn->pluck('ten')->join(', ') }}</div>
                @endif
              </td>
              <td>
                @unless ($t->da_duyet)
                  <span class="chip cho">Chờ duyệt</span>
                @endunless
              </td>
              <td style="white-space:nowrap">
                @if ($t->vai_tro === 'chu_dau_tu' && ! $t->da_duyet)
                  <button type="button" class="btn sm primary" data-duyet
                          data-action="{{ route('tai-khoan.duyet', $t) }}"
                          data-tai-khoan-id="{{ $t->id }}" data-ten="{{ $t->name }}"
                          data-du-an-chon="{{ json_encode(array_values(array_unique(array_merge($t->du_an_cho_duyet ?? [], $t->duAn->modelKeys())))) }}"
                          data-du-an-yeu-cau="{{ json_encode($t->du_an_cho_duyet ?? []) }}">Duyệt / chọn dự án</button>
                @endif
                @unless ($t->vai_tro === 'quan_tri')
                  <button type="button" class="btn sm" data-quyen
                          data-action="{{ route('tai-khoan.quyen-xem', $t) }}"
                          data-ten="{{ $t->name }}" data-vai-tro="{{ $t->vai_tro }}" data-ten-vai-tro="{{ $t->ten_vai_tro }}"
                          data-quyen-hien="{{ json_encode($t->dsQuyenXem()) }}"
                          data-mac-dinh="{{ json_encode($t->quyenXemMacDinh()) }}">Phân quyền</button>
                @endunless
                <button type="button" class="btn sm" data-sua
                        data-action="{{ route('tai-khoan.update', $t) }}"
                        data-name="{{ $t->name }}" data-email="{{ $t->email }}"
                        data-vai-tro="{{ $t->vai_tro }}" data-don-vi="{{ $t->don_vi }}">Sửa</button>
                <form method="post" action="{{ route('tai-khoan.destroy', $t) }}" style="display:inline"
                      onsubmit="return confirm('Xoá người dùng {{ $t->name }}?')">
                  @csrf @method('delete')
                  <button class="btn sm danger">Xoá</button>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="7" style="padding:26px;color:var(--ink-2)">Chưa có người dùng nào.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<dialog id="dlgDuyet">
  <form method="post" id="formDuyet">
    @csrf @method('patch')
    <div class="sheet-hd">
      <h2 style="font-size:14px">Duyệt chủ đầu tư — <span id="duyetTen"></span></h2>
      <div class="hint" style="margin:3px 0 0">Chọn các dự án muốn gán. Yêu cầu đăng ký chỉ là gợi ý; có thể duyệt mà không gán dự án.</div>
    </div>
    <div class="sheet-bd">
      <div id="dsDuAnDuyet" style="max-height:50vh;overflow:auto">
        @forelse ($dsDuAnCoTheGan as $d)
          <label class="du-an-duyet" data-chu-dau-tu-id="{{ $d->chu_dau_tu_id }}" style="display:flex;gap:8px;align-items:flex-start;padding:8px;border-bottom:1px solid var(--line)">
            <input type="checkbox" name="du_an_ids[]" value="{{ $d->id }}">
            <span>{{ $d->ten }}<small style="display:block;color:var(--ink-2)">{{ $d->ten_loai_hinh }}@if ($d->dia_diem) — Hiện nay: {{ $d->dia_diem }}@endif @if ($d->diaDiemTruocDayDanhSach()) — Trước đây: {{ implode(', ', $d->diaDiemTruocDayDanhSach()) }}@endif</small></span>
          </label>
        @empty
          <p class="hint" style="margin:0">Hiện không có dự án chưa được gán.</p>
        @endforelse
      </div>
      <p id="duyetCanhBao" class="hint" hidden style="margin:8px 0 0"></p>
    </div>
    <div class="sheet-ft">
      <button type="button" class="btn" onclick="document.getElementById('dlgDuyet').close()">Huỷ</button>
      <button class="btn primary">Duyệt và lưu lựa chọn</button>
    </div>
  </form>
</dialog>

<dialog id="dlgSua">
  <form method="post" id="formSua">
    @csrf @method('put')
    <div class="sheet-hd"><h2 style="font-size:14px">Sửa người dùng</h2></div>
    <div class="sheet-bd">
      <div class="grid">
        <label class="f full">Tên
          <input name="name" id="suaName" required maxlength="255">
        </label>
        <label class="f full">Thư điện tử
          <input type="email" name="email" id="suaEmail" required maxlength="255">
        </label>
        <label class="f full">Mật khẩu mới (để trống nếu không đổi)
          <input type="password" name="password" autocomplete="new-password" minlength="6">
        </label>
        <label class="f">Vai trò
          <select name="vai_tro" id="suaVaiTro" required>
            @foreach ($vaiTro as $ma => $ten)
              <option value="{{ $ma }}">{{ $ten }}</option>
            @endforeach
          </select>
        </label>
        <label class="f">Đơn vị
          <input name="don_vi" id="suaDonVi" maxlength="255" placeholder="VD: Công ty CP Điện gió Hướng Linh 5">
        </label>
      </div>
      <p class="hint">Vai trò "Chủ đầu tư" dùng để gán cho tài khoản của nhà đầu tư — chỉ thấy dự án được gán riêng ở màn hình sửa dự án.</p>
    </div>
    <div class="sheet-ft">
      <button type="button" class="btn" onclick="document.getElementById('dlgSua').close()">Huỷ</button>
      <button class="btn primary">Lưu</button>
    </div>
  </form>
</dialog>

<dialog id="dlgQuyen">
  <form method="post" id="formQuyen">
    @csrf @method('put')
    <div class="sheet-hd">
      <h2 style="font-size:14px">Phân quyền xem — <span id="quyenTen"></span></h2>
      <div class="hint" style="margin:3px 0 0">Vai trò: <span id="quyenVaiTro"></span> · đánh dấu các mục người dùng này được xem</div>
    </div>
    <div class="sheet-bd">
      <div class="pq-ds">
        @foreach ($mucXem as $ma => $m)
          <label class="pq-muc" data-muc="{{ $ma }}">
            <input type="checkbox" name="quyen_xem[]" value="{{ $ma }}">
            <span><b>{{ $m['ten'] }}</b><small>{{ $m['mo_ta'] }}</small><em class="pq-khac" hidden>khác mặc định</em></span>
          </label>
        @endforeach
      </div>
      <p class="hint">Chỉ là quyền <b>xem</b> — thêm/sửa/xoá vẫn theo vai trò. Danh mục và Người dùng luôn chỉ dành cho Quản trị hệ thống.</p>
    </div>
    <div class="sheet-ft">
      <button type="submit" name="mac_dinh" value="1" class="btn" style="margin-right:auto" formnovalidate>Theo mặc định của vai trò</button>
      <button type="button" class="btn" onclick="document.getElementById('dlgQuyen').close()">Huỷ</button>
      <button class="btn primary">Lưu</button>
    </div>
  </form>
</dialog>

<dialog id="dlgThem">
  <form method="post" action="{{ route('tai-khoan.store') }}">
    @csrf
    <div class="sheet-hd"><h2 style="font-size:14px">Thêm người dùng</h2></div>
    <div class="sheet-bd">
      <div class="grid">
        <label class="f full">Tên
          <input name="name" value="{{ old('name') }}" required maxlength="255">
        </label>
        <label class="f full">Thư điện tử
          <input type="email" name="email" value="{{ old('email') }}" required maxlength="255">
        </label>
        <label class="f full">Mật khẩu
          <input type="password" name="password" autocomplete="new-password" required minlength="6">
        </label>
        <label class="f">Vai trò
          <select name="vai_tro" required>
            @foreach ($vaiTro as $ma => $ten)
              <option value="{{ $ma }}" @selected(old('vai_tro') === $ma)>{{ $ten }}</option>
            @endforeach
          </select>
        </label>
        <label class="f">Đơn vị
          <input name="don_vi" value="{{ old('don_vi') }}" maxlength="255" placeholder="VD: Công ty CP Điện gió Hướng Linh 5">
        </label>
      </div>
    </div>
    <div class="sheet-ft">
      <button type="button" class="btn" onclick="document.getElementById('dlgThem').close()">Huỷ</button>
      <button class="btn primary">Thêm</button>
    </div>
  </form>
</dialog>

@if ($errors->has('name') || $errors->has('email') || $errors->has('password'))
  {{-- Chỉ tự mở lại hộp "Thêm tài khoản" khi lỗi đến từ chính form này — lỗi bảo vệ khi
       sửa/xoá (tự đổi vai trò, tự xoá chính mình...) dùng khoá lỗi riêng, không trùng đây. --}}
  <script>document.getElementById('dlgThem').showModal();</script>
@endif

@push('cuoi_trang')
<script>
(function () {
  var dlgDuyet = document.getElementById('dlgDuyet');
  var formDuyet = document.getElementById('formDuyet');
  var duAnDuyet = Array.prototype.slice.call(document.querySelectorAll('.du-an-duyet'));
  var canhBaoDuyet = document.getElementById('duyetCanhBao');

  Array.prototype.forEach.call(document.querySelectorAll('[data-duyet]'), function (btn) {
    btn.addEventListener('click', function () {
      var taiKhoanId = btn.dataset.taiKhoanId;
      var duAnChon = JSON.parse(btn.dataset.duAnChon || '[]').map(String);
      var duAnYeuCau = JSON.parse(btn.dataset.duAnYeuCau || '[]').map(String);
      var duAnKhaDung = [];

      formDuyet.action = btn.dataset.action;
      document.getElementById('duyetTen').textContent = btn.dataset.ten;
      duAnDuyet.forEach(function (hang) {
        var chuDauTuId = hang.dataset.chuDauTuId;
        var khaDung = !chuDauTuId || chuDauTuId === taiKhoanId;
        var oChon = hang.querySelector('input');
        hang.hidden = !khaDung;
        hang.style.display = khaDung ? 'flex' : 'none';
        oChon.checked = khaDung && duAnChon.indexOf(oChon.value) >= 0;
        if (khaDung) duAnKhaDung.push(oChon.value);
      });
      var yeuCauKhongKhaDung = duAnYeuCau.some(function (id) { return duAnKhaDung.indexOf(id) < 0; });
      canhBaoDuyet.hidden = !yeuCauKhongKhaDung;
      canhBaoDuyet.textContent = yeuCauKhongKhaDung
        ? 'Một số dự án được yêu cầu đã được gán cho người khác hoặc không còn khả dụng; hãy chọn lại nếu cần.'
        : '';
      dlgDuyet.showModal();
    });
  });

  var dlgSua = document.getElementById('dlgSua');
  var formSua = document.getElementById('formSua');

  Array.prototype.forEach.call(document.querySelectorAll('[data-sua]'), function (btn) {
    btn.addEventListener('click', function () {
      formSua.action = btn.dataset.action;
      document.getElementById('suaName').value = btn.dataset.name || '';
      document.getElementById('suaEmail').value = btn.dataset.email || '';
      document.getElementById('suaVaiTro').value = btn.dataset.vaiTro || '';
      document.getElementById('suaDonVi').value = btn.dataset.donVi || '';
      dlgSua.showModal();
    });
  });

  // Hộp Phân quyền xem: đánh dấu mục nào đang khác mặc định của vai trò
  var dlgQuyen = document.getElementById('dlgQuyen');
  var formQuyen = document.getElementById('formQuyen');
  var macDinh = [];
  function capNhatQuyen() {
    Array.prototype.forEach.call(formQuyen.querySelectorAll('.pq-muc'), function (muc) {
      var khac = muc.querySelector('input').checked !== (macDinh.indexOf(muc.dataset.muc) >= 0);
      muc.querySelector('.pq-khac').hidden = !khac || muc.hidden;
    });
  }
  formQuyen.addEventListener('change', capNhatQuyen);
  Array.prototype.forEach.call(document.querySelectorAll('[data-quyen]'), function (btn) {
    btn.addEventListener('click', function () {
      var hien = JSON.parse(btn.dataset.quyenHien || '[]');
      macDinh = JSON.parse(btn.dataset.macDinh || '[]');
      formQuyen.action = btn.dataset.action;
      document.getElementById('quyenTen').textContent = btn.dataset.ten;
      document.getElementById('quyenVaiTro').textContent = btn.dataset.tenVaiTro;
      Array.prototype.forEach.call(formQuyen.querySelectorAll('.pq-muc'), function (muc) {
        muc.querySelector('input').checked = hien.indexOf(muc.dataset.muc) >= 0;
        // "Xem tất cả dự án" chỉ có nghĩa với chủ đầu tư — vai trò khác luôn thấy hết dự án
        muc.hidden = muc.dataset.muc === 'tat_ca_du_an' && btn.dataset.vaiTro !== 'chu_dau_tu';
      });
      capNhatQuyen();
      dlgQuyen.showModal();
    });
  });
})();
</script>
@endpush
@endsection
