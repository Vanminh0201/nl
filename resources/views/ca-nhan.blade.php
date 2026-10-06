@extends('layouts.app')
@section('tieu_de', 'Thông tin tài khoản')

@section('noi_dung')
<div class="pad cn-luoi">
  <div class="sheet">
    <div class="sheet-hd"><h2>Thông tin tài khoản</h2></div>
    <form method="post" action="{{ route('ca-nhan.cap-nhat') }}">
      @csrf @method('put')
      <div class="sheet-bd">
        @if ($errors->thongTin->any())
          <div class="loi">{{ $errors->thongTin->first() }}</div>
        @endif
        <div class="grid">
          <label class="f full">Tên
            <input name="name" required maxlength="255" value="{{ old('name', $nguoiDung->name) }}">
          </label>
          <label class="f full">Thư điện tử (dùng để đăng nhập)
            <input type="email" name="email" required maxlength="255" value="{{ old('email', $nguoiDung->email) }}" id="cnEmail"
                   data-goc="{{ $nguoiDung->email }}">
          </label>
          <label class="f full">Đơn vị
            <input name="don_vi" maxlength="255" value="{{ old('don_vi', $nguoiDung->don_vi) }}" placeholder="VD: Ban QLDA Điện 2">
          </label>
          <label class="f full" id="cnXacNhan" @unless ($errors->thongTin->has('mat_khau_hien_tai') || old('email', $nguoiDung->email) !== $nguoiDung->email) hidden @endunless>
            Mật khẩu hiện tại (xác nhận khi đổi thư điện tử)
            <input type="password" name="mat_khau_hien_tai" autocomplete="current-password">
          </label>
        </div>

        <dl class="cn-chi-tiet">
          <div class="row"><dt>Vai trò</dt><dd>{{ $nguoiDung->ten_vai_tro }}</dd></div>
          <div class="row"><dt>Được xem</dt><dd>
            @if ($nguoiDung->vai_tro === 'quan_tri')
              Toàn quyền
            @else
              <div class="pq-chips">
                @foreach ($nguoiDung->dsQuyenXem() as $ma)
                  @continue($ma === 'tat_ca_du_an' && $nguoiDung->vai_tro !== 'chu_dau_tu')
                  <span class="pq-chip">{{ $mucXem[$ma]['ten'] }}</span>
                @endforeach
              </div>
            @endif
          </dd></div>
          @if ($dsDuAn->isNotEmpty())
            <div class="row"><dt>Dự án được gán</dt><dd>{{ $dsDuAn->pluck('ten')->join('; ') }}</dd></div>
          @endif
        </dl>
        <p class="hint">Vai trò, quyền xem và dự án được gán do quản trị hệ thống cấp.</p>
      </div>
      <div class="sheet-ft"><button class="btn primary">Lưu thông tin</button></div>
    </form>
  </div>

  <div class="sheet">
    <div class="sheet-hd"><h2>Đổi mật khẩu</h2></div>
    <form method="post" action="{{ route('ca-nhan.mat-khau') }}">
      @csrf @method('put')
      <div class="sheet-bd">
        @if ($errors->matKhau->any())
          <div class="loi">{{ $errors->matKhau->first() }}</div>
        @endif
        <div class="grid">
          <label class="f full">Mật khẩu hiện tại
            <input type="password" name="mat_khau_hien_tai" required autocomplete="current-password">
          </label>
          <label class="f full">Mật khẩu mới (ít nhất 6 ký tự)
            <input type="password" name="mat_khau_moi" required minlength="6" autocomplete="new-password">
          </label>
          <label class="f full">Nhập lại mật khẩu mới
            <input type="password" name="mat_khau_moi_confirmation" required minlength="6" autocomplete="new-password">
          </label>
        </div>
      </div>
      <div class="sheet-ft"><button class="btn primary">Đổi mật khẩu</button></div>
    </form>
  </div>
</div>
@endsection

@push('cuoi_trang')
<script>
(function () {
  // Chỉ hỏi mật khẩu hiện tại khi thư điện tử (tên đăng nhập) bị đổi
  var email = document.getElementById('cnEmail');
  var xacNhan = document.getElementById('cnXacNhan');
  email.addEventListener('input', function () {
    xacNhan.hidden = email.value.trim() === email.dataset.goc;
  });
})();
</script>
@endpush
