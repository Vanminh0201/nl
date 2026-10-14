@extends('layouts.app')
@section('tieu_de', 'Lập báo cáo '.$ky->ten())

@php
  // Sau khi lỗi kiểm tra, ô đánh dấu không được gửi lên (bỏ chọn) phải hiện đúng là bỏ chọn,
  // không quay về giá trị mặc định — chỉ phân biệt được khi biết có dữ liệu cũ trong phiên hay không.
  $coDuLieuCu = session()->hasOldInput();
  $goiY = [
    'thu_tuc_da_xong' => "VD:\n- Đã được phê duyệt điều chỉnh thiết kế cơ sở tại Quyết định số …/QĐ-… ngày …\n- Công tác lựa chọn nhà thầu: đã hoàn thành …/… gói thầu.",
    'cong_viec_dang_lam' => "VD:\n- Công tác bồi thường, GPMB: diện tích còn lại chưa bàn giao khoảng … ha, gồm …\n- Công tác thi công xây dựng: khối lượng tổng thể đạt …%; hạng mục … đã hoàn thành …",
    'kho_khan' => "VD:\n- Còn … hộ dân chưa thống nhất phương án bồi thường…\n- Kiến nghị UBND xã … phối hợp …",
    'ke_hoach' => "VD:\n- Dự kiến phê duyệt thiết kế kỹ thuật: …\n- Phát điện thương mại tổ máy số 1: tháng …/…",
  ];
  $choPhepNhap = ! $baoCao || in_array($baoCao->trang_thai, ['nhap', 'yeu_cau_bo_sung'], true);
  $soVN = fn ($v) => $v !== null && $v !== '' ? \App\Models\BaoCaoTuanDuAn::so((float) $v) : '';

  $vb = fn ($cot) => old($cot, $vanBan[$cot]);
  $thieuVanBan = collect(['ten_don_vi', 'kinh_gui'])->contains(fn ($c) => trim((string) $vb($c)) === '')
    || $errors->hasAny(['ten_don_vi', 'kinh_gui', 'nguoi_ky', 'ngay_bao_cao', 'so_hieu']);
  $tomTatVanBan = collect([
    $vb('ten_don_vi'),
    $vb('so_hieu') ? 'Số '.$vb('so_hieu') : null,
    trim(($vb('dia_danh') ?: '').($vb('ngay_bao_cao') ? ', '.$vb('ngay_bao_cao') : ''), ', '),
    $vb('nguoi_ky') ? 'Người ký: '.$vb('nguoi_ky') : null,
  ])->filter()->implode(' · ');
@endphp

@section('noi_dung')
<form method="post" action="{{ route('bao-cao.luu') }}" id="formBaoCao" class="bcs" novalidate
      data-ky-truoc="tuần trước" data-xac-nhan="Gửi báo cáo {{ $ky->ten() }} cho Sở Công Thương?">
  @csrf
  <input type="hidden" name="ky" value="{{ $ky->ma() }}">

  <div class="bcs-dau">
    <div>
      <h1>Báo cáo {{ $ky->ten() }}</h1>
      <div class="bcs-dau-phu">
        Từ ngày {{ $ky->tuNgay->format('d/m/Y') }} đến ngày {{ $ky->denNgay->format('d/m/Y') }}
        @if ($baoCao) · <span class="chip {{ $baoCao->lop_chip }}">{{ $baoCao->ten_trang_thai }}</span>@endif
      </div>
    </div>
    <a href="{{ route('bao-cao.index') }}" class="btn">Về danh sách báo cáo</a>
  </div>

  <div class="bcs-than">
    <nav class="bcs-nav" aria-label="Các phần của báo cáo">
      <div class="bcs-nav-tieu-de">Các phần của báo cáo</div>
      <a href="#phan-van-ban" class="bcs-nav-muc" data-nav="van-ban">
        <span class="bcs-nav-so">≡</span><span class="bcs-nav-ten">Thông tin văn bản</span><span class="bcs-nav-dem" data-dem></span>
      </a>
      @foreach ($muc as $i => $m)
        <a href="#du-an-{{ $m['du_an']->id }}" class="bcs-nav-muc" data-nav="{{ $m['du_an']->id }}">
          <span class="bcs-nav-so">{{ \App\Models\BaoCaoTuan::soLaMa($i + 1) }}</span>
          <span class="bcs-nav-ten">{{ $m['du_an']->ten }}</span>
          <span class="bcs-nav-dem" data-dem></span>
        </a>
      @endforeach
      <div class="bcs-chu-giai">
        <div><i class="bcs-cham thieu"></i>Bắt buộc, chưa nhập</div>
        <div><i class="bcs-cham giu"></i>Giống tuần trước — cần rà soát</div>
        <div><i class="bcs-cham moi"></i>Đã cập nhật tuần này</div>
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
          <b data-tong="giu">0</b><span>mục giống {{ $truoc ? $truoc->ky()->ten() : 'tuần trước' }} — cần rà soát</span>
        </button>
        <div class="bcs-so-lieu moi">
          <b data-tong="moi">0</b><span>mục đã cập nhật tuần này</span>
        </div>
        <p class="bcs-tong-quan-ghi">
          @if ($truoc)
            Nội dung được chép sẵn từ báo cáo {{ $truoc->ky()->ten() }}. Mục <b class="mau-giu">màu vàng</b> vẫn y nguyên như tuần trước —
            đọc lại và sửa những gì đã thay đổi; mục đổi xong tự chuyển <b class="mau-moi">màu xanh</b>.
          @else
            Điền các mục bên dưới. Mục <b class="mau-thieu">màu đỏ</b> là bắt buộc khi gửi.
          @endif
        </p>
      </div>

      {{-- Thông tin văn bản: ít thay đổi giữa các tuần nên thu gọn thành 1 dòng tóm tắt, chỉ tự mở khi còn thiếu --}}
      <section class="bcs-the" id="phan-van-ban" data-phan="van-ban">
        <details class="bcs-van-ban" @if ($thieuVanBan) open @endif>
          <summary class="bcs-the-dau">
            <span class="bcs-so-la-ma">≡</span>
            <span class="bcs-the-ten">
              <h2>Thông tin văn bản</h2>
              <span class="bcs-the-meta">{{ $tomTatVanBan }}</span>
            </span>
            <span class="bcs-the-chip" data-chip></span>
            <span class="bcs-mo-them">Sửa</span>
          </summary>
          <div class="bcs-van-ban-luoi">
            <div class="bcs-o" data-o>
              <div class="bcs-o-dau"><label for="vbCoQuan">Cơ quan chủ quản (nếu có)</label><span class="bcs-nhan" data-nhan></span></div>
              <input id="vbCoQuan" name="co_quan_chu_quan" maxlength="255" value="{{ $vb('co_quan_chu_quan') }}" placeholder="VD: Tập đoàn Điện lực Việt Nam">
            </div>
            <div class="bcs-o" data-o data-bat-buoc>
              <div class="bcs-o-dau"><label for="vbDonVi">Tên đơn vị báo cáo <span class="bb">*</span></label><span class="bcs-nhan" data-nhan></span></div>
              <input id="vbDonVi" name="ten_don_vi" maxlength="255" value="{{ $vb('ten_don_vi') }}">
              @error('ten_don_vi')<span class="sai">{{ $message }}</span>@enderror
            </div>
            <div class="bcs-o" data-o>
              <div class="bcs-o-dau"><label for="vbSoHieu">Số hiệu văn bản</label><span class="bcs-nhan" data-nhan></span></div>
              <input id="vbSoHieu" name="so_hieu" maxlength="100" value="{{ $vb('so_hieu') }}" placeholder="VD: 125/BC-ABC (để trống nếu chưa lấy số)">
              @error('so_hieu')<span class="sai">{{ $message }}</span>@enderror
            </div>
            <div class="bcs-o" data-o>
              <div class="bcs-o-dau"><label for="vbDiaDanh">Địa danh</label><span class="bcs-nhan" data-nhan></span></div>
              <input id="vbDiaDanh" name="dia_danh" maxlength="100" value="{{ $vb('dia_danh') }}">
            </div>
            <div class="bcs-o" data-o>
              <div class="bcs-o-dau"><label for="vbNgay">Ngày báo cáo (ngày/tháng/năm)</label><span class="bcs-nhan" data-nhan></span></div>
              <input id="vbNgay" name="ngay_bao_cao" inputmode="numeric" maxlength="10" placeholder="dd/mm/yyyy" value="{{ $vb('ngay_bao_cao') }}">
              @error('ngay_bao_cao')<span class="sai">{{ $message }}</span>@enderror
            </div>
            <div class="bcs-o" data-o>
              <div class="bcs-o-dau"><label for="vbNguoiKy">Người ký</label><span class="bcs-nhan" data-nhan></span></div>
              <input id="vbNguoiKy" name="nguoi_ky" maxlength="255" value="{{ $vb('nguoi_ky') }}" placeholder="Họ và tên người ký">
              @error('nguoi_ky')<span class="sai">{{ $message }}</span>@enderror
            </div>
            <div class="bcs-o" data-o>
              <div class="bcs-o-dau"><label for="vbChucVu">Chức vụ người ký (mỗi dòng 1 dòng trên văn bản)</label><span class="bcs-nhan" data-nhan></span></div>
              <textarea id="vbChucVu" name="chuc_vu_ky" rows="2" maxlength="255" data-tu-gian placeholder="VD:&#10;KT. GIÁM ĐỐC&#10;PHÓ GIÁM ĐỐC">{{ $vb('chuc_vu_ky') }}</textarea>
            </div>
            <div class="bcs-o" data-o data-bat-buoc>
              <div class="bcs-o-dau"><label for="vbKinhGui">Kính gửi <span class="bb">*</span> (mỗi dòng 1 nơi)</label><span class="bcs-nhan" data-nhan></span></div>
              <textarea id="vbKinhGui" name="kinh_gui" rows="2" maxlength="1000" data-tu-gian>{{ $vb('kinh_gui') }}</textarea>
              @error('kinh_gui')<span class="sai">{{ $message }}</span>@enderror
            </div>
            <div class="bcs-o" data-o>
              <div class="bcs-o-dau"><label for="vbNoiNhan">Nơi nhận</label><span class="bcs-nhan" data-nhan></span></div>
              <textarea id="vbNoiNhan" name="noi_nhan" rows="2" maxlength="1000" data-tu-gian>{{ $vb('noi_nhan') }}</textarea>
            </div>
            <div class="bcs-o bcs-rong-het" data-o>
              <div class="bcs-o-dau"><label for="vbGioiThieu">Thông tin chung (không bắt buộc — đặt ngay sau câu mở đầu báo cáo)</label><span class="bcs-nhan" data-nhan></span></div>
              <textarea id="vbGioiThieu" name="gioi_thieu" rows="3" data-tu-gian placeholder="VD: giới thiệu chung về các dự án của đơn vị: địa điểm, tổng mức đầu tư, quy mô công suất, nguồn vốn…">{{ $vb('gioi_thieu') }}</textarea>
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
          // Tiến độ hiển thị kiểu Việt Nam "99,65" (người dùng gõ dấu phẩy hay dấu chấm đều được)
          $tdVN = fn ($v) => $v === null || $v === '' ? '' : \App\Models\BaoCaoTuanDuAn::so((float) $v, 2);
          $tienDo = old("$khoa.tien_do", $tdVN($gt['tien_do']));
        @endphp
        <section class="bcs-the bcs-du-an {{ $co ? '' : 'tat' }}" id="du-an-{{ $d->id }}" data-phan="{{ $d->id }}" data-muc>
          <header class="bcs-the-dau">
            <span class="bcs-so-la-ma">{{ \App\Models\BaoCaoTuan::soLaMa($i + 1) }}</span>
            <span class="bcs-the-ten">
              <h2>{{ $d->ten }}</h2>
              <span class="bcs-the-meta">{{ $d->ten_loai_hinh }}@if ($d->trang_thai) · {{ $d->trang_thai }}@endif @if ($d->dia_diem) · Hiện nay: {{ $d->dia_diem }}@endif @if ($d->diaDiemTruocDayDanhSach()) · Trước đây: {{ implode(', ', $d->diaDiemTruocDayDanhSach()) }}@endif</span>
            </span>
            <span class="bcs-the-chip" data-chip></span>
            <label class="bcs-chon"><input type="checkbox" name="{{ $ten }}[co]" value="1" @checked($co) data-chon-muc> Đưa vào báo cáo</label>
          </header>
          <div class="bcs-bo-qua">Dự án này không đưa vào báo cáo tuần này — đánh dấu "Đưa vào báo cáo" để nhập nội dung.</div>

          {{-- Xếp 1 cột theo đúng thứ tự văn bản: mục 1–4, mục 5 giải ngân, sau cùng là tiến độ tổng thể --}}
          <div class="bcs-the-than">
            @foreach (\App\Models\BaoCaoTuanDuAn::TRUONG_CHU as $cot => $nhan)
              @php $batBuoc = $cot === 'cong_viec_dang_lam'; @endphp
              <div class="bcs-o" data-o data-so-sanh data-truoc="{{ $tr[$cot] ?? '' }}" @if ($batBuoc) data-bat-buoc @endif>
                <div class="bcs-o-dau">
                  <label for="o-{{ $d->id }}-{{ $cot }}"><span class="bcs-o-so">{{ $loop->iteration }}</span>{{ $nhan }}@if ($batBuoc) <span class="bb">*</span>@endif</label>
                  <span class="bcs-nhan" data-nhan></span>
                </div>
                <textarea id="o-{{ $d->id }}-{{ $cot }}" name="{{ $ten }}[{{ $cot }}]" rows="3" data-tu-gian placeholder="{{ $goiY[$cot] }}">{{ old("$khoa.$cot", $gt[$cot]) }}</textarea>
                @error("$khoa.$cot")<span class="sai">{{ $message }}</span>@enderror
                @if (! empty($tr[$cot]))
                  <details class="bcs-truoc">
                    <summary>Xem nội dung {{ $tr['ky'] }}</summary>
                    <div class="bcs-truoc-noi-dung">{{ $tr[$cot] }}</div>
                  </details>
                @endif
              </div>
            @endforeach

            <div class="bcs-nhom" data-giai-ngan>
              <div class="bcs-o-dau">
                <span class="bcs-nhom-tieu-de"><span class="bcs-o-so">5</span>Công tác giải ngân <span class="bcs-don-vi">(tỷ đồng)</span></span>
              </div>
              <div class="bcs-giai-ngan">
                @foreach ([
                  'tong_muc_dau_tu' => 'Tổng mức đầu tư',
                  'ke_hoach_von_nam' => 'Kế hoạch vốn năm '.$ky->namTaiChinh(),
                  'giai_ngan_nam' => 'Đã giải ngân năm '.$ky->namTaiChinh(),
                  'luy_ke_giai_ngan' => 'Lũy kế giải ngân từ khởi công',
                ] as $cot => $nhan)
                  {{-- TMĐT, kế hoạch vốn năm hầu như không đổi theo tuần — không đánh dấu "giống tuần trước" cho đỡ nhiễu --}}
                  <div class="bcs-o bcs-o-nho" data-o data-so data-truoc="{{ $soVN($tr[$cot] ?? null) }}"
                       @if (in_array($cot, ['giai_ngan_nam', 'luy_ke_giai_ngan'], true)) data-so-sanh @endif>
                    <div class="bcs-o-dau"><label for="o-{{ $d->id }}-{{ $cot }}">{{ $nhan }}</label><span class="bcs-nhan" data-nhan></span></div>
                    <input type="text" inputmode="decimal" id="o-{{ $d->id }}-{{ $cot }}" name="{{ $ten }}[{{ $cot }}]" data-so-gn="{{ $cot }}"
                           data-dinh-dang="3" placeholder="VD: 41.130,929" value="{{ old("$khoa.$cot", $soVN($gt[$cot])) }}">
                    @if ($tr && ($tr[$cot] ?? null) !== null)<div class="bcs-goi-y-truoc">{{ $tr['ky'] }}: {{ $soVN($tr[$cot]) }}</div>@endif
                    @error("$khoa.$cot")<span class="sai">{{ $message }}</span>@enderror
                  </div>
                @endforeach
              </div>
              <p class="bcs-ti-le" data-ti-le></p>
            </div>

            <div class="bcs-o bcs-tien-do" data-o data-so-sanh data-so data-bat-buoc data-truoc="{{ $tdVN($tr['tien_do'] ?? null) }}">
              <div class="bcs-o-dau">
                <label for="o-{{ $d->id }}-tien_do"><span class="bcs-o-so">%</span>Tiến độ tổng thể dự án <span class="bb">*</span></label>
                <span class="bcs-nhan" data-nhan></span>
              </div>
              <div class="bcs-tien-do-dong">
                <div class="bcs-tien-do-nhap">
                  <input type="text" inputmode="decimal" maxlength="6" id="o-{{ $d->id }}-tien_do" name="{{ $ten }}[tien_do]" value="{{ $tienDo }}"
                         placeholder="Nhập %" data-tien-do data-dinh-dang="2">
                  <span>%</span>
                </div>
                <div class="tiendo"><i data-thanh-tien-do style="width:{{ min(100, max(0, (float) str_replace(',', '.', (string) $tienDo))) }}%"></i></div>
                @if ($tr && $tr['tien_do'] !== null)
                  <div class="bcs-goi-y-truoc">{{ $tr['ky'] }}: {{ $tdVN($tr['tien_do']) }}% <span data-chenh-lech-tien-do></span></div>
                @endif
              </div>
              @error("$khoa.tien_do")<span class="sai">{{ $message }}</span>@enderror
            </div>
          </div>
        </section>
      @endforeach
    </div>
  </div>

  <div class="bcs-chan">
    <div class="bcs-chan-tom-tat" data-tom-tat></div>
    <button type="button" class="btn" data-toi-tiep hidden>Đi tới mục cần nhập ↓</button>
    <span class="bcs-gian"></span>
    <a href="{{ route('bao-cao.index') }}" class="btn">Huỷ</a>
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
