@extends('layouts.app')
@section('tieu_de', 'Nhập sản lượng điện')

@php
  use App\Support\BaoCaoSanLuong as SL;
  $stt = 1;
@endphp

@section('noi_dung')
<div class="pad bc-trang">
  @include('bao-cao._tab', ['dangChon' => 'bao-cao.san-luong.index'])
  @if ($errors->any())
    <div class="loi" style="margin:0">
      @foreach (array_slice($errors->all(), 0, 6) as $loi)<div>{{ $loi }}</div>@endforeach
    </div>
  @endif

  <form method="post" action="{{ route('bao-cao.san-luong.luu') }}" class="sheet">
    @csrf
    <input type="hidden" name="ky" value="{{ $ky->ma() }}">
    <div class="sheet-hd bc-phan-hd">
      <div class="bc-phan-giua">
        <h2>Sản lượng điện {{ mb_strtolower($ky->ten()) }} (kWh)</h2>
        <div class="hint" style="margin-top:2px">Hạn gửi ngày {{ SL::hanNop($ky)->format('d/m/Y') }}</div>
      </div>
      <div class="bc-phan-phai" style="display:flex;gap:7px;flex-wrap:wrap">
        <a href="{{ route('bao-cao.san-luong.soan', ['ky' => $ky->truoc()->ma()]) }}" class="btn">@include('bao-cao._icon', ['ten' => 'trai'])Tháng trước</a>
        @if (SL::nhapDuoc($ky->sau()))
          <a href="{{ route('bao-cao.san-luong.soan', ['ky' => $ky->sau()->ma()]) }}" class="btn">Tháng sau@include('bao-cao._icon', ['ten' => 'phai'])</a>
        @endif
      </div>
    </div>
    <div class="tablewrap" style="border:0;border-radius:0">
      <table class="sl-bang sl-nhap">
        <thead>
          <tr>
            <th class="giua">STT</th><th>Dự án</th><th class="num">Công suất (MW)</th><th class="num">Sản lượng (kWh)</th>
            <th class="num">{{ $ky->truoc()->ten() }}</th><th class="num">Tháng {{ $ky->thang }}/{{ $ky->nam - 1 }}</th><th>Ghi chú</th>
          </tr>
        </thead>
        <tbody>
        @foreach ($hang as $h)
          @php $d = $h['du_an']; $dong = $h['dong']; $khoa = (bool) $dong?->khoa(); @endphp
          <tr>
            <td class="giua sl-stt">{{ $stt++ }}</td>
            <td class="sl-ten-o">
              <b>{{ $d->ten }}</b>
              @if (SL::phatDienThu($d))<div class="sl-phu">Phát điện thử nghiệm từ {{ mb_strtolower(\App\Support\KyThang::tuMa($d->phat_dien_tu)?->ten() ?? '') }}</div>@elseif ($d->trang_thai !== SL::TRANG_THAI_VAN_HANH)<div class="sl-phu">{{ $d->trang_thai }}</div>@endif
              @if ($dong?->trang_thai === 'tra_lai')
                <div class="sl-phu sl-tra-lai">Sở trả lại: {{ $dong->y_kien_xu_ly }}</div>
              @elseif ($dong?->trang_thai === 'da_tiep_nhan')
                <div class="sl-phu">Sở đã tiếp nhận {{ ($dong->xu_ly_luc ?? $dong->gui_luc)?->copy()->setTimezone(\App\Support\KyBaoCao::MUI_GIO)->format('d/m/Y H:i') }}</div>
              @elseif ($dong?->trang_thai === 'da_gui')
                <div class="sl-phu">Đã gửi {{ $dong->gui_luc?->copy()->setTimezone(\App\Support\KyBaoCao::MUI_GIO)->format('d/m/Y H:i') }}, chờ Sở tiếp nhận</div>
              @elseif ($dong)
                <div class="sl-phu">Đang lưu nháp</div>
              @endif
            </td>
            <td class="num" data-nhan="Công suất (MW)">{{ SL::so($d->cong_suat_mw, 2) }}</td>
            <td class="num" data-nhan="Sản lượng (kWh)">
              @if ($khoa)
              <span class="sl-khoa">{{ SL::so($dong->san_luong) }}</span>
              @else
              <input class="sl-so {{ $errors->has("sl.{$d->id}.san_luong") ? 'sai' : '' }}" name="sl[{{ $d->id }}][san_luong]"
                     value="{{ old("sl.{$d->id}.san_luong", SL::so($dong?->san_luong)) }}" inputmode="decimal" autocomplete="off" aria-label="Sản lượng — {{ $d->ten }}">
              @endif
            </td>
            <td class="num sl-cu" data-nhan="{{ $ky->truoc()->ten() }}">{{ SL::so($h['truoc']?->san_luong) }}</td>
            <td class="num sl-cu" data-nhan="Tháng {{ $ky->thang }}/{{ $ky->nam - 1 }}">{{ SL::so($h['cung_ky']?->san_luong) }}</td>
            <td class="sl-gc-o">
              @if ($khoa)
              <span class="sl-khoa-gc">{{ $dong->ghi_chu }}</span>
              @else
              <input class="sl-gc" name="sl[{{ $d->id }}][ghi_chu]" value="{{ old("sl.{$d->id}.ghi_chu", $dong?->ghi_chu) }}" maxlength="2000"
                     placeholder="VD: dừng máy bảo dưỡng, cắt giảm công suất…" aria-label="Ghi chú — {{ $d->ten }}">
              @endif
            </td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>
    <div class="sheet-ft">
      <a href="{{ route('bao-cao.san-luong.index') }}" class="btn">Quay lại</a>
      @if ($coSua)
        <button class="btn" name="hanh_dong" value="nhap">Lưu nháp</button>
        <button class="btn primary" name="hanh_dong" value="gui">{{ $coTraLai ? 'Gửi lại cho Sở' : 'Gửi báo cáo cho Sở' }}</button>
      @endif
    </div>
  </form>
</div>
@endsection

@include('bao-cao.san-luong._nhap-js')

@push('dau_trang')
<style>
  .sl-tra-lai{color:var(--danger);white-space:pre-line}
  .sl-khoa{display:inline-block;padding:6px 2px;font-weight:600}
  .sl-khoa-gc{display:inline-block;padding:6px 2px;color:var(--ink-2)}
</style>
@endpush
