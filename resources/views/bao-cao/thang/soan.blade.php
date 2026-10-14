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
    'du_kien_khoi_cong' => 'VD: Tháng 12/2026 — hoặc "Đã khởi công ngày 19/12/2025"',
    'du_kien_hoan_thanh' => 'VD: Tháng 6/2027 — hoặc "Năm đóng điện thương mại 2021"',
    'tinh_hinh' => "VD:\n- Tình trạng hồ sơ: đã chấp thuận chủ trương đầu tư, đang …\n- Phần nhà máy: đã thi công hoàn thành …/… trụ tuabin, đang …\n- Phần đường dây đấu nối: …",
    'kho_khan' => 'VD: Hành lang tuyến đường dây còn 01 hộ chưa phối hợp kiểm đếm; thi công gián đoạn do mưa…',
    'kien_nghi_xa' => 'VD: Đề nghị UBND xã … hỗ trợ bàn giao mặt bằng hành lang tuyến trước ngày …',
    'kien_nghi_so' => 'VD: Đề nghị Sở Nông nghiệp và Môi trường hướng dẫn, hỗ trợ xã xử lý GPMB…',
  ];
  $tenNgan = ['du_kien_khoi_cong', 'du_kien_hoan_thanh'];
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
          $ten = "du_an[{$d->id}]";
          $khoa = "du_an.{$d->id}";
          $co = $coDuLieuCu ? (bool) old("$khoa.co") : $m['co'];
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
              @foreach (['chu_dau_tu' => 'Chủ đầu tư', 'cong_suat' => 'Công suất (MW)'] as $cot => $nhan)
                <div class="bcs-o bcs-o-nho" data-o>
                  <div class="bcs-o-dau"><label for="o-{{ $d->id }}-{{ $cot }}">{{ $nhan }}</label><span class="bcs-nhan" data-nhan></span></div>
                  <input id="o-{{ $d->id }}-{{ $cot }}" name="{{ $ten }}[{{ $cot }}]" maxlength="{{ $cot === 'cong_suat' ? 255 : 1000 }}"
                         value="{{ old("$khoa.$cot", $gt[$cot]) }}" @if ($cot === 'cong_suat') placeholder="VD: 48" @endif>
                  @error("$khoa.$cot")<span class="sai">{{ $message }}</span>@enderror
                </div>
              @endforeach
            </div>

            @php $dsO = collect(\App\Models\BaoCaoThangDuAn::NOI_DUNG_MAU); @endphp
            <div class="bcs-luoi-moc">
              @foreach ($dsO->only($tenNgan) as $cot => $nhan)
                @include('bao-cao.thang._o-soan', ['cot' => $cot, 'nhan' => $nhan, 'soDong' => 2])
              @endforeach
            </div>
            @foreach ($dsO->except($tenNgan) as $cot => $nhan)
              @include('bao-cao.thang._o-soan', ['cot' => $cot, 'nhan' => $nhan, 'soDong' => $cot === 'tinh_hinh' ? 6 : 3])
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
