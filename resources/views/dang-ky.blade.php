<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Đăng ký chủ đầu tư — Quản lý dự án công nghiệp</title>
<link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
</head>
<body>
<div class="dangnhap">
  <form method="post" action="{{ route('dang-ky') }}" class="sheet">
    @csrf
    <div class="sheet-hd">
      <h2>Đăng ký tài khoản chủ đầu tư</h2>
      <p class="hint">Tài khoản sẽ chờ quản trị viên duyệt trước khi đăng nhập được.</p>
    </div>
    <div class="sheet-bd">
      @if ($errors->any())
        <div class="loi">{{ $errors->first() }}</div>
      @endif
      <div class="grid">
        <label class="f full">Họ tên
          <input name="name" value="{{ old('name') }}" required maxlength="255" autofocus>
        </label>
        <label class="f full">Thư điện tử
          <input type="email" name="email" value="{{ old('email') }}" required maxlength="255">
        </label>
        <label class="f full">Đơn vị / Nhà đầu tư
          <input name="don_vi" value="{{ old('don_vi') }}" maxlength="255" placeholder="VD: Công ty CP Điện gió Hướng Linh 5">
        </label>
        <label class="f full">Dự án bạn là chủ đầu tư (có thể chọn nhiều, không bắt buộc)
          <input type="search" id="timDuAn" placeholder="Gõ để tìm tên dự án…">
        </label>
        <div class="full ds-du-an-chon" id="dsDuAnChon">
          @forelse ($dsDuAn as $d)
            <label>
              <input type="checkbox" name="du_an_ids[]" value="{{ $d->id }}"
                     @checked(in_array($d->id, old('du_an_ids', [])))>
              <span>{{ $d->ten }}</span>
              <small>{{ $d->ten_loai_hinh }}@if ($d->dia_diem) — {{ $d->dia_diem }}@endif</small>
            </label>
          @empty
            <p class="hint" style="margin:0">Chưa có dự án nào trong hệ thống.</p>
          @endforelse
        </div>
        <label class="f">Mật khẩu
          <input type="password" name="password" required minlength="6">
        </label>
        <label class="f">Xác nhận mật khẩu
          <input type="password" name="password_confirmation" required minlength="6">
        </label>
      </div>
    </div>
    <div class="sheet-ft" style="justify-content:space-between;align-items:center">
      <a href="{{ route('dang-nhap') }}" class="hint">‹ Đã có tài khoản, đăng nhập</a>
      <button class="btn primary">Đăng ký</button>
    </div>
  </form>
</div>
<script>
(function () {
  var o = document.getElementById('timDuAn');
  var hop = document.getElementById('dsDuAnChon');
  if (!o || !hop) return;

  // Bỏ dấu để tìm không cần gõ dấu (VD gõ "gio" vẫn khớp "gió") — thói quen gõ tiếng Việt phổ biến
  function boDau(s) {
    return s.normalize('NFD').replace(/[̀-ͯ]/g, '')
      .replace(/đ/g, 'd').replace(/Đ/g, 'D').toLowerCase();
  }

  var hang = Array.prototype.slice.call(hop.querySelectorAll('label')).map(function (h) {
    return { el: h, khoa: boDau(h.textContent) };
  });
  o.addEventListener('input', function () {
    var tu = boDau(o.value.trim());
    hang.forEach(function (h) {
      h.el.style.display = h.khoa.indexOf(tu) === -1 ? 'none' : '';
    });
  });
})();
</script>
</body>
</html>
