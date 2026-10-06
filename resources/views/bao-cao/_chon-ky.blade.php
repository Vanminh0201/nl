{{-- Ô chọn kỳ báo cáo: nút kỳ trước / kỳ sau và danh sách kỳ nhóm theo tháng (hoặc năm). Mở bằng <details>
     nên vẫn chọn được khi trình duyệt tắt JS; mỗi kỳ kèm số đơn vị đã gửi / phải gửi. --}}
<div class="bc-ky-chon">
  <a href="{{ $truoc['url'] }}" class="bc-ky-buoc" title="{{ $truoc['ten'] }}" aria-label="{{ $truoc['ten'] }}">@include('bao-cao._icon', ['ten' => 'trai'])</a>
  <details class="bc-ky-hop">
    <summary title="Chọn kỳ khác">
      @include('bao-cao._icon', ['ten' => 'lich', 'co' => 20])
      <span class="bc-ky-chu"><b>{{ $ten }}</b><small>{{ $phu }}</small></span>
      <span class="bc-ky-mui">@include('bao-cao._icon', ['ten' => 'xuong', 'co' => 16])</span>
    </summary>
    <div class="bc-ky-ds">
      @foreach ($nhom as $tenNhom => $dsMuc)
        <div class="bc-ky-nhom">{{ $tenNhom }}</div>
        @foreach ($dsMuc as $m)
          @php
            $th = $m['tinh_hinh'];
            $lopDem = ! $th || ! $th['phai'] ? '' : ($th['gui'] >= $th['phai'] ? 'du' : ($m['hien_tai'] ? 'dang' : 'thieu'));
          @endphp
          <a href="{{ $m['url'] }}" class="bc-ky-muc {{ $m['chon'] ? 'chon' : '' }}" @if ($m['chon']) aria-current="true" @endif>
            <b>{{ $m['ten'] }}</b>
            <span class="bc-ky-khoang">{{ $m['phu'] }}</span>
            @if ($th && $th['phai'])
              <i class="bc-ky-dem {{ $lopDem }}" title="Đã gửi {{ $th['gui'] }}/{{ $th['phai'] }} báo cáo">{{ $th['gui'] }}/{{ $th['phai'] }}</i>
            @endif
          </a>
        @endforeach
      @endforeach
    </div>
  </details>
  @if ($sau)
    <a href="{{ $sau['url'] }}" class="bc-ky-buoc" title="{{ $sau['ten'] }}" aria-label="{{ $sau['ten'] }}">@include('bao-cao._icon', ['ten' => 'phai'])</a>
  @else
    <span class="bc-ky-buoc tat" aria-disabled="true">@include('bao-cao._icon', ['ten' => 'phai'])</span>
  @endif
</div>

@pushOnce('cuoi_trang')
<script>
// Ô chọn kỳ: mở thì cuộn tới kỳ đang chọn; đóng khi bấm ra ngoài hoặc nhấn Esc; phím mũi tên lên/xuống để chọn
(function () {
  var dsHop = Array.prototype.slice.call(document.querySelectorAll('.bc-ky-hop'));
  dsHop.forEach(function (d) {
    var ds = d.querySelector('.bc-ky-ds');
    d.addEventListener('toggle', function () {
      var chon = d.querySelector('.bc-ky-muc.chon');
      if (d.open && ds && chon) ds.scrollTop = chon.offsetTop - ds.clientHeight / 2 + chon.offsetHeight / 2;
    });
    d.addEventListener('keydown', function (e) {
      if (!d.open || (e.key !== 'ArrowDown' && e.key !== 'ArrowUp')) return;
      var muc = Array.prototype.slice.call(d.querySelectorAll('.bc-ky-muc'));
      var i = muc.indexOf(document.activeElement);
      if (i < 0) i = muc.indexOf(d.querySelector('.bc-ky-muc.chon'));
      i = e.key === 'ArrowDown' ? Math.min(muc.length - 1, i + 1) : Math.max(0, i - 1);
      if (muc[i]) { muc[i].focus(); e.preventDefault(); }
    });
  });
  document.addEventListener('click', function (e) {
    dsHop.forEach(function (d) { if (d.open && !d.contains(e.target)) d.open = false; });
  });
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    dsHop.forEach(function (d) { if (d.open) { d.open = false; d.querySelector('summary').focus(); } });
  });
})();
</script>
@endPushOnce
