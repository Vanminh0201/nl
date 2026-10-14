@extends('layouts.app')
@section('tieu_de', 'Sản lượng điện')

@php
  use App\Support\BaoCaoSanLuong as SL;
  $kyNay = $hang->first();
  // Sở bỏ chọn "Sản lượng điện" cho tài khoản (Cài đặt báo cáo): không yêu cầu gửi, không tính quá hạn
  $trangThai = function (array $h) use ($phaiBaoCao) {
    if ($h['tra_lai'] > 0) return ['do', 'Sở trả lại '.$h['tra_lai'].' dự án'];
    if ($h['phai'] && $h['gui'] >= $h['phai']) return $h['tiep_nhan'] >= $h['gui'] ? ['co', 'Sở đã tiếp nhận'] : ['cho', 'Đã gửi, chờ tiếp nhận'];
    if ($h['gui'] > 0) return ['cho', 'Gửi '.$h['gui'].'/'.$h['phai'].' dự án'];
    if ($h['nhap'] > 0) return ['cho', 'Đang soạn'];
    if (! $phaiBaoCao) return ['', 'Không yêu cầu báo cáo'];
    if ($h['truoc_ap_dung']) return ['', 'Chưa áp dụng'];
    return SL::quaHan($h['ky']) ? ['do', 'Quá hạn, chưa gửi'] : ['', 'Chưa gửi'];
  };
  // Nút mở trang nhập: chỉ "Xem" khi mọi dự án đã gửi và không có dòng bị trả lại hoặc đang soạn
  $nutSoan = function (array $h) {
    if ($h['tra_lai']) return 'Sửa, gửi lại';
    if ($h['phai'] && $h['gui'] >= $h['phai'] && ! $h['nhap']) return 'Xem';
    return $h['gui'] || $h['nhap'] ? 'Xem, sửa' : 'Nhập';
  };
  $tr = fn (?float $kwh, int $le = 2) => $kwh === null ? '' : SL::so($kwh / 1e6, $le);
  $phanTram = fn (?float $p) => $p === null ? '' : ($p >= 0 ? '▲ +' : '▼ −').SL::so(abs($p), 1).'%';
  $lopBien = fn (?float $p) => $p === null ? '' : ($p >= 0 ? 'tang' : 'giam');
  $loc = array_filter(['nam' => $nam, 'loai_hinh' => $loaiHinh]);
@endphp

@section('noi_dung')
<div class="pad bc-trang sl-trang">
  @include('bao-cao._tab', ['dangChon' => 'bao-cao.san-luong.index'])
  @if ($errors->any())
    <div class="loi" style="margin:0">{{ $errors->first() }}</div>
  @endif

  <section class="sheet tk-so-sanh sl-bao-cao-ky">
    <div class="sheet-hd">
      <div>
        <h2>Báo cáo sản lượng điện {{ mb_strtolower($kyCanBao->ten()) }}</h2>
        <p class="hint">Hạn gửi ngày {{ SL::hanNop($kyCanBao)->format('d/m/Y') }} · sản lượng nhập theo kWh</p>
      </div>
      @if ($dsDuAn->isNotEmpty())
        @php [$lop, $ten] = $trangThai($kyNay); @endphp
        <span class="chip {{ $lop }}">{{ $ten }}</span>
      @endif
    </div>
    <div class="sheet-bd sl-bao-cao-ky-than">
      @if ($dsDuAn->isEmpty())
        <p class="hint" style="margin:0">Tài khoản chưa được gán dự án nguồn điện đã vận hành — liên hệ Sở Công Thương để được gán dự án.</p>
      @else
        <div class="sl-bao-cao-ky-nut">
          <a href="{{ route('bao-cao.san-luong.soan', ['ky' => $kyCanBao->ma()]) }}" class="btn primary bc-nut">{{ ['Sửa, gửi lại' => 'Sửa báo cáo bị trả lại', 'Xem' => 'Xem báo cáo tháng này', 'Xem, sửa' => 'Tiếp tục báo cáo tháng này', 'Nhập' => 'Nhập sản lượng tháng này'][$nutSoan($kyNay)] }}</a>
          <form method="get" action="{{ route('bao-cao.san-luong.soan') }}" class="sl-thang-khac">
            <input type="month" name="ky" max="{{ $kyCanBao->ma() }}" required aria-label="Tháng cần nhập">
            <button class="btn bc-nut">Nhập tháng khác</button>
          </form>
          @if ($kyNay['gui_luc'])<span class="hint">Gửi lúc {{ $kyNay['gui_luc']->copy()->setTimezone(\App\Support\KyBaoCao::MUI_GIO)->format('d/m/Y H:i') }}</span>@endif
        </div>
        <div class="sl-du-an-bao-cao">
          <span>Dự án báo cáo ({{ $dsDuAn->count() }}):</span>
          @foreach ($dsDuAn as $d)<span class="chip">{{ $d->ten }}</span>@endforeach
        </div>
      @endif
    </div>
  </section>

  @if ($dsDuAn->isNotEmpty())
    <section class="sheet tk-so-sanh">
      <div class="sheet-hd">
        <div>
          <h2>Lịch sử gửi báo cáo 12 tháng gần nhất</h2>
          <p class="hint">Tổng sản lượng các dự án đã gửi trong từng tháng (kWh)</p>
        </div>
      </div>
      <div class="tablewrap">
        <table class="sl-bang">
          <thead>
            <tr><th>Tháng</th><th class="giua">Hạn gửi</th><th class="giua">Dự án đã gửi</th><th class="num">Tổng sản lượng (kWh)</th><th class="giua">Trạng thái</th><th class="giua"></th></tr>
          </thead>
          <tbody>
          @foreach ($hang as $h)
            @php [$lop, $ten] = $trangThai($h); @endphp
            <tr>
              <td><b>{{ $h['ky']->ten() }}</b></td>
              <td class="giua">{{ $h['han']->format('d/m/Y') }}</td>
              <td class="giua">{{ $h['gui'] }}/{{ $h['phai'] }}</td>
              <td class="num">{{ SL::so($h['tong'] ?: null) }}</td>
              <td class="giua"><span class="chip {{ $lop }}">{{ $ten }}</span></td>
              <td class="giua"><a href="{{ route('bao-cao.san-luong.soan', ['ky' => $h['ky']->ma()]) }}" class="btn sm">{{ $nutSoan($h) }}</a></td>
            </tr>
          @endforeach
          </tbody>
        </table>
      </div>
    </section>
  @endif

  <div class="bc-thanh-ky sl-thanh-cong-cu">
    <span class="sl-thanh-tieu-de">@include('bao-cao._icon', ['ten' => 'bieu-do', 'co' => 20])Thống kê sản lượng</span>
    <form method="get" class="sl-loc-loai-hinh">
      <select name="nam" onchange="this.form.submit()" aria-label="Năm thống kê">
        @foreach ($dsNam as $n)<option value="{{ $n }}" @selected($n === $nam)>Năm {{ $n }}</option>@endforeach
      </select>
      @if ($loaiHinhCo->count() > 1)
        <select name="loai_hinh" onchange="this.form.submit()" aria-label="Loại hình nguồn điện">
          <option value="">Tất cả nguồn điện</option>
          @foreach ($loaiHinhCo as $lh)<option value="{{ $lh }}" @selected($lh === $loaiHinh)>{{ $dsLoaiHinh[$lh]->ten ?? $lh }}</option>@endforeach
        </select>
      @endif
      <noscript><button class="btn bc-nut">Xem</button></noscript>
    </form>
    <span class="bc-ky-gian"></span>
    <a href="{{ route('bao-cao.san-luong.excel', $loc) }}" class="btn primary bc-nut">@include('bao-cao._icon', ['ten' => 'tai'])Tải Excel năm {{ $nam }}</a>
  </div>

  <section class="tk-tong-quan sl-tong-quan sl-tong-quan-3" aria-label="Tổng quan sản lượng điện năm {{ $nam }}">
    <div class="tk-tong-quan-dau">
      <span class="tk-tong-quan-nhan">SẢN LƯỢNG ĐIỆN · DỰ ÁN CỦA ĐƠN VỊ</span>
      <h1>Năm {{ $nam }}</h1>
      <p>Số liệu tổng hợp từ báo cáo sản lượng đã gửi Sở.</p>
    </div>
    <div class="tk-tong-quan-chi-so">
      <article class="tk-chi-so">
        <span class="tk-chi-so-nhan">Lũy kế năm {{ $nam }}{{ $thangCuoi ? ' (T1–T'.$thangCuoi.')' : '' }}</span>
        <strong>{{ $kpi['luy_ke'] > 0 ? $tr($kpi['luy_ke']) : '—' }}@if ($kpi['luy_ke'] > 0)<span> triệu kWh</span>@endif</strong>
        <small>Cùng kỳ {{ $nam - 1 }}: {{ $kpi['cung_ky'] > 0 ? $tr($kpi['cung_ky']) : 'chưa có số liệu' }}</small>
        @if ($kpi['so_cung_ky'] !== null)<small class="sl-chi-so-ss"><b class="{{ $lopBien($kpi['so_cung_ky']) }}">{{ $phanTram($kpi['so_cung_ky']) }}</b> so cùng kỳ</small>@endif
      </article>
      <article class="tk-chi-so">
        <span class="tk-chi-so-nhan">Tháng {{ $thangCuoi ?: '—' }}/{{ $nam }}</span>
        <strong>{{ $thangCuoi ? $tr($kpi['thang_gan_nhat']) : '—' }}@if ($thangCuoi)<span> triệu kWh</span>@endif</strong>
        <small>{{ $kpi['du_an_thang_gan_nhat'] }}/{{ $kpi['so_du_an'] }} dự án có số liệu</small>
      </article>
      <article class="tk-chi-so">
        <span class="tk-chi-so-nhan">Công suất đang phát điện</span>
        <strong>{{ SL::so($kpi['cong_suat'], 1) }}<span> MW</span></strong>
        <small>{{ $kpi['so_du_an'] }} dự án</small>
      </article>
    </div>
  </section>

  @include('bao-cao.san-luong._thong-ke', ['loc' => $loc, 'thangChon' => $nam === $kyCanBao->nam ? $kyCanBao->thang : null])
</div>
@endsection
