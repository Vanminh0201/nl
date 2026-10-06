@extends('layouts.app')
@section('tieu_de', 'Danh mục loại hình và trạng thái')

@php
  // Mỗi form gửi kèm "_phan" (them-… là form thêm mới): lỗi kiểm tra dữ liệu thì mở đúng phần đó và chỉ điền lại
  // ô của form vừa gửi. Lỗi khi xoá (loai_hinh, trang_thai, bieu_tuong) không có "_phan" nên dò theo khoá lỗi.
  $phanLoi = str_replace('them-', '', (string) old('_phan')) ?: match (true) {
    $errors->has('trang_thai') => 'trang-thai',
    $errors->has('loai_hinh') => 'loai-hinh',
    $errors->has('bieu_tuong') || $errors->has('tep') || $errors->has('hinh') => 'bieu-tuong',
    default => '',
  };
  $cu = fn (string $phan, string $truong, string $mac = '') => old('_phan') === 'them-'.$phan ? old($truong, $mac) : $mac;
  $dsPhan = [
    'loai-hinh' => ['Loại hình dự án', $dsLoaiHinh->count()],
    'trang-thai' => ['Trạng thái dự án', $dsTrangThai->count()],
    'bieu-tuong' => ['Biểu tượng', $dsBieuTuong->count()],
  ];
  $nhomTrangThai = App\Models\TrangThaiDuAn::NHOM;
@endphp

@section('noi_dung')
<div class="pad dm-trang" data-phan-loi="{{ $phanLoi }}">
  <nav class="bc-tab dm-tab" aria-label="Danh mục">
    @foreach ($dsPhan as $ma => [$ten, $dem])
      <a href="#{{ $ma }}" data-phan="{{ $ma }}">@include('bao-cao._icon', ['ten' => $ma, 'co' => 17])<span>{{ $ten }}</span><span class="dm-dem">{{ $dem }}</span></a>
    @endforeach
  </nav>

  {{-- ================= Loại hình dự án ================= --}}
  <section class="sheet dm-phan" id="loai-hinh">
    <div class="sheet-hd bc-phan-hd"><div class="bc-phan-giua"><h2>Loại hình dự án</h2></div></div>
    @if ($phanLoi === 'loai-hinh' && $errors->any())
      <div class="loi dm-loi">{{ implode(' ', $errors->all()) }}</div>
    @endif
    @foreach ($dsLoaiHinh as $l)
      <form id="sua-lh-{{ $l->id }}" method="post" action="{{ route('danh-muc.loai-hinh.update', $l) }}" hidden>
        @csrf @method('put')<input type="hidden" name="_phan" value="loai-hinh">
      </form>
      <form id="xoa-lh-{{ $l->id }}" method="post" action="{{ route('danh-muc.loai-hinh.destroy', $l) }}" hidden
            onsubmit="return confirm({{ \Illuminate\Support\Js::from('Xoá loại hình "'.$l->ten.'"?') }})">@csrf @method('delete')</form>
    @endforeach
    <form id="them-lh" method="post" action="{{ route('danh-muc.loai-hinh.store') }}" hidden>
      @csrf<input type="hidden" name="_phan" value="them-loai-hinh">
    </form>
    <div class="tablewrap" style="border:0;border-radius:0">
      <table class="dm-bang">
        <colgroup>
          <col style="width:60px"><col style="width:104px"><col><col style="width:17%"><col style="width:170px"><col style="width:110px"><col style="width:176px">
        </colgroup>
        <thead>
          <tr>
            <th class="giua">STT</th><th class="giua">Biểu tượng</th><th>Tên loại hình</th>
            <th title="Mã lưu trong dữ liệu dự án, không đổi được sau khi tạo">Mã</th><th>Màu</th>
            <th class="giua">Số dự án</th><th class="giua">Thao tác</th>
          </tr>
        </thead>
        <tbody>
        @forelse ($dsLoaiHinh as $i => $l)
          @php $soDung = $l->soDuAnDangDung(); @endphp
          <tr data-form="sua-lh-{{ $l->id }}">
            <td class="giua">{{ $i + 1 }}</td>
            <td class="giua"><span class="bt-chon chon-bieu-tuong" data-ten="bieu_tuong" data-form="sua-lh-{{ $l->id }}" data-mau="mau-lh-{{ $l->id }}" data-chon="{{ $l->bieu_tuong }}"></span></td>
            <td><input class="dm-o" form="sua-lh-{{ $l->id }}" name="ten" value="{{ $l->ten }}" required maxlength="150" aria-label="Tên loại hình"></td>
            <td><code class="dm-ma">{{ $l->ma }}</code></td>
            <td><label class="dm-mau"><input form="sua-lh-{{ $l->id }}" type="color" name="mau" value="{{ $l->mau }}" id="mau-lh-{{ $l->id }}"><span>{{ $l->mau }}</span></label></td>
            <td class="giua"><span class="dm-so {{ $soDung ? '' : 'rong' }}">{{ $soDung }}</span></td>
            <td class="giua">
              <span class="dm-nut">
                <button form="sua-lh-{{ $l->id }}" class="btn sm dm-luu">Lưu</button>
                <button form="xoa-lh-{{ $l->id }}" class="btn sm danger" @if ($soDung) disabled title="Đang có {{ $soDung }} dự án thuộc loại hình này" @endif>Xoá</button>
              </span>
            </td>
          </tr>
        @empty
          <tr><td colspan="7" class="trong">Chưa có loại hình nào.</td></tr>
        @endforelse
        </tbody>
        <tfoot>
          <tr class="dm-them">
            <td class="giua"><span class="dm-cong" aria-hidden="true">+</span></td>
            <td class="giua"><span class="bt-chon chon-bieu-tuong" data-ten="bieu_tuong" data-form="them-lh" data-mau="mau-lh-moi" data-chon="{{ $cu('loai-hinh', 'bieu_tuong', 'khac') }}"></span></td>
            <td><input class="dm-o" form="them-lh" name="ten" value="{{ $cu('loai-hinh', 'ten') }}" placeholder="Tên loại hình mới" maxlength="150" required></td>
            <td><input class="dm-o" form="them-lh" name="ma" value="{{ $cu('loai-hinh', 'ma') }}" placeholder="Mã, VD: cang_bien" maxlength="30" required title="Chữ không dấu, số, gạch dưới; không đổi được sau khi tạo"></td>
            <td><label class="dm-mau"><input form="them-lh" type="color" name="mau" value="{{ $cu('loai-hinh', 'mau', '#1f4b6e') }}" id="mau-lh-moi"><span>{{ $cu('loai-hinh', 'mau', '#1f4b6e') }}</span></label></td>
            <td></td>
            <td class="giua"><button form="them-lh" class="btn primary sm dm-them-nut">Thêm loại hình</button></td>
          </tr>
        </tfoot>
      </table>
    </div>
  </section>

  {{-- ================= Trạng thái dự án ================= --}}
  <section class="sheet dm-phan" id="trang-thai">
    <div class="sheet-hd bc-phan-hd"><div class="bc-phan-giua"><h2>Trạng thái dự án</h2></div></div>
    @if ($phanLoi === 'trang-thai' && $errors->any())
      <div class="loi dm-loi">{{ implode(' ', $errors->all()) }}</div>
    @endif
    @foreach ($dsTrangThai as $t)
      <form id="sua-tt-{{ $t->id }}" method="post" action="{{ route('danh-muc.trang-thai.update', $t) }}" hidden>
        @csrf @method('put')<input type="hidden" name="_phan" value="trang-thai">
      </form>
      <form id="xoa-tt-{{ $t->id }}" method="post" action="{{ route('danh-muc.trang-thai.destroy', $t) }}" hidden
            onsubmit="return confirm({{ \Illuminate\Support\Js::from('Xoá trạng thái "'.$t->ten.'"?') }})">@csrf @method('delete')</form>
    @endforeach
    <form id="them-tt" method="post" action="{{ route('danh-muc.trang-thai.store') }}" hidden>
      @csrf<input type="hidden" name="_phan" value="them-trang-thai">
    </form>
    <div class="tablewrap" style="border:0;border-radius:0">
      <table class="dm-bang">
        <colgroup><col style="width:60px"><col><col style="width:34%"><col style="width:110px"><col style="width:176px"></colgroup>
        <thead>
          <tr>
            <th class="giua">STT</th>
            <th title="Đổi tên sẽ cập nhật cho mọi dự án đang dùng trạng thái này">Tên trạng thái</th>
            <th title="Nhóm Đã hoàn thành và Chậm tiến độ quyết định 2 ô đếm trên trang Thống kê">Nhóm thống kê</th>
            <th class="giua">Số dự án</th><th class="giua">Thao tác</th>
          </tr>
        </thead>
        <tbody>
        @forelse ($dsTrangThai as $i => $t)
          @php $soDung = $t->soDuAnDangDung(); @endphp
          <tr data-form="sua-tt-{{ $t->id }}">
            <td class="giua">{{ $i + 1 }}</td>
            <td><input class="dm-o" form="sua-tt-{{ $t->id }}" name="ten" value="{{ $t->ten }}" required maxlength="150" aria-label="Tên trạng thái"></td>
            <td>
              <select class="dm-o dm-nhom" form="sua-tt-{{ $t->id }}" name="nhom" data-nhom="{{ $t->nhom }}" aria-label="Nhóm thống kê">
                @foreach ($nhomTrangThai as $ma => $nhan)
                  <option value="{{ $ma }}" @selected($t->nhom === $ma)>{{ $nhan }}</option>
                @endforeach
              </select>
            </td>
            <td class="giua"><span class="dm-so {{ $soDung ? '' : 'rong' }}">{{ $soDung }}</span></td>
            <td class="giua">
              <span class="dm-nut">
                <button form="sua-tt-{{ $t->id }}" class="btn sm dm-luu">Lưu</button>
                <button form="xoa-tt-{{ $t->id }}" class="btn sm danger" @if ($soDung) disabled title="Đang có {{ $soDung }} dự án ở trạng thái này" @endif>Xoá</button>
              </span>
            </td>
          </tr>
        @empty
          <tr><td colspan="5" class="trong">Chưa có trạng thái nào.</td></tr>
        @endforelse
        </tbody>
        <tfoot>
          <tr class="dm-them">
            <td class="giua"><span class="dm-cong" aria-hidden="true">+</span></td>
            <td><input class="dm-o" form="them-tt" name="ten" value="{{ $cu('trang-thai', 'ten') }}" placeholder="Tên trạng thái mới" maxlength="150" required></td>
            <td>
              <select class="dm-o dm-nhom" form="them-tt" name="nhom" data-nhom="{{ $cu('trang-thai', 'nhom', 'binh_thuong') }}" aria-label="Nhóm thống kê">
                @foreach ($nhomTrangThai as $ma => $nhan)
                  <option value="{{ $ma }}" @selected($cu('trang-thai', 'nhom', 'binh_thuong') === $ma)>{{ $nhan }}</option>
                @endforeach
              </select>
            </td>
            <td></td>
            <td class="giua"><button form="them-tt" class="btn primary sm dm-them-nut">Thêm trạng thái</button></td>
          </tr>
        </tfoot>
      </table>
    </div>
  </section>

  {{-- ================= Biểu tượng ================= --}}
  <section class="sheet dm-phan" id="bieu-tuong">
    <div class="sheet-hd bc-phan-hd"><div class="bc-phan-giua"><h2>Biểu tượng</h2></div></div>
    @if ($phanLoi === 'bieu-tuong' && $errors->any())
      <div class="loi dm-loi">{{ implode(' ', $errors->all()) }}</div>
    @endif
    @foreach ($dsBieuTuong as $b)
      <form id="xoa-bt-{{ $b->id }}" method="post" action="{{ route('danh-muc.bieu-tuong.destroy', $b) }}" hidden
            onsubmit="return confirm({{ \Illuminate\Support\Js::from('Xoá biểu tượng "'.$b->ten.'"?') }})">@csrf @method('delete')</form>
    @endforeach
    <div class="dm-bt-luoi">
      @foreach ($dsBieuTuong as $b)
        @php $soDung = $b->soDangDung(); @endphp
        <form class="dm-bt" method="post" action="{{ route('danh-muc.bieu-tuong.update', $b) }}" enctype="multipart/form-data">
          @csrf @method('put')<input type="hidden" name="_phan" value="bieu-tuong">
          <div class="dm-bt-xem"><span class="xem-bieu-tuong-thuc" data-ma="{{ $b->ma }}"></span></div>
          <input class="dm-o dm-bt-ten" name="ten" value="{{ $b->ten }}" required maxlength="100" aria-label="Tên biểu tượng">
          <code class="dm-ma">{{ $b->ma }}</code>
          <div class="dm-bt-chon">
            <span class="bt-chon chon-hinh" data-ten="hinh" data-chon="{{ $b->hinh }}" title="Hình dự phòng, dùng khi chưa tải icon riêng"></span>
            <label class="btn sm dm-bt-tai" title="Tải icon riêng: SVG hoặc PNG, tối đa 300 KB, nên nền trong suốt">
              @include('bao-cao._icon', ['ten' => 'tai-len', 'co' => 15])<span>Icon riêng</span>
              <input type="file" name="tep" accept=".svg,.png" hidden>
            </label>
          </div>
          @if ($b->tep)
            <label class="dm-bt-xoa-tep"><input type="checkbox" name="xoa_tep" value="1"> Xoá icon riêng đã tải</label>
          @endif
          <div class="dm-bt-chan">
            <span class="dm-bt-dung" title="Số loại hình và điểm dự án đang dùng"><b class="dm-so {{ $soDung ? '' : 'rong' }}">{{ $soDung }}</b> đang dùng</span>
            <span class="dm-nut">
              <button class="btn sm dm-luu">Lưu</button>
              <button form="xoa-bt-{{ $b->id }}" class="btn sm danger" @if ($soDung) disabled title="Đang có {{ $soDung }} nơi dùng biểu tượng này" @endif>Xoá</button>
            </span>
          </div>
        </form>
      @endforeach

      <form class="dm-bt dm-bt-moi" method="post" action="{{ route('danh-muc.bieu-tuong.store') }}" enctype="multipart/form-data">
        @csrf<input type="hidden" name="_phan" value="them-bieu-tuong">
        <div class="dm-bt-xem"><span class="xem-bieu-tuong-thuc"><span class="dm-bt-trong" aria-hidden="true">+</span></span></div>
        <input class="dm-o dm-bt-ten" name="ten" value="{{ $cu('bieu-tuong', 'ten') }}" placeholder="Tên biểu tượng mới" maxlength="100" required>
        <input class="dm-o dm-bt-ma" name="ma" value="{{ $cu('bieu-tuong', 'ma') }}" placeholder="Mã, VD: cang_bien" maxlength="30" required title="Chữ không dấu, số, gạch dưới; không đổi được sau khi tạo">
        <div class="dm-bt-chon">
          <span class="bt-chon chon-hinh" data-ten="hinh" data-chon="{{ $cu('bieu-tuong', 'hinh', 'khac') }}" title="Hình dự phòng, dùng khi chưa tải icon riêng"></span>
          <label class="btn sm dm-bt-tai" title="Tải icon riêng: SVG hoặc PNG, tối đa 300 KB, nên nền trong suốt">
            @include('bao-cao._icon', ['ten' => 'tai-len', 'co' => 15])<span>Icon riêng</span>
            <input type="file" name="tep" accept=".svg,.png" hidden>
          </label>
        </div>
        <div class="dm-bt-chan"><button class="btn primary sm dm-them-nut" style="width:100%">Thêm biểu tượng</button></div>
      </form>
    </div>
  </section>
</div>
@endsection

@push('cuoi_trang')
<script>window.DS_BIEU_TUONG = @json($dsBieuTuong);</script>
<script src="{{ asset('js/bieu-tuong.js') }}?v={{ filemtime(public_path('js/bieu-tuong.js')) }}"></script>
<script>
(function () {
  var $$ = function (s, g) { return Array.prototype.slice.call((g || document).querySelectorAll(s)); };

  $$('.chon-bieu-tuong').forEach(function (el) { BieuTuongLoaiHinh.khoiTaoBoChon(el, 'bieu_tuong'); });
  $$('.chon-hinh').forEach(function (el) { BieuTuongLoaiHinh.khoiTaoBoChon(el, 'hinh'); });
  $$('.xem-bieu-tuong-thuc[data-ma]').forEach(function (el) {
    el.innerHTML = BieuTuongLoaiHinh.badgeHtml(el.dataset.ma, '#5b6773', 48);
  });

  // Tab 3 phần: mở phần có lỗi (nếu có), không thì phần đang xem lần trước (nhớ qua các lần lưu)
  var trang = document.querySelector('.dm-trang');
  var tab = $$('.dm-tab a');
  var phan = $$('.dm-phan');
  function mo(ma) {
    if (!document.getElementById(ma)) ma = phan[0].id;
    phan.forEach(function (p) { p.hidden = p.id !== ma; });
    tab.forEach(function (a) { a.classList.toggle('chon', a.dataset.phan === ma); });
    try { sessionStorage.setItem('dm-phan', ma); } catch (e) {}
  }
  tab.forEach(function (a) {
    a.addEventListener('click', function (e) { e.preventDefault(); mo(a.dataset.phan); });
  });
  var dau = trang.dataset.phanLoi || location.hash.slice(1);
  if (!dau) { try { dau = sessionStorage.getItem('dm-phan') || ''; } catch (e) {} }
  mo(dau);

  // Sửa bất kỳ ô nào thì đánh dấu dòng/thẻ đó và làm nổi nút "Lưu"
  function danhDau(e) {
    var o = e.target.closest && e.target.closest('tr[data-form], form.dm-bt:not(.dm-bt-moi)');
    if (o) o.classList.add('doi');
    if (e.target.classList && e.target.classList.contains('dm-nhom')) e.target.dataset.nhom = e.target.value;
  }
  trang.addEventListener('input', danhDau);
  trang.addEventListener('change', danhDau);

  // Đổi màu: cập nhật mã màu và vẽ lại biểu tượng theo màu mới
  $$('.dm-mau input[type=color]').forEach(function (inp) {
    inp.addEventListener('input', function () {
      inp.parentNode.querySelector('span').textContent = inp.value;
      var chon = document.querySelector('.chon-bieu-tuong[data-mau="' + inp.id + '"]');
      var gt = chon && chon.querySelector('.bt-chon-gt');
      if (!chon || !gt) return;
      chon.dataset.chon = gt.value;
      BieuTuongLoaiHinh.khoiTaoBoChon(chon, 'bieu_tuong');
    });
  });

  // Chọn file icon: xem trước ngay (URL tạm của trình duyệt), trước khi lưu
  $$('input[name="tep"]').forEach(function (inp) {
    inp.addEventListener('change', function () {
      var form = inp.closest('form');
      var xem = form ? form.querySelector('.xem-bieu-tuong-thuc') : null;
      if (!inp.files[0] || !xem) return;
      var url = URL.createObjectURL(inp.files[0]);
      xem.innerHTML = '<span class="bieu-tuong-badge" style="width:48px;height:48px;background:#5b6773">'
        + '<span class="bieu-tuong-anh" style="width:28px;height:28px;-webkit-mask-image:url(\'' + url + '\');mask-image:url(\'' + url + '\')"></span></span>';
      var nhan = inp.parentNode.querySelector('span');
      if (nhan) nhan.textContent = inp.files[0].name.length > 14 ? inp.files[0].name.slice(0, 12) + '…' : inp.files[0].name;
    });
  });
})();
</script>
@endpush
