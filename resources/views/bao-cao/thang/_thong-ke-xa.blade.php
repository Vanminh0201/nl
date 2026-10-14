{{--
  Phần "Thống kê các dự án năng lượng trên địa bàn các xã" của báo cáo tháng đúng như mẫu (trang in): thống kê theo loại
  hình, danh sách dự án trên địa bàn từ 2 xã trở lên. Trang quản lý dùng bản trình bày riêng (_thong-ke-tong-quan).
  Biến: $bang (TongHopBaoCaoThang); $lopBang (class bảng).
--}}
@php
  $lopBang = $lopBang ?? 'bct-bang';
  $chuaRo = \App\Services\XuatExcelBaoCaoThang::coCotChuaRo($bang);
  $tong = $bang['thong_ke']['tong'];
@endphp
<h3 class="bct-muc">1. Thống kê theo loại hình</h3>
<table class="{{ $lopBang }} bct-thong-ke">
  <thead>
    <tr><th style="width:52px">STT</th><th>Loại hình</th><th>Trong 1 xã</th><th>Từ 2 xã trở lên</th>@if ($chuaRo)<th>Chưa xác định xã</th>@endif</tr>
  </thead>
  <tbody>
  @forelse ($bang['thong_ke']['hang'] as $i => $h)
    <tr>
      <td class="giua">{{ $i + 1 }}</td><td>{{ $h['loai_hinh'] }}</td>
      <td class="giua">{{ $h['mot_xa'] }}</td><td class="giua">{{ $h['nhieu_xa'] }}</td>@if ($chuaRo)<td class="giua">{{ $h['chua_ro'] }}</td>@endif
    </tr>
  @empty
    <tr><td colspan="{{ $chuaRo ? 5 : 4 }}" class="trong">Chưa có dự án nào.</td></tr>
  @endforelse
    <tr class="bct-tong">
      <td></td><td>Tổng cộng</td><td class="giua">{{ $tong['mot_xa'] }}</td><td class="giua">{{ $tong['nhieu_xa'] }}</td>@if ($chuaRo)<td class="giua">{{ $tong['chua_ro'] }}</td>@endif
    </tr>
  </tbody>
</table>

<h3 class="bct-muc">2. Danh sách dự án trên địa bàn từ 2 xã trở lên</h3>
<table class="{{ $lopBang }} bct-thong-ke">
  <thead><tr><th style="width:52px">STT</th><th>Tên dự án</th><th>Các xã, phường</th></tr></thead>
  <tbody>
  @forelse ($bang['nhieu_xa'] as $i => $nhom)
    <tr class="bct-xa-dau"><td colspan="3">{{ \App\Models\BaoCaoTuan::soLaMa($i + 1) }}. {{ $nhom['ten'] }}</td></tr>
    @foreach ($nhom['muc'] as $m)
      <tr><td class="giua">{{ $m['stt'] }}</td><td>{{ $m['ten'] }}</td><td>{{ $m['xa'] }}</td></tr>
    @endforeach
  @empty
    <tr><td colspan="3" class="trong">Không có dự án nào trên địa bàn từ 2 xã trở lên.</td></tr>
  @endforelse
  </tbody>
</table>
