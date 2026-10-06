<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Đăng nhập — Quản lý dự án công nghiệp</title>
<link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
</head>
<body>
<div class="dangnhap">
  <form method="post" action="{{ route('dang-nhap') }}" class="sheet">
    @csrf
    <div class="sheet-hd">
      <h2>Quản lý dự án công nghiệp</h2>
      <p class="hint">Sở Công Thương Quảng Trị</p>
    </div>
    <div class="sheet-bd">
      @if (session('thong_bao'))
        <div class="flash" style="margin:0 0 12px">{{ session('thong_bao') }}</div>
      @endif
      @if ($errors->any())
        <div class="loi">{{ $errors->first() }}</div>
      @endif
      <div class="grid">
        <label class="f full">Thư điện tử
          <input type="email" name="email" value="{{ old('email') }}" required autofocus>
        </label>
        <label class="f full">Mật khẩu
          <input type="password" name="password" required>
        </label>
        <label class="full" style="display:flex;gap:7px;align-items:center;font-size:13px">
          <input type="checkbox" name="ghi_nho" value="1"> Ghi nhớ đăng nhập
        </label>
      </div>
    </div>
    <div class="sheet-ft" style="justify-content:space-between;align-items:center">
      <a href="{{ route('dang-ky') }}" class="hint">Là chủ đầu tư, chưa có tài khoản? Đăng ký ›</a>
      <button class="btn primary">Đăng nhập</button>
    </div>
  </form>
</div>
</body>
</html>
