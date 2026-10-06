@extends('layouts.app')
@section('tieu_de', 'Lập báo cáo '.mb_strtolower($ky->ten()))

@php
  $coDuLieuCu = session()->hasOldInput();
  $choPhepNhap = ! $baoCao || in_array($baoCao->trang_thai, ['nhap', 'yeu_cau_bo_sung'], true);
  $vb = fn ($cot) => old($cot, $vanBan[$cot]);
  $thieuVanBan = trim((string) $vb('ten_don_vi')) === '' || $errors->hasAny(['ten_don_vi', 'nguoi_lap', 'dien_thoai']);
  $tomTatVanBan = collect([$vb('ten_don_vi'), $vb('nguoi_lap') ? 'Người lập: '.$vb('nguoi_lap') : null, $vb('dien_thoai')])
    ->filter()->implode(' · ');
  $goiY = [
    'phap_ly' => "VD:\n- Quyết định chấp thuận chủ trương đầu tư số …/QĐ-UBND ngày …\n- Quyết định chấp thuận nhà đầu tư số …/QĐ-UBND ngày …",
    'tien_do_cod' => "VD:\n- Duyệt FS: tháng …/…\n- Khởi công: …\n- Hoàn thành toàn dự án, vận hành COD: tháng …/…",
    'tien_do_thuc_te' => 'VD: Vận hành thương mại tổ máy 1: tháng …/…; tổ máy 2: tháng …/…',
    'tinh_hinh' => "VD:\n- Đã thi công hoàn thành …; đang triển khai …\n- Khó khăn, vướng mắc: công tác GPMB còn … hộ chưa thống nhất …",
    'phuong_an' => 'VD: Kính đề nghị UBND tỉnh chỉ đạo … hỗ trợ …',
    'ghi_chu' => '',
  ];
  $namHienTai = (int) now()->format('Y');
@endphp

@section('noi_dung')
<form method="post" action="{{ route('bao-cao.thang.luu') }}" id="formBaoCao" class="bcs" novalidate
      data-ky-truoc="tháng trước" data-xac-nhan="Gửi báo cáo {{ mb_strtolower($ky->ten()) }} cho Sở Công Thương?">
  @csrf
  <input type="hidden" name="ky" value="{{ $ky->ma() }}">

  <div class="bcs-dau">
    <div>
      <h1>Báo cáo {{ mb_strtolower($ky->ten()) }}</h1>
      <div class="bcs-dau-phu">
        Tình hình triển khai các dự án theo mẫu bảng của Sở · hạn gửi {{ $ky->hanNop()->format('d/m/Y') }}
        @if ($baoCao) · <span class="chip {{ $baoCao->lop_chip }}">{{ $baoCao->ten_trang_thai }}</span>@endif
      </div>
    </div>
    <a href="{{ route('bao-cao.thang.index') }}" class="btn">Về danh sách báo cáo</a>
  </div>

  <div class="bcs-than">
    <nav class="bcs-nav" aria-label="Các phần của báo cáo">
      <div class="bcs-nav-tieu-de">Các phần của báo cáo</div>
      <a href="#phan-van-ban" class="bcs-nav-muc" data-nav="van-ban">
        <span class="bcs-nav-so">≡</span><span class="bcs-nav-ten">Đơn vị báo cáo</span><span class="bcs-nav-dem" data-dem></span>
      </a>
      @foreach ($muc as $i => $m)
        <a href="#du-an-{{ $m['du_an']->id }}" class="bcs-nav-muc" data-nav="{{ $m['du_an']->id }}">
          <span class="bcs-nav-so">{{ $i + 1 }}</span>
          <span class="bcs-nav-ten">{{ $m['du_an']->ten }}</span>
          <span class="bcs-nav-dem" data-dem></span>
        </a>
      @endforeach
      <div class="bcs-chu-giai">
        <div><i class="bcs-cham thieu"></i>Bắt buộc, chưa nhập</div>
        <div><i class="bcs-cham giu"></i>Giống tháng trước — cần rà soát</div>
        <div><i class="bcs-cham moi"></i>Đã cập nhật tháng này</div>
      </div>
    </nav>

    <div class="bcs-chinh">
      @if ($baoCao?->trang_thai === 'yeu_cau_bo_sung')
        <div class="bc-canh-bao">
          <div><b>Sở yêu cầu bổ sung</b> <span class="hint" style="margin:0">({{ $baoCao->gio('xu_ly_luc') }})</span>
            <div style="white-space:pre-line;margin-top:3px">{{ $baoCao->y_kien_xu_ly }}</div></div>
        </div>
      @elseif ($baoCao?->trang_thai === 'da_nop')
        <div class="flash" style="margin:0">Báo cáo này đã gửi lúc {{ $baoCao->gio('nop_luc') }} và đang chờ Sở tiếp nhận — sửa xong bấm "Cập nhật báo cáo đã gửi".</div>
      @endif

      @if ($errors->any())
        <div class="loi" style="margin:0">
          Báo cáo chưa được lưu, cần sửa:
          <ul>@foreach ($errors->all() as $loi)<li>{{ $loi }}</li>@endforeach</ul>
        </div>
      @endif

      <div class="bcs-tong-quan" id="tongQuan">
        <button type="button" class="bcs-so-lieu thieu" data-toi="thieu">
          <b data-tong="thieu">0</b><span>mục bắt buộc chưa nhập</span>
        </button>
        <button type="button" class="bcs-so-lieu giu" data-toi="giu">
          <b data-tong="giu">0</b><span>mục giống {{ $truoc ? mb_strtolower($truoc->ky()->ten()) : 'tháng trước' }} — cần rà soát</span>
        </button>
        <div class="bcs-so-lieu moi">
          <b data-tong="moi">0</b><span>mục đã cập nhật tháng này</span>
        </div>
        <p class="bcs-tong-quan-ghi">
          @if ($truoc)
            Nội dung được chép sẵn từ báo cáo {{ mb_strtolower($truoc->ky()->ten()) }}. Mục <b class="mau-giu">màu vàng</b> vẫn y nguyên như tháng trước —
            đọc lại và sửa những gì đã thay đổi; mục đổi xong tự chuyển <b class="mau-moi">màu xanh</b>.
          @else
            Mỗi dự án là 1 dòng trong bảng báo cáo của Sở. Nội dung ban đầu lấy từ hồ sơ dự án và báo cáo tuần gần nhất (nếu có) —
            rà soát, bổ sung rồi gửi. Mục <b class="mau-thieu">màu đỏ</b> là bắt buộc.
          @endif
        </p>
      </div>

      <section class="bcs-the" id="phan-van-ban" data-phan="van-ban">
        <details class="bcs-van-ban" @if ($thieuVanBan) open @endif>
          <summary class="bcs-the-dau">
            <span class="bcs-so-la-ma">≡</span>
            <span class="bcs-the-ten">
              <h2>Đơn vị báo cáo</h2>
              <span class="bcs-the-meta">{{ $tomTatVanBan }}</span>
            </span>
            <span class="bcs-the-chip" data-chip></span>
            <span class="bcs-mo-them">Sửa</span>
          </summary>
          <div class="bcs-van-ban-luoi">
            <div class="bcs-o" data-o data-bat-buoc>
              <div class="bcs-o-dau"><label for="vbDonVi">Tên đơn vị báo cáo <span class="bb">*</span></label><span class="bcs-nhan" data-nhan></span></div>
              <input id="vbDonVi" name="ten_don_vi" maxlength="255" value="{{ $vb('ten_don_vi') }}">
              @error('ten_don_vi')<span class="sai">{{ $message }}</span>@enderror
            </div>
            <div class="bcs-o" data-o>
              <div class="bcs-o-dau"><label for="vbNguoiLap">Người lập báo cáo</label><span class="bcs-nhan" data-nhan></span></div>
              <input id="vbNguoiLap" name="nguoi_lap" maxlength="255" value="{{ $vb('nguoi_lap') }}" placeholder="Họ và tên, chức vụ">
            </div>
            <div class="bcs-o" data-o>
              <div class="bcs-o-dau"><label for="vbDienThoai">Điện thoại liên hệ</label><span class="bcs-nhan" data-nhan></span></div>
              <input id="vbDienThoai" name="dien_thoai" maxlength="50" value="{{ $vb('dien_thoai') }}">
            </div>
          </div>
        </details>
      </section>

      @error('du_an')<div class="loi" style="margin:0">{{ $message }}</div>@enderror

      @foreach ($muc as $i => $m)
        @php
          $d = $m['du_an'];
          $gt = $m['gt'];
          $tr = $m['truoc'];
          $sh = $m['sheet'];
          $ten = "du_an[{$d->id}]";
          $khoa = "du_an.{$d->id}";
          $co = $coDuLieuCu ? (bool) old("$khoa.co") : $m['co'];
          $truongChu = ['phap_ly', 'tien_do_cod'];
          if (! empty($sh['thuc_te'])) $truongChu[] = 'tien_do_thuc_te';
          $truongChu[] = 'tinh_hinh';
          if ($sh['phuong_an'] ?? true) $truongChu[] = 'phuong_an';
          $truongChu[] = 'ghi_chu';
          $codNam = old("$khoa.cod_nam", $gt['cod_nam']);
          $codKy = old("$khoa.cod_ky", $gt['cod_ky']);
        @endphp
        <section class="bcs-the bcs-du-an {{ $co ? '' : 'tat' }}" id="du-an-{{ $d->id }}" data-phan="{{ $d->id }}" data-muc>
          <header class="bcs-the-dau">
            <span class="bcs-so-la-ma">{{ $i + 1 }}</span>
            <span class="bcs-the-ten">
              <h2>{{ $d->ten }}</h2>
              <span class="bcs-the-meta">{{ $d->ten_loai_hinh }}@if ($d->trang_thai) · {{ $d->trang_thai }}@endif</span>
            </span>
            <span class="bcs-the-chip" data-chip></span>
            <label class="bcs-chon"><input type="checkbox" name="{{ $ten }}[co]" value="1" @checked($co) data-chon-muc> Đưa vào báo cáo</label>
          </header>
          <div class="bcs-bo-qua">Dự án này không đưa vào báo cáo tháng này — đánh dấu "Đưa vào báo cáo" để nhập nội dung.</div>

          <div class="bcs-the-than">
            <div class="bcs-thong-tin">
              @foreach (['dia_diem' => 'Địa điểm xây dựng', 'cong_suat' => 'Công suất (MW)', 'chu_dau_tu' => 'Chủ đầu tư'] as $cot => $nhan)
                <div class="bcs-o bcs-o-nho" data-o>
                  <div class="bcs-o-dau"><label for="o-{{ $d->id }}-{{ $cot }}">{{ $nhan }}</label><span class="bcs-nhan" data-nhan></span></div>
                  <input id="o-{{ $d->id }}-{{ $cot }}" name="{{ $ten }}[{{ $cot }}]" maxlength="{{ $cot === 'cong_suat' ? 255 : 1000 }}"
                         value="{{ old("$khoa.$cot", $gt[$cot]) }}" @if ($cot === 'cong_suat') placeholder="VD: 48 hoặc 2x900MVA; 5km" @endif>
                  @error("$khoa.$cot")<span class="sai">{{ $message }}</span>@enderror
                </div>
              @endforeach
            </div>

            @foreach ($truongChu as $cot)
              @php $batBuoc = $cot === 'tinh_hinh'; @endphp
              <div class="bcs-o" data-o data-so-sanh data-truoc="{{ $tr[$cot] ?? '' }}" @if ($batBuoc) data-bat-buoc @endif>
                <div class="bcs-o-dau">
                  <label for="o-{{ $d->id }}-{{ $cot }}">{{ \App\Models\BaoCaoThangDuAn::TRUONG_CHU[$cot] }}@if ($batBuoc) <span class="bb">*</span>@endif</label>
                  <span class="bcs-nhan" data-nhan></span>
                </div>
                <textarea id="o-{{ $d->id }}-{{ $cot }}" name="{{ $ten }}[{{ $cot }}]" rows="{{ $cot === 'tinh_hinh' ? 5 : 3 }}" data-tu-gian
                          placeholder="{{ $goiY[$cot] }}">{{ old("$khoa.$cot", $gt[$cot]) }}</textarea>
                @error("$khoa.$cot")<span class="sai">{{ $message }}</span>@enderror
                @if (! empty($tr[$cot]))
                  <details class="bcs-truoc">
                    <summary>Xem nội dung {{ mb_strtolower($tr['ky']) }}</summary>
                    <div class="bcs-truoc-noi-dung">{{ $tr[$cot] }}</div>
                  </details>
                @endif

                @if ($cot === 'tien_do_cod')
                  {{-- Mốc COD có cấu trúc để Sở tự tổng hợp "Năm 2027: dự kiến có … dự án vận hành thương mại" --}}
                  <div class="bcs-cod">
                    <span>Dự kiến vận hành thương mại (COD):</span>
                    <select name="{{ $ten }}[cod_ky]" aria-label="Tháng/quý dự kiến COD">
                      <option value="">— Tháng/quý —</option>
                      @foreach (\App\Models\BaoCaoThangDuAn::KY_COD as $ma => $nhanKy)
                        <option value="{{ $ma }}" @selected($codKy === $ma)>{{ $nhanKy }}</option>
                      @endforeach
                    </select>
                    <span>năm</span>
                    <input type="number" name="{{ $ten }}[cod_nam]" value="{{ $codNam }}" min="2000" max="2100" step="1"
                           placeholder="{{ $namHienTai + 1 }}" aria-label="Năm dự kiến COD" style="width:92px">
                    @error("$khoa.cod_nam")<span class="sai">{{ $message }}</span>@enderror
                  </div>
                @endif
              </div>
            @endforeach
          </div>
        </section>
      @endforeach
    </div>
  </div>

  <div class="bcs-chan">
    <div class="bcs-chan-tom-tat" data-tom-tat></div>
    <button type="button" class="btn" data-toi-tiep hidden>Đi tới mục cần nhập ↓</button>
    <span class="bcs-gian"></span>
    <a href="{{ route('bao-cao.thang.index') }}" class="btn">Huỷ</a>
    @if ($choPhepNhap)
      <button name="hanh_dong" value="luu" class="btn">Lưu nháp</button>
    @endif
    <button name="hanh_dong" value="nop" class="btn primary" data-nop>
      {{ $baoCao?->trang_thai === 'da_nop' ? 'Cập nhật báo cáo đã gửi' : 'Gửi báo cáo' }}
    </button>
  </div>
</form>
@endsection

@push('cuoi_trang')
<script src="{{ asset('js/soan-bao-cao.js') }}?v={{ filemtime(public_path('js/soan-bao-cao.js')) }}"></script>
@endpush
