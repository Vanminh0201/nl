@extends('layouts.app')
@section('tieu_de', 'Quản lý báo cáo tháng')

@php
  $coTheXuLy = in_array(auth()->user()->vai_tro, ['quan_tri', 'nhap_lieu']);
@endphp

@section('noi_dung')
<div class="pad bc-trang">
  @include('bao-cao._tab', ['dangChon' => 'bao-cao.thang.index'])
  @php
    // Ô chọn tháng: nhóm theo năm, tháng mới nhất ở trên
    $nhomKy = [];
    foreach (array_reverse($dsKyChon) as $k) {
      $nhomKy['Năm '.$k->nam][] = [
        'url' => route('bao-cao.thang.index', ['ky' => $k->ma()]),
        'ten' => 'Tháng '.$k->thang,
        'phu' => 'Hạn gửi '.$k->hanNop()->format('d/m'),
        'chon' => $k->bang($ky),
        'hien_tai' => $k->laHienTai(),
        'tinh_hinh' => $tinhHinhKy[$k->ma()] ?? null,
      ];
    }
    $oKpi = [
      ['k-tong', 'don-vi', $dem['phai_nop'], 'Chủ đầu tư phải báo cáo'],
      ['k-cho', 'cho', $dem['cho'], 'Chờ tiếp nhận'],
      ['k-xong', 'xong', $dem['tiep_nhan'], 'Đã tiếp nhận'],
      ['k-bo-sung', 'bo-sung', $dem['bo_sung'], 'Yêu cầu bổ sung'],
      ['k-chua', 'chua', $dem['chua_nop'], 'Chưa gửi'],
    ];
  @endphp
  <div class="bc-thanh-ky bc-ky">
    @include('bao-cao._chon-ky', [
      'ten' => $ky->ten(),
      'phu' => 'Hạn gửi '.$ky->hanNop()->format('d/m/Y'),
      'truoc' => ['url' => route('bao-cao.thang.index', ['ky' => $ky->truoc()->ma()]), 'ten' => 'Tháng trước'],
      'sau' => $ky->laHienTai() ? null : ['url' => route('bao-cao.thang.index', ['ky' => $ky->sau()->ma()]), 'ten' => 'Tháng sau'],
      'nhom' => $nhomKy,
    ])
    @unless ($ky->laHienTai())
      <a href="{{ route('bao-cao.thang.index') }}" class="btn bc-nut">Về tháng này</a>
    @endunless
    <span class="bc-ky-gian"></span>
    <a href="{{ route('bao-cao.thang.tong-hop-in', ['ky' => $ky->ma()]) }}" class="btn bc-nut" target="_blank" rel="noopener">@include('bao-cao._icon', ['ten' => 'in'])In bảng tổng hợp</a>
    <a href="{{ route('bao-cao.thang.tong-hop-excel', ['ky' => $ky->ma()]) }}" class="btn bc-nut">@include('bao-cao._icon', ['ten' => 'tai'])Tải Excel</a>
    <a href="{{ route('bao-cao.thang.tong-hop-word', ['ky' => $ky->ma()]) }}" class="btn primary bc-nut">@include('bao-cao._icon', ['ten' => 'tai'])Tải Word theo mẫu</a>
  </div>

  <div class="bc-kpi">
    @foreach ($oKpi as [$lop, $icon, $soLuong, $nhan])
      <div class="bc-kpi-o {{ $lop }} {{ $soLuong ? '' : 'rong' }}">
        <span class="bc-kpi-icon">@include('bao-cao._icon', ['ten' => $icon, 'co' => 21])</span>
        <div class="bc-kpi-noi">
          <b>{{ $soLuong }}</b><span>{{ $nhan }}</span>
        </div>
      </div>
    @endforeach
  </div>

  @if ($choTiepNhanKyKhac->isNotEmpty())
    <div class="bc-nhac">
      <b>Báo cáo các tháng khác đang chờ tiếp nhận:</b>
      @foreach ($choTiepNhanKyKhac as $b)
        <a href="{{ route('bao-cao.thang.show', $b) }}" class="chip xanh">{{ $b->ten_don_vi_hien_thi }} — {{ $b->ky()->ten() }}</a>
      @endforeach
    </div>
  @endif

  <div class="sheet">
    <div class="sheet-hd bc-phan-hd">
      <div class="bc-phan-giua">
        <h2>Tình hình gửi báo cáo {{ mb_strtolower($ky->ten()) }}</h2>
      </div>
      @if ($coTheXuLy)
        <form method="post" action="{{ route('bao-cao.thang.xu-ly-hang-loat') }}" id="bc-form-hang-loat" class="bc-phan-phai">
          @csrf
          <button type="submit" class="btn primary" id="bc-nut-hang-loat" disabled>Tiếp nhận đã chọn (<span id="bc-so-chon">0</span>)</button>
        </form>
      @endif
    </div>
    <div class="tablewrap" style="border:0;border-radius:0">
      <table class="bc-nop">
        <colgroup>
          @if ($coTheXuLy)<col style="width:2.6%">@endif
          <col style="width:3.6%"><col style="width:17%"><col style="width:25%"><col style="width:10.5%">
          <col style="width:9%"><col style="width:14%"><col style="width:9%"><col style="width:9.3%">
        </colgroup>
        <thead>
          <tr>
            @if ($coTheXuLy)<th class="giua"><input type="checkbox" id="bc-chon-tat-ca" title="Chọn tất cả đang chờ tiếp nhận"></th>@endif
            <th class="giua">STT</th><th>Chủ đầu tư</th><th>Dự án được gán</th><th class="giua">Trạng thái</th>
            <th class="giua">Thời điểm gửi</th><th>Xử lý</th><th class="giua">Thao tác</th><th class="giua">Báo cáo gần nhất</th>
          </tr>
        </thead>
        <tbody>
        @forelse ($hang as $i => $h)
          @php
            $u = $h['nguoi']; $b = $h['bao_cao']; $choTiepNhan = $b && $b->trang_thai === 'da_nop';
            [$ngayNop, $gioNop] = array_pad(explode(' ', (string) $b?->gio('nop_luc'), 2), 2, '');
          @endphp
          <tr class="{{ $choTiepNhan ? 'bc-hang-cho' : '' }}">
            @if ($coTheXuLy)
              <td class="giua">@if ($choTiepNhan)<input type="checkbox" class="bc-chon-dong" value="{{ $b->id }}">@endif</td>
            @endif
            <td class="giua">{{ $i + 1 }}</td>
            <td>
              <div class="bc-nop-dv">{{ $u->don_vi ?: $u->name }}</div>
              <div class="bc-nop-lh">{{ $u->don_vi ? $u->name.' · ' : '' }}{{ $u->email }}</div>
            </td>
            <td>
              @if ($u->duAn->isNotEmpty())
                <ul class="bc-nop-da">@foreach ($u->duAn as $d)<li>{{ $d->ten }}</li>@endforeach</ul>
              @else
                <span class="rong">—</span>
              @endif
            </td>
            <td class="giua">
              @if ($b)
                <span class="chip {{ $b->lop_chip }}">{{ $b->ten_trang_thai }}</span>
              @elseif ($ky->somHon($h['bat_dau']))
                <span class="chip" title="Kỳ trước mốc áp dụng không tính là chưa gửi — đổi mốc ở Cài đặt báo cáo">Áp dụng từ {{ mb_strtolower($h['bat_dau']->ten()) }}</span>
              @else
                <span class="chip {{ $ky->quaHan() ? 'do' : '' }}">{{ $ky->quaHan() ? 'Quá hạn, chưa gửi' : 'Chưa gửi' }}</span>
              @endif
            </td>
            <td class="giua bc-nop-tg">
              @if ($ngayNop !== '')<b>{{ $ngayNop }}</b><span>{{ $gioNop }}</span>@else<span class="rong">—</span>@endif
            </td>
            <td class="bc-nop-xl">
              @if ($b?->xu_ly_luc)
                <b>{{ $b->nguoiXuLy?->name }}</b><span>{{ $b->gio('xu_ly_luc') }}</span>
                @if ($b->y_kien_xu_ly)<p>{{ $b->y_kien_xu_ly }}</p>@endif
              @else
                <span class="rong">—</span>
              @endif
            </td>
            <td class="giua">
              @if ($b)
                <div class="bc-nop-tt">
                  @if ($coTheXuLy && $b->trang_thai === 'da_nop')
                    <a href="{{ route('bao-cao.thang.show', $b) }}" class="btn sm primary">Xem &amp; xử lý</a>
                  @else
                    <a href="{{ route('bao-cao.thang.show', $b) }}" class="btn sm">Xem</a>
                  @endif
                  <a href="{{ route('bao-cao.thang.excel', $b) }}" class="btn sm">Excel</a>
                </div>
              @else
                <span class="rong">—</span>
              @endif
            </td>
            <td class="giua bc-nop-gn">
              @if ($gn = $h['gan_nhat'])
                <a href="{{ route('bao-cao.thang.show', $gn->id) }}" title="Xem nội dung báo cáo">{{ $gn->ky()->ten() }}</a>
                <span class="chip {{ $gn->lop_chip }}">{{ $gn->ten_trang_thai }}</span>
              @else
                <span class="rong">Chưa gửi báo cáo nào</span>
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="9" class="trong">Chưa có chủ đầu tư nào phải báo cáo tháng — gán dự án cho tài khoản chủ đầu tư ở màn hình sửa dự án và bật "Báo cáo tháng" ở Cài đặt báo cáo.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="sheet" id="tong-hop">
    <div class="sheet-hd bc-phan-hd">
      <div class="bc-phan-giua">
        <h2>Tình hình triển khai các dự án năng lượng — {{ mb_strtolower($ky->ten()) }}</h2>
        <div class="hint">
          {{ $bang['so_du_an'] }} dự án, nhóm theo xã/phường hiện nay (dự án trên nhiều xã ghi lại ở mỗi xã). Nội dung mỗi dự án lấy theo thứ tự:
          Sở biên tập tháng này → báo cáo chủ đầu tư gửi tháng này → nội dung gần nhất các tháng trước.
        </div>
      </div>
    </div>
    <div class="bct-loc">
      <input type="search" placeholder="Tìm dự án, chủ đầu tư…" aria-label="Tìm dự án hoặc chủ đầu tư" data-th-tim>
      <label><input type="checkbox" data-th-an-trong> Ẩn dự án chưa có nội dung</label>
      <span class="hint" data-th-dem></span>
    </div>
    @include('bao-cao.thang._bang', ['bang' => $bang, 'ky' => $ky, 'hienNguon' => true, 'coTheSua' => $coTheSua])
  </div>

  <div class="sheet">
    <div class="sheet-hd bc-phan-hd">
      <div class="bc-phan-giua"><h2>Thống kê các dự án năng lượng trên địa bàn các xã</h2></div>
    </div>
    <div class="sheet-bd bct-thong-ke-khung">@include('bao-cao.thang._thong-ke-tong-quan', ['bang' => $bang])</div>
  </div>

  @if ($coTheSua)
    @php
      // Nội dung hiện tại của từng dự án, để mở hộp biên tập điền sẵn
      $noiDungSua = collect($bang['xa'])->flatMap(fn ($xa) => collect($xa['nhom'])->flatMap(fn ($n) => $n['muc']))
        ->unique(fn ($m) => $m['du_an']->id)
        ->mapWithKeys(fn ($m) => [$m['du_an']->id => ['ten' => $m['ten'], 'so' => $m['nguon'] === 'so' && $m['ky_nguon']->bang($ky)] + $m['noi_dung']]);
    @endphp
    <dialog id="thSua" class="th-sua" aria-labelledby="thSuaTen">
      <form method="post" data-url="{{ route('bao-cao.thang.tong-hop-luu', ['duAn' => '__ID__']) }}">
        @csrf
        <input type="hidden" name="ky" value="{{ $ky->ma() }}">
        <div class="th-sua-dau">
          <div>
            <span class="th-sua-nhan">Biên tập nội dung · {{ mb_strtolower($ky->ten()) }}</span>
            <h3 id="thSuaTen"></h3>
          </div>
          <button type="button" class="btn" data-dong-sua>Đóng</button>
        </div>
        <div class="th-sua-than">
          @foreach (\App\Models\BaoCaoThangDuAn::NOI_DUNG_MAU as $cot => $nhan)
            <label class="f {{ in_array($cot, ['du_kien_khoi_cong', 'du_kien_hoan_thanh'], true) ? 'th-sua-ngan' : '' }}">{{ $nhan }}
              <textarea name="{{ $cot }}" rows="{{ $cot === 'tinh_hinh' ? 6 : (in_array($cot, ['du_kien_khoi_cong', 'du_kien_hoan_thanh'], true) ? 2 : 4) }}" maxlength="10000"></textarea>
            </label>
          @endforeach
          <p class="hint" style="margin:0">Nội dung lưu ở đây dùng cho bảng tổng hợp {{ mb_strtolower($ky->ten()) }} và các tháng sau (cho tới khi có báo cáo mới của chủ đầu tư).
            Xoá trắng cả 6 ô rồi lưu để bỏ phần Sở biên tập, dùng lại nội dung chủ đầu tư báo cáo.</p>
        </div>
        <div class="th-sua-chan">
          <button type="button" class="btn" data-dong-sua>Huỷ</button>
          <button class="btn primary">Lưu nội dung</button>
        </div>
      </form>
    </dialog>
    <script type="application/json" id="thNoiDung">@json($noiDungSua)</script>
  @endif

  <div class="sheet">
    <div class="sheet-hd bc-phan-hd">
      <div class="bc-phan-giua">
      <h2>Theo dõi gửi báo cáo {{ count($cacKy) }} tháng gần nhất</h2>
      <div class="bc-chu-giai hint">
        <span><i class="o-ky co">✓</i> Đã tiếp nhận</span>
        <span><i class="o-ky xanh">●</i> Chờ tiếp nhận</span>
        <span><i class="o-ky do">!</i> Yêu cầu bổ sung</span>
        <span><i class="o-ky thieu">✕</i> Không gửi</span>
      </div>
      </div>
    </div>
    <div class="tablewrap" style="border:0;border-radius:0">
      <table class="bc-ma-tran">
        <thead>
          <tr>
            <th>Chủ đầu tư</th>
            @foreach ($cacKy as $k)
              <th><a href="{{ route('bao-cao.thang.index', ['ky' => $k->ma()]) }}">T{{ $k->thang }}/{{ $k->nam % 100 }}</a></th>
            @endforeach
            <th class="num">Tỷ lệ gửi</th>
          </tr>
        </thead>
        <tbody>
        @forelse ($hang as $h)
          @php
            $u = $h['nguoi'];
            $cua = $theoDoi->get($u->id, collect());
            $tinh = array_filter($cacKy, fn ($k) => $cua->has($k->ma()) || (! $k->laHienTai() && ! $k->somHon($h['bat_dau'])));
          @endphp
          <tr>
            <td>{{ $u->don_vi ?: $u->name }}</td>
            @foreach ($cacKy as $k)
              @php $b = $cua->get($k->ma()); @endphp
              <td>
                @if ($b)
                  <a href="{{ route('bao-cao.thang.show', $b->id) }}" class="o-ky {{ $b->lop_chip }}" title="{{ $k->ten() }}: {{ $b->ten_trang_thai }}">{{ ['da_tiep_nhan' => '✓', 'da_nop' => '●', 'yeu_cau_bo_sung' => '!'][$b->trang_thai] ?? '·' }}</a>
                @elseif ($k->somHon($h['bat_dau']))
                  <span class="o-ky na" title="{{ $k->ten() }}: chưa áp dụng báo cáo tháng">–</span>
                @elseif ($k->laHienTai())
                  <span class="o-ky" title="{{ $k->ten() }}: chưa gửi (tháng đang diễn ra)">·</span>
                @else
                  <span class="o-ky thieu" title="{{ $k->ten() }}: không gửi">✕</span>
                @endif
              </td>
            @endforeach
            <td class="num">{{ count($tinh) ? $cua->count().'/'.count($tinh) : '—' }}</td>
          </tr>
        @empty
          <tr><td colspan="{{ count($cacKy) + 2 }}" class="trong">—</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection

@push('cuoi_trang')
<script>
(function () {
  var chonTatCa = document.getElementById('bc-chon-tat-ca');
  var dsChon = Array.prototype.slice.call(document.querySelectorAll('.bc-chon-dong'));
  var nutHangLoat = document.getElementById('bc-nut-hang-loat');
  var soChonEl = document.getElementById('bc-so-chon');
  var form = document.getElementById('bc-form-hang-loat');
  if (!dsChon.length || !form) return;

  function capNhat() {
    var chon = dsChon.filter(function (c) { return c.checked; });
    soChonEl.textContent = chon.length;
    nutHangLoat.disabled = chon.length === 0;
    if (chonTatCa) chonTatCa.checked = chon.length === dsChon.length;
  }
  dsChon.forEach(function (c) { c.addEventListener('change', capNhat); });
  if (chonTatCa) {
    chonTatCa.addEventListener('change', function () {
      dsChon.forEach(function (c) { c.checked = chonTatCa.checked; });
      capNhat();
    });
  }
  form.addEventListener('submit', function (e) {
    var chon = dsChon.filter(function (c) { return c.checked; });
    if (! chon.length) { e.preventDefault(); return; }
    if (! confirm('Tiếp nhận ' + chon.length + ' báo cáo đã chọn?')) {
      e.preventDefault();
      return;
    }
    chon.forEach(function (c) {
      var h = document.createElement('input');
      h.type = 'hidden'; h.name = 'bao_cao_ids[]'; h.value = c.value;
      form.appendChild(h);
    });
  });
})();

// Bảng tổng hợp theo xã: tìm dự án / chủ đầu tư (gõ không dấu được), ẩn dự án chưa có nội dung; dòng tiêu đề xã và
// tình trạng không còn dự án nào hiện thì ẩn theo
(function () {
  var bang = document.querySelector('#tong-hop .bct-xa');
  if (!bang) return;
  var oTim = document.querySelector('[data-th-tim]'), oAn = document.querySelector('[data-th-an-trong]'), oDem = document.querySelector('[data-th-dem]');
  var boDau = function (s) { return (s || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/đ/g, 'd'); };
  var dsHang = Array.prototype.slice.call(bang.tBodies[0].rows);
  dsHang.forEach(function (tr) { if (tr.dataset.tim !== undefined) tr.dataset.timBoDau = boDau(tr.dataset.tim); });
  function apDung() {
    var tu = boDau(oTim.value.trim()), an = oAn.checked, hien = 0;
    dsHang.forEach(function (tr) {
      if (tr.dataset.duAn === undefined) return;
      tr.hidden = (tu && tr.dataset.timBoDau.indexOf(tu) < 0) || (an && tr.dataset.coNoiDung === '0');
      if (!tr.hidden) hien++;
    });
    // Duyệt ngược: tiêu đề tình trạng / xã chỉ hiện khi bên dưới (tới tiêu đề kế tiếp cùng cấp) còn dự án hiện
    var conTT = false, conXa = false;
    for (var i = dsHang.length - 1; i >= 0; i--) {
      var tr = dsHang[i];
      if (tr.dataset.duAn !== undefined) { if (!tr.hidden) { conTT = true; conXa = true; } }
      else if (tr.dataset.tinhTrang !== undefined) { tr.hidden = !conTT; conTT = false; }
      else if (tr.dataset.xa !== undefined) { tr.hidden = !conXa; conXa = false; }
    }
    oDem.textContent = (tu || an) ? 'Đang hiện ' + hien + ' dòng' : '';
  }
  oTim.addEventListener('input', apDung);
  oAn.addEventListener('change', apDung);

  var hop = document.getElementById('thSua'), duLieu = document.getElementById('thNoiDung');
  if (!hop || !duLieu || !hop.showModal) return;
  var noiDung = JSON.parse(duLieu.textContent), formSua = hop.querySelector('form');
  document.addEventListener('click', function (e) {
    var nut = e.target.closest('[data-sua-th]');
    if (nut) {
      var id = nut.dataset.suaTh, nd = noiDung[id] || {};
      formSua.action = formSua.dataset.url.replace('__ID__', id);
      hop.querySelector('#thSuaTen').textContent = nd.ten || '';
      Array.prototype.forEach.call(formSua.querySelectorAll('textarea'), function (t) { t.value = nd[t.name] || ''; });
      hop.showModal();
      formSua.querySelector('textarea[name="tinh_hinh"]').focus();
    } else if (e.target.closest('[data-dong-sua]')) {
      hop.close();
    }
  });
})();
</script>
@endpush
