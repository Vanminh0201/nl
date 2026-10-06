@extends('layouts.app')
@section('tieu_de', 'Báo cáo tháng')

@section('noi_dung')
<div class="pad bc-trang">
  @include('bao-cao._tab', ['dangChon' => 'bao-cao.thang.index'])
  @if ($errors->any())
    <div class="loi" style="margin:0">{{ $errors->first() }}</div>
  @endif

  @foreach ($canBoSung as $b)
    <div class="bc-canh-bao">
      <div style="flex:1;min-width:240px">
        <b>Sở yêu cầu bổ sung báo cáo {{ mb_strtolower($b->ky()->ten()) }}</b>
        <span class="hint" style="margin:0">({{ $b->gio('xu_ly_luc') }})</span>
        <div style="white-space:pre-line;margin-top:3px">{{ $b->y_kien_xu_ly }}</div>
      </div>
      <a href="{{ route('bao-cao.thang.soan', ['ky' => $b->ky()->ma()]) }}" class="btn primary sm">Bổ sung báo cáo</a>
    </div>
  @endforeach

  <div class="sheet">
    <div class="sheet-hd">
      <h2>Báo cáo {{ mb_strtolower($kyNay->ten()) }}</h2>
      <div class="hint" style="margin-top:2px">
        Theo mẫu bảng "Tình hình triển khai quy hoạch điện VIII điều chỉnh, quy hoạch tỉnh điều chỉnh" · hạn gửi ngày {{ $kyNay->hanNop()->format('d/m/Y') }}
      </div>
    </div>
    <div class="sheet-bd" style="display:flex;flex-direction:column;gap:10px">
      @if ($dsDuAn->isEmpty())
        <p class="hint" style="margin:0">Tài khoản chưa được gán dự án nào — liên hệ Sở Công Thương để được gán dự án trước khi lập báo cáo.</p>
      @elseif (! $phaiBaoCao && ! $baoCaoKyNay)
        <p class="hint" style="margin:0">Sở Công Thương hiện không yêu cầu tài khoản này báo cáo tháng.</p>
      @elseif (! $baoCaoKyNay)
        @unless ($kyNay->somHon($batDau))
          <div>
            <span class="chip {{ $kyNay->quaHan() ? 'do' : '' }}">{{ $kyNay->quaHan() ? 'Quá hạn' : 'Chưa lập' }}</span>
            Bạn chưa lập báo cáo tháng này.
          </div>
        @endunless
        <div><a href="{{ route('bao-cao.thang.soan') }}" class="btn primary">Lập báo cáo tháng này</a></div>
      @else
        <div>
          <span class="chip {{ $baoCaoKyNay->lop_chip }}">{{ $baoCaoKyNay->ten_trang_thai }}</span>
          @if ($baoCaoKyNay->nop_luc)
            <span class="hint">Gửi lúc {{ $baoCaoKyNay->gio('nop_luc') }}</span>
          @else
            <span class="hint">Đang lưu nháp, Sở chưa nhận được báo cáo.</span>
          @endif
        </div>
        @if ($baoCaoKyNay->y_kien_xu_ly)
          <div class="hint" style="margin:0;white-space:pre-line">Ý kiến của Sở: {{ $baoCaoKyNay->y_kien_xu_ly }}</div>
        @endif
        <div style="display:flex;gap:7px;flex-wrap:wrap">
          @if ($baoCaoKyNay->conSuaDuoc())
            <a href="{{ route('bao-cao.thang.soan', ['ky' => $kyNay->ma()]) }}" class="btn primary">
              {{ ['nhap' => 'Tiếp tục soạn', 'yeu_cau_bo_sung' => 'Bổ sung báo cáo', 'da_nop' => 'Sửa báo cáo đã gửi'][$baoCaoKyNay->trang_thai] ?? 'Sửa' }}
            </a>
          @endif
          <a href="{{ route('bao-cao.thang.show', $baoCaoKyNay) }}" class="btn">Xem, in</a>
          <a href="{{ route('bao-cao.thang.excel', $baoCaoKyNay) }}" class="btn">Tải Excel theo mẫu</a>
        </div>
      @endif
      @if ($dsDuAn->isNotEmpty())
        <div class="hint" style="margin:0">Dự án thuộc tài khoản: {{ $dsDuAn->pluck('ten')->join('; ') }}</div>
      @endif
    </div>
  </div>

  <div class="sheet">
    <div class="sheet-hd"><h2>Các báo cáo tháng đã lập</h2></div>
    <div class="tablewrap" style="border:0;border-radius:0">
      <table>
        <thead>
          <tr><th>Kỳ báo cáo</th><th class="num">Số dự án</th><th>Trạng thái</th><th>Gửi lúc</th><th>Ý kiến của Sở</th><th></th></tr>
        </thead>
        <tbody>
        @forelse ($dsBaoCao as $b)
          <tr>
            <td><b>{{ $b->ky()->ten() }}</b></td>
            <td class="num">{{ $b->chi_tiet_count }}</td>
            <td><span class="chip {{ $b->lop_chip }}">{{ $b->ten_trang_thai }}</span></td>
            <td>{{ $b->gio('nop_luc') }}</td>
            <td style="white-space:pre-line;max-width:420px">{{ $b->y_kien_xu_ly }}</td>
            <td style="white-space:nowrap">
              <a href="{{ route('bao-cao.thang.show', $b) }}" class="btn sm">Xem</a>
              @if ($b->conSuaDuoc())
                <a href="{{ route('bao-cao.thang.soan', ['ky' => $b->ky()->ma()]) }}" class="btn sm">Sửa</a>
              @endif
              <a href="{{ route('bao-cao.thang.excel', $b) }}" class="btn sm">Excel</a>
            </td>
          </tr>
        @empty
          <tr><td colspan="6" class="trong">Chưa có báo cáo tháng nào.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
    <div style="padding:12px 18px">{{ $dsBaoCao->links('vendor.pagination.don-gian', ['donVi' => 'báo cáo']) }}</div>
  </div>
</div>
@endsection
