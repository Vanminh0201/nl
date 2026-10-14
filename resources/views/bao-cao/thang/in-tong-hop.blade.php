@extends('layouts.in')
@section('tieu_de', 'Bảng tổng hợp báo cáo '.mb_strtolower($ky->ten()))

@php
  $xb = \App\Services\XuatExcelBaoCaoThang::class;
  $theoNguoi = $daGui->keyBy('nguoi_bao_cao_id');
@endphp

@section('noi_dung')
  <h1>{{ $xb::TIEU_DE }}</h1>
  <p class="phu">({{ $ky->ten() }} — tổng hợp từ báo cáo tháng của các chủ đầu tư và nội dung Sở biên tập)</p>

  <h3>I. TÌNH HÌNH GỬI BÁO CÁO THÁNG</h3>
  <p>Số chủ đầu tư phải báo cáo: {{ $phaiNop->count() }}; đã gửi: {{ $daGui->count() }}
    (đã tiếp nhận {{ $daGui->where('trang_thai', 'da_tiep_nhan')->count() }}, chờ tiếp nhận {{ $daGui->where('trang_thai', 'da_nop')->count() }},
    yêu cầu bổ sung {{ $daGui->where('trang_thai', 'yeu_cau_bo_sung')->count() }}); chưa gửi: {{ $phaiNop->reject(fn ($u) => $theoNguoi->has($u->id))->count() }}.
    Hạn gửi: ngày {{ $ky->hanNop()->format('d/m/Y') }}.</p>
  <table class="in">
    <thead><tr><th style="width:5%">STT</th><th style="width:25%">Chủ đầu tư</th><th>Dự án</th><th style="width:15%">Tình trạng</th><th style="width:14%">Thời điểm gửi</th></tr></thead>
    <tbody>
    @forelse ($phaiNop as $i => $u)
      @php $b = $theoNguoi->get($u->id); @endphp
      <tr>
        <td class="giua">{{ $i + 1 }}</td>
        <td>{{ $u->don_vi ?: $u->name }}</td>
        <td>{{ $u->duAn->pluck('ten')->join('; ') }}</td>
        <td>{{ $b ? $b->ten_trang_thai : ($ky->quaHan() ? 'Quá hạn, chưa gửi' : 'Chưa gửi') }}</td>
        <td class="giua">{{ $b?->gio('nop_luc') }}</td>
      </tr>
    @empty
      <tr><td colspan="5" class="trong">Không có chủ đầu tư nào phải báo cáo trong tháng này.</td></tr>
    @endforelse
    </tbody>
  </table>

  <div class="ngat-trang">
    <h3>II. TÌNH HÌNH TRIỂN KHAI CÁC DỰ ÁN NĂNG LƯỢNG THEO XÃ, PHƯỜNG</h3>
    <table class="in ngat-dong bct-in-xa" style="table-layout:fixed">
      <colgroup>
        @foreach ([3.5, 16.5, 10, 8.5, 18.5, 14, 12.5, 16.5] as $w)<col style="width:{{ $w }}%">@endforeach
      </colgroup>
      <thead>
        <tr>
          <th rowspan="2">TT</th><th rowspan="2">Thông tin dự án<br>(Tên, chủ đầu tư, công suất)</th><th rowspan="2">Dự kiến khởi công</th>
          <th rowspan="2">Dự kiến hoàn thành</th><th rowspan="2">Tình hình thực hiện</th><th rowspan="2">Khó khăn vướng mắc</th><th colspan="2">Kiến nghị xử lý</th>
        </tr>
        <tr><th>Xã/phường</th><th>Sở, ngành</th></tr>
      </thead>
      <tbody>
      @forelse ($bang['xa'] as $xa)
        <tr class="nhom"><td colspan="8">{{ $xa['ten'] }}</td></tr>
        @foreach ($xa['nhom'] as $nhom)
          <tr class="nhom"><td colspan="8">* {{ $nhom['ten'] }}</td></tr>
          @foreach ($nhom['muc'] as $m)
            <tr>
              <td class="giua">{{ $m['tt'] }}</td>
              <td class="chu">{{ $xb::thongTin($m) }}</td>
              @foreach ($xb::COT_NOI_DUNG as $cot)
                <td class="van">@include('bao-cao._doan-van', ['chu' => $m['noi_dung'][$cot], 'trong' => ''])</td>
              @endforeach
            </tr>
          @endforeach
        @endforeach
      @empty
        <tr><td colspan="8" class="trong">Chưa có dự án nào.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>

  <div class="ngat-trang">
    <h3>III. {{ $xb::TIEU_DE_THONG_KE }}</h3>
    @include('bao-cao.thang._thong-ke-xa', ['bang' => $bang, 'lopBang' => 'in'])
  </div>
  <style>
    /* Bảng theo xã như mẫu: chữ 10,5pt, ô canh giữa theo chiều dọc; cột hẹp không căn đều hai bên (giãn chữ) */
    table.in.bct-in-xa{font-size:10.5pt}
    table.in.bct-in-xa td{vertical-align:middle}
    table.in.bct-in-xa td.van p{text-align:left}
  </style>
@endsection
