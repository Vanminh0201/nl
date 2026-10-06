@php
  // Thông báo riêng (abort(403, '…')) bằng tiếng Việt thì hiện, thông báo mặc định tiếng Anh thì thay bằng câu dưới
  $tb = trim((string) (isset($exception) ? $exception->getMessage() : ''));
  if ($tb === '' || ! preg_match('/[^\x00-\x7F]/', $tb) || ! empty($chiMacDinh)) {
    $tb = $loi;
  }
@endphp
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $tieuDe }} — Quản lý dự án công nghiệp</title>
<style>
body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#e9ece8;
  font-family:system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif;color:#1f2a33}
.khung{box-sizing:border-box;width:calc(100% - 32px);max-width:460px;padding:28px 24px;background:#fff;border:1px solid #d6dbd5;border-radius:6px;text-align:center}
.ma{margin:0;font-size:44px;font-weight:700;line-height:1;color:#1f4b6e}
h1{margin:10px 0 10px;font-size:18px}
p{margin:0 0 20px;font-size:14px;line-height:1.55;color:#4a5866}
.nut{display:flex;gap:8px;justify-content:center;flex-wrap:wrap}
a{display:inline-block;padding:8px 16px;border-radius:4px;background:#1f4b6e;color:#fff;font-size:14px;text-decoration:none}
a.phu{background:#fff;color:#1f4b6e;border:1px solid #b7cde0}
</style>
</head>
<body>
<div class="khung">
  <p class="ma">{{ $ma }}</p>
  <h1>{{ $tieuDe }}</h1>
  <p>{{ $tb }}</p>
  <div class="nut">
    <a href="javascript:history.back()" class="phu">Quay lại</a>
    <a href="{{ url('/') }}">Về trang chính</a>
  </div>
</div>
</body>
</html>
