@extends('layouts.app')
@section('tieu_de', $duAn->ten)

@php
  $nguoiDung = auth()->user();
  $dangVanHanh = $duAn->trang_thai === 'Đã đi vào hoạt động';
  $laNguonDien = in_array($duAn->loai_hinh, config('duan.ban_do.loai_hinh_nguon_dien', []), true);
  // Ô số liệu đầu khung — mỗi chỉ tiêu một màu (lớp ct-o-…), chỉ hiện chỉ tiêu có số liệu
  $oSo = array_values(array_filter([
    ['cong-suat', 'Công suất', $duAn->cong_suat],
    ['von', 'Vốn đăng ký', $duAn->von_dang_ky ? number_format($duAn->von_dang_ky, 2, ',', '.').' tỷ đồng' : null],
    ['dien-tich', 'Diện tích', $duAn->dien_tich ? number_format($duAn->dien_tich, 2, ',', '.').' ha' : null],
    ['san-luong', 'Sản lượng điện', $duAn->san_luong_dien ? number_format($duAn->san_luong_dien, 0, ',', '.').' kWh/năm' : null],
  ], fn ($o) => filled($o[2])));
  $dsHoSo = App\Models\DuAn::HO_SO_PHAP_LY;
  $dayDuHoSo = App\Models\DuAn::HO_SO_PHAP_LY_DAY_DU;
  $soHoSo = $duAn->so_ho_so_da_co;
  // Văn bản của từng hồ sơ pháp lý — cùng danh sách với mục "Văn bản hồ sơ pháp lý" ở form sửa
  $vbTheoHoSo = $duAn->vanBanPhapLy->groupBy('ho_so');
  $thongTin = array_filter([
    'Chủ đầu tư' => $duAn->chu_dau_tu,
    'Tài khoản chủ đầu tư' => $duAn->taiKhoanChuDauTu?->name,
    'Địa điểm trước đây' => implode(', ', $duAn->diaDiemTruocDayDanhSach()),
    'Địa điểm hiện nay' => $duAn->dia_diem,
    'Khởi công' => $duAn->ngay_khoi_cong?->format('d/m/Y'),
    'Hoàn thành' => $duAn->tien_do_van_hanh ?: $duAn->ngay_hoan_thanh?->format('d/m/Y'),
    'Toạ độ' => $duAn->vi_do ? number_format($duAn->vi_do, 5).', '.number_format($duAn->kinh_do, 5) : null,
  ]);
@endphp

@push('dau_trang')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
<style>
  /* Như trang Bản đồ: khoá khung bằng chiều cao màn hình để sidebar tự cuộn, bản đồ chiếm phần còn lại */
  .app{height:100dvh;min-height:0}
</style>
@endpush

@section('noi_dung')
<div class="mapview">
  <aside class="ct-ben" id="ctBen">
    <button type="button" class="panel-dong" id="ctDong" title="Thu gọn khung thông tin">‹</button>
    <div class="ct-cuon">
      <div class="ct-dau" style="--ct-mau:{{ $duAn->mau ?: '#1f4b6e' }}">
        <a href="{{ route('du-an.index') }}" class="ct-ve">‹ Danh sách dự án</a>
        <div class="ct-loai">
          <span class="ct-loai-icon" data-bieu-tuong="{{ $duAn->bieu_tuong_loai_hinh }}" data-mau="{{ $duAn->mau }}"></span>
          <span>{{ $duAn->ten_loai_hinh }}</span>
        </div>
        <h1>{{ $duAn->ten }}</h1>
        @if ($duAn->trang_thai)
          <div class="ct-dau-phu"><span class="chip {{ $dangVanHanh ? 'co' : 'xanh' }}">{{ $duAn->trang_thai }}</span></div>
        @endif
        @if ($nguoiDung->duocSua() || $nguoiDung->duocXoa())
          <div class="ct-nut">
            @if ($nguoiDung->duocSua())
              <a href="{{ route('du-an.edit', $duAn) }}" class="btn primary sm">Sửa dự án</a>
            @endif
            @if ($nguoiDung->duocXoa())
              <form method="post" action="{{ route('du-an.destroy', $duAn) }}" onsubmit="return confirm(this.dataset.hoi)"
                    data-hoi="Xoá dự án {{ $duAn->ten }}?">
                @csrf @method('delete')
                <button class="btn danger sm">Xoá</button>
              </form>
            @endif
          </div>
        @endif
      </div>

      @if ($oSo || $duAn->tien_do !== null)
        <div class="ct-so">
          @foreach ($oSo as [$lopO, $nhanO, $giaTriO])
            <div class="ct-o ct-o-{{ $lopO }}"><span>{{ $nhanO }}</span><b>{{ $giaTriO }}</b></div>
          @endforeach
          @if ($duAn->tien_do !== null)
            <div class="ct-o ct-o-tien-do ct-o-rong">
              <span>Tiến độ thực hiện</span><b>{{ \App\Models\BaoCaoTuanDuAn::phanTramTienDo($duAn->tien_do) }}</b>
              <i class="ct-thanh"><i style="width:{{ max(0, min(100, $duAn->tien_do)) }}%"></i></i>
            </div>
          @endif
        </div>
      @endif

      <section class="ct-muc">
        <h3>Bản đồ chi tiết dự án</h3>
        <div id="ctLop" class="ct-lop"></div>
        @if (! $nhomHinhHoc)
          <p class="ct-trong">Chưa có dữ liệu chi tiết.
            @if ($nguoiDung->duocSua())<a href="{{ route('du-an.edit', $duAn) }}">Nhập lớp hình học</a>@endif
          </p>
        @endif
        @if (collect($nhomHinhHoc)->contains('loai', 'diem') || $nguoiDung->duocXem('ban_do'))
          <div class="ct-tuy-chon">
            @if (collect($nhomHinhHoc)->contains('loai', 'diem'))
              <label class="ct-cong-tac"><input type="checkbox" id="ctHienNhan"><span>Hiện tên các điểm</span></label>
            @endif
            @if ($nguoiDung->duocXem('ban_do'))
              <label class="ct-cong-tac"><input type="checkbox" id="ctLuoiDien"><span>Lưới điện khu vực</span></label>
            @endif
          </div>
        @endif
      </section>

      @if ($thongTin)
        <section class="ct-muc">
          <h3>Thông tin chung</h3>
          <dl class="ct-dl">
            @foreach ($thongTin as $nhan => $gt)
              <div><dt>{{ $nhan }}</dt><dd>{{ $gt }}</dd></div>
            @endforeach
          </dl>
        </section>
      @endif

      <section class="ct-muc" id="hoSoPhapLy">
        <h3>Hồ sơ pháp lý <span class="ct-dem">{{ $soHoSo }}/{{ count($dsHoSo) }}</span></h3>
        @if ($errors->has('tep') || $errors->has('tep.*') || $errors->has('loai') || $errors->has('so_van_ban') || $errors->has('ngay_van_ban'))
          <div class="loi ct-loi">{{ implode(' ', array_unique($errors->all())) }}</div>
        @endif
        <i class="ct-hs-thanh"><i style="width:{{ count($dsHoSo) ? round($soHoSo / count($dsHoSo) * 100) : 0 }}%"></i></i>
        <ul class="ct-hs">
          @foreach ($dsHoSo as $maHs => $tenHs)
            @php $vbHs = $vbTheoHoSo->get($maHs, collect()); @endphp
            <li class="{{ $duAn->$maHs ? 'co' : '' }}">
              <div class="ct-hs-dong">
                <span class="ct-hs-dau">{{ $duAn->$maHs ? '✓' : '' }}</span>
                <span class="ct-hs-ten" @isset($dayDuHoSo[$maHs]) title="{{ $dayDuHoSo[$maHs] }}" @endisset>{{ $tenHs }}</span>
                @if ($vbHs->isNotEmpty())<span class="ct-hs-so-tep">{{ $vbHs->count() }} văn bản</span>@endif
                @if ($nguoiDung->duocSua())
                  <button type="button" class="ct-hs-tai" data-loai="{{ $maHs }}" data-ten="{{ $tenHs }}" title="Tải văn bản lên" aria-label="Tải văn bản lên cho {{ $tenHs }}">@include('bao-cao._icon', ['ten' => 'tai-len', 'co' => 15])</button>
                @endif
              </div>
              @if ($vbHs->isNotEmpty())
                <ul class="ct-tep">
                  @foreach ($vbHs as $vb)
                    <li class="ct-tep-vb">
                      @if ($vb->tep)
                        <a href="{{ $vb->tep_url }}" target="_blank" rel="noopener" class="ct-tep-ten" title="{{ $vb->ten_tep ?: $vb->so_van_ban }}">{{ $vb->so_van_ban ?: ($vb->ten_tep ?: 'Văn bản') }}</a>
                      @else
                        <span class="ct-tep-ten ct-tep-chua">{{ $vb->so_van_ban ?: 'Chưa có số' }}</span>
                      @endif
                      <span class="ct-tep-phu">{{ collect([$vb->ngay_van_ban?->format('d/m/Y'), $vb->loai])->filter()->join(' · ') }}</span>
                      @if ($nguoiDung->duocSua())
                        <form method="post" action="{{ route('van-ban.destroy', $vb) }}" onsubmit="return confirm(this.dataset.hoi)" data-hoi="Xoá văn bản {{ $vb->so_van_ban ?: $vb->ten_tep }}?">
                          @csrf @method('delete')
                          <button class="ct-tep-xoa" title="Xoá văn bản" aria-label="Xoá văn bản">×</button>
                        </form>
                      @endif
                    </li>
                  @endforeach
                </ul>
              @endif
            </li>
          @endforeach
        </ul>
        @if ($nguoiDung->duocSua())
          <form class="ct-tai" id="ctTai" method="post" action="{{ route('du-an.ho-so-tep.store', $duAn) }}" enctype="multipart/form-data" hidden>
            @csrf
            <input type="hidden" name="loai" value="{{ old('loai') }}">
            <div class="ct-tai-tieu-de">Tải văn bản: <b class="ct-tai-ten"></b></div>
            <input type="file" name="tep[]" multiple required accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.zip,.rar">
            <div class="ct-tai-hang">
              <input name="so_van_ban" value="{{ old('so_van_ban') }}" placeholder="Số văn bản" maxlength="100">
              <input type="date" name="ngay_van_ban" value="{{ old('ngay_van_ban') }}" title="Ngày văn bản">
            </div>
            <div class="ct-tai-nut">
              <button class="btn primary sm">Tải lên</button>
              <button type="button" class="btn sm ct-tai-huy">Huỷ</button>
            </div>
          </form>
          <script>
          // Bấm nút tải lên ở hồ sơ nào thì khung tải văn bản mở ngay dưới hồ sơ đó
          (function () {
            var form = document.getElementById('ctTai');
            var oLoai = form.querySelector('[name=loai]'), oTen = form.querySelector('.ct-tai-ten');
            function mo(nut) {
              nut.closest('li').appendChild(form);
              oLoai.value = nut.dataset.loai;
              oTen.textContent = nut.dataset.ten;
              form.hidden = false;
              form.querySelector('input[type=file]').focus();
            }
            Array.prototype.forEach.call(document.querySelectorAll('.ct-hs-tai'), function (nut) {
              nut.addEventListener('click', function () {
                if (!form.hidden && oLoai.value === nut.dataset.loai) { form.hidden = true; return; }
                mo(nut);
              });
            });
            form.querySelector('.ct-tai-huy').addEventListener('click', function () { form.hidden = true; });
            form.addEventListener('submit', function () {
              var gui = form.querySelector('button.primary');
              gui.disabled = true;
              gui.textContent = 'Đang tải lên…';
            });
            // Lần tải trước bị lỗi: mở lại khung tải ở đúng hồ sơ đó
            if (oLoai.value) {
              var nutCu = document.querySelector('.ct-hs-tai[data-loai="' + oLoai.value + '"]');
              if (nutCu) mo(nutCu);
            }
          })();
          </script>
        @endif
      </section>

      @if ($mucBaoCao)
        @php $bc = $mucBaoCao->baoCao; @endphp
        <section class="ct-muc">
          <h3>Báo cáo tuần gần nhất</h3>
          <div class="ct-bao-cao">
            <div class="ct-bc-dau"><b>{{ $bc->ky()->ten() }}</b> <span class="chip {{ $bc->lop_chip }}">{{ $bc->ten_trang_thai }}</span></div>
            <div class="ct-bc-phu">{{ $bc->ky()->khoang() }} · gửi {{ $bc->gio('nop_luc') }}</div>
            @foreach ($mucBaoCao->cacMuc($bc->ky()->namTaiChinh()) as $muc)
              <details>
                <summary>{{ rtrim(preg_replace('/^\d+\.\s*/', '', $muc['tieu_de']), ':') }}</summary>
                <div style="white-space:pre-line">{{ implode("\n", $muc['dong']) }}</div>
              </details>
            @endforeach
            <a href="{{ route('bao-cao.show', $bc) }}" class="btn sm" style="margin-top:10px">Xem toàn văn báo cáo</a>
          </div>
        </section>
      @endif

      @if ($duAn->ghi_chu)
        <section class="ct-muc">
          <h3>Ghi chú</h3>
          <div class="ct-ghi-chu">{!! nl2br(e($duAn->ghi_chu)) !!}</div>
        </section>
      @endif

      <div class="ct-cap-nhat">Cập nhật {{ $duAn->updated_at->format('d/m/Y H:i') }}@if ($duAn->nguoiCapNhat) — {{ $duAn->nguoiCapNhat->name }}@endif</div>
    </div>
  </aside>

  <div class="mapwrap">
    <div id="map"></div>
    <button type="button" class="panel-mo" id="ctMo"><span class="ico">☰</span> Thông tin dự án</button>
    <div class="maptools">
      <button type="button" class="btn" id="ctToanCanh" title="Phóng vừa toàn bộ dự án">⤢ Toàn cảnh dự án</button>
      <select id="ctNen" class="btn" style="padding:6px 9px">
        @foreach (config('duan.ban_do.lop_nen') as $ma => $l)
          <option value="{{ $ma }}" @selected($ma === 've_tinh')>{{ $l['ten'] }}</option>
        @endforeach
      </select>
      <select id="ctThoiDiemAnh" class="btn" style="padding:6px 9px" hidden aria-label="Thời điểm ảnh vệ tinh"></select>
    </div>
    @unless ($duAn->vi_do || $nhomHinhHoc)
      <div class="ct-chua-vi-tri">
        Dự án chưa có toạ độ nên chưa hiện được trên bản đồ.
        @if ($nguoiDung->duocSua())<a href="{{ route('du-an.edit', $duAn) }}">Cập nhật vị trí</a>@endif
      </div>
    @endunless
    <div class="canhbao" id="canhBao"></div>
  </div>
</div>
@endsection

@push('cuoi_trang')
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>
<script>
  window.DS_BIEU_TUONG = @json($dsBieuTuong);
  window.CHI_TIET_DU_AN = {
    ten: @json($duAn->ten),
    viDo: @json($duAn->vi_do),
    kinhDo: @json($duAn->kinh_do),
    mau: @json($duAn->mau),
    bieuTuong: @json($duAn->bieu_tuong_loai_hinh),
    dangVanHanh: @json($dangVanHanh),
    chuaVanHanh: @json($laNguonDien && ! $dangVanHanh),
    nhom: @json($nhomHinhHoc),
    banDo: @json(config('duan.ban_do')),
    haTang: @json($nguoiDung->duocXem('ban_do') ? route('api.ha-tang') : null),
  };
</script>
<script src="{{ asset('js/bieu-tuong.js') }}?v={{ filemtime(public_path('js/bieu-tuong.js')) }}"></script>
<script src="{{ asset('js/nen-ban-do.js') }}?v={{ filemtime(public_path('js/nen-ban-do.js')) }}"></script>
<script src="{{ asset('js/vi-tri-cua-toi.js') }}?v={{ filemtime(public_path('js/vi-tri-cua-toi.js')) }}"></script>
<script src="{{ asset('js/do-khoang-cach.js') }}?v={{ filemtime(public_path('js/do-khoang-cach.js')) }}"></script>
<script src="{{ asset('js/chi-tiet-du-an.js') }}?v={{ filemtime(public_path('js/chi-tiet-du-an.js')) }}"></script>
@endpush
