{{--
  Phần "Thống kê các dự án năng lượng trên địa bàn các xã" trên trang quản lý báo cáo tháng: 4 ô số liệu, bảng theo loại
  hình (thêm cột tổng và thanh cơ cấu) và danh sách dự án trên địa bàn từ 2 xã trở lên — 2 bảng đặt cạnh nhau trên màn
  hình rộng. Trang in, Word, Excel giữ đúng mẫu (_thong-ke-xa). Biến: $bang (TongHopBaoCaoThang).
--}}
@php
  $tong = $bang['thong_ke']['tong'];
  $soDuAn = $tong['mot_xa'] + $tong['nhieu_xa'] + $tong['chua_ro'];
  $tyLe = fn (int $so) => ($soDuAn ? number_format($so / $soDuAn * 100, 1, ',', '.') : '0').'% tổng số dự án';
  $nhom = ['mot_xa' => ['tk-mot-xa', 'Trong 1 xã'], 'nhieu_xa' => ['tk-nhieu-xa', 'Từ 2 xã trở lên']]
    + ($tong['chua_ro'] ? ['chua_ro' => ['tk-chua-ro', 'Chưa xác định xã']] : []);
  $hang = array_map(fn ($h) => $h + ['tong' => $h['mot_xa'] + $h['nhieu_xa'] + $h['chua_ro']], $bang['thong_ke']['hang']);
  $lonNhat = max([1, ...array_column($hang, 'tong')]);
  $oSo = [
    ['k-tong', 'san-luong', $soDuAn, 'Tổng số dự án', count($hang).' loại hình'],
    ['k-xong', 'ghim', $tong['mot_xa'], 'Trong 1 xã', $tyLe($tong['mot_xa'])],
    ['k-cho', 'ban-do', $tong['nhieu_xa'], 'Từ 2 xã trở lên', $tyLe($tong['nhieu_xa'])],
    ['k-chua', 'ghim-hoi', $tong['chua_ro'], 'Chưa xác định xã', $tyLe($tong['chua_ro'])],
  ];
@endphp
<div class="bc-kpi bct-tk-kpi">
  @foreach ($oSo as [$lop, $icon, $so, $nhan, $phan])
    <div class="bc-kpi-o {{ $lop }} {{ $so ? '' : 'rong' }}">
      <span class="bc-kpi-icon">@include('bao-cao._icon', ['ten' => $icon, 'co' => 21])</span>
      <div class="bc-kpi-noi"><b>{{ $so }}</b><span>{{ $nhan }}</span>@if ($phan)<small>{{ $phan }}</small>@endif</div>
    </div>
  @endforeach
</div>

<div class="bct-tk-luoi">
  <section>
    <h3 class="bct-muc">1. Thống kê theo loại hình</h3>
    <div class="bct-tk-cuon">
      <table class="bct-bang bct-tk-bang">
        <thead>
          <tr>
            <th class="bct-tt">STT</th><th class="bct-tk-ten">Loại hình</th>
            @foreach ($nhom as [$lop, $nhan])<th class="so">{{ $nhan }}</th>@endforeach
            <th class="so">Tổng</th><th class="bct-tk-co-cau">Cơ cấu</th>
          </tr>
        </thead>
        <tbody>
        @forelse ($hang as $i => $h)
          <tr>
            <td class="bct-tt">{{ $i + 1 }}</td>
            <td class="bct-tk-ten">{{ $h['loai_hinh'] }}</td>
            @foreach ($nhom as $cot => $_)<td class="so {{ $h[$cot] ? '' : 'so-0' }}">{{ $h[$cot] }}</td>@endforeach
            <td class="so"><b>{{ $h['tong'] }}</b></td>
            <td class="bct-tk-co-cau">
              <span class="bct-tk-thanh" style="width:{{ round($h['tong'] / $lonNhat * 100, 1) }}%"
                    title="{{ collect($nhom)->map(fn ($n, $cot) => $n[1].': '.$h[$cot])->implode(' · ') }}">
                @foreach ($nhom as $cot => [$lop])
                  @if ($h[$cot])<i class="{{ $lop }}" style="flex:{{ $h[$cot] }}"></i>@endif
                @endforeach
              </span>
            </td>
          </tr>
        @empty
          <tr><td colspan="{{ count($nhom) + 4 }}" class="trong">Chưa có dự án nào.</td></tr>
        @endforelse
        </tbody>
        <tfoot>
          <tr class="bct-tong">
            <td></td><td>Tổng cộng</td>
            @foreach ($nhom as $cot => $_)<td class="so">{{ $tong[$cot] }}</td>@endforeach
            <td class="so">{{ $soDuAn }}</td><td></td>
          </tr>
        </tfoot>
      </table>
    </div>
    <div class="bct-tk-chu-giai">
      @foreach ($nhom as [$lop, $nhan])<span><i class="{{ $lop }}"></i>{{ $nhan }}</span>@endforeach
    </div>
  </section>

  <section>
    <h3 class="bct-muc">2. Danh sách dự án trên địa bàn từ 2 xã trở lên</h3>
    <div class="bct-tk-cuon">
      <table class="bct-bang bct-tk-bang">
        <thead><tr><th class="bct-tt">STT</th><th class="bct-tk-ten">Tên dự án</th><th>Các xã, phường</th></tr></thead>
        <tbody>
        @forelse ($bang['nhieu_xa'] as $i => $n)
          <tr class="bct-tk-nhom"><td colspan="3">{{ \App\Models\BaoCaoTuan::soLaMa($i + 1) }}. {{ $n['ten'] }}</td></tr>
          @foreach ($n['muc'] as $m)
            <tr>
              <td class="bct-tt">{{ $m['stt'] }}</td>
              <td class="bct-tk-ten">{{ $m['ten'] }}</td>
              <td><span class="bct-tk-xa">@foreach (explode(', ', $m['xa']) as $x)<span>{{ $x }}</span>@endforeach</span></td>
            </tr>
          @endforeach
        @empty
          <tr><td colspan="3" class="trong">Không có dự án nào trên địa bàn từ 2 xã trở lên.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </section>
</div>
