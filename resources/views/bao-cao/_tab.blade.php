@php
  // Thanh chuyển giữa báo cáo tuần / tháng / cài đặt. Chủ đầu tư chỉ thấy loại báo cáo Sở đặt cho
  // mình (hoặc đã từng lập); quản trị thấy thêm số báo cáo chờ tiếp nhận của từng loại.
  $nd = auth()->user();
  $laCdt = $nd->vai_tro === 'chu_dau_tu';
  $coTheXuLy = in_array($nd->vai_tro, ['quan_tri', 'nhap_lieu']);
  $tabBc = [];
  if (! $laCdt || $nd->phaiBaoCaoTuan() || $nd->baoCaoTuan()->exists()) {
    $tabBc['bao-cao.index'] = ['Báo cáo tuần', $coTheXuLy ? \App\Models\BaoCaoTuan::where('trang_thai', 'da_nop')->count() : 0];
  }
  if (! $laCdt || $nd->phaiBaoCaoThang() || $nd->baoCaoThang()->exists()) {
    $tabBc['bao-cao.thang.index'] = ['Báo cáo tháng', $coTheXuLy ? \App\Models\BaoCaoThang::where('trang_thai', 'da_nop')->count() : 0];
  }
  // Sản lượng điện tháng: chủ đầu tư có dự án nguồn điện đã vận hành
  $coSanLuong = ! $laCdt || $nd->duAnVanHanh()->exists();
  if ($coSanLuong) {
    $tabBc['bao-cao.san-luong.index'] = ['Sản lượng điện', 0];
  }
  if (! $laCdt) {
    $tabBc['bao-cao.thong-ke'] = ['Thống kê báo cáo', 0];
  }
  if ($coSanLuong) {
    $tabBc['bao-cao.san-luong.thong-ke'] = ['Thống kê sản lượng', 0];
  }
  if ($nd->vai_tro === 'quan_tri') {
    $tabBc['bao-cao.cai-dat'] = ['Cài đặt báo cáo', 0];
  }
  $iconTab = ['bao-cao.index' => 'tuan', 'bao-cao.thang.index' => 'thang', 'bao-cao.thong-ke' => 'thong-ke', 'bao-cao.cai-dat' => 'cai-dat',
    'bao-cao.san-luong.index' => 'san-luong', 'bao-cao.san-luong.thong-ke' => 'bieu-do'];
@endphp
@if (count($tabBc) > 1)
  <nav class="bc-tab" aria-label="Loại báo cáo">
    @foreach ($tabBc as $r => [$ten, $dem])
      <a href="{{ route($r) }}" class="{{ $dangChon === $r ? 'chon' : '' }}">@include('bao-cao._icon', ['ten' => $iconTab[$r] ?? 'lich', 'co' => 17])<span>{{ $ten }}</span>@if ($dem)<span class="nav-dem" title="Chờ tiếp nhận">{{ $dem }}</span>@endif</a>
    @endforeach
  </nav>
@endif
