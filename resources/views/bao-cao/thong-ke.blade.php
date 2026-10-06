@extends('layouts.app')
@section('tieu_de', 'Thống kê báo cáo')

@php
  $tiLe = fn (array $d) => $d['phai'] ? \App\Models\BaoCaoTuanDuAn::so($d['gui'] / $d['phai'] * 100, 0).'%' : '—';
  $tenKy = $chon ? mb_strtolower($chon->ten()) : 'toàn bộ các tháng ('.$dsThang[0]->ten().' – '.end($dsThang)->ten().')';
  $khoangKy = $chon ? $chon->ten() : $dsThang[0]->ten().' – '.end($dsThang)->ten();
  $oKpi = [
    ['k-tong', 'don-vi', $hang->count(), $hang->count(), 'Chủ đầu tư'],
    ['k-cho', 'tuan', $tongTuan['gui'].'/'.$tongTuan['phai'], $tongTuan['phai'], 'Báo cáo tuần đã gửi / phải gửi'],
    ['k-chua', 'chua', $tongTuan['chua'], $tongTuan['chua'], 'Kỳ tuần chưa gửi'],
    ['k-xong', 'thang', $tongThang['gui'].'/'.$tongThang['phai'], $tongThang['phai'], 'Báo cáo tháng đã gửi / phải gửi'],
    ['k-chua', 'chua', $tongThang['chua'], $tongThang['chua'], 'Kỳ tháng chưa gửi'],
  ];
@endphp

@section('noi_dung')
<div class="pad bc-trang tk-trang">
  @include('bao-cao._tab', ['dangChon' => 'bao-cao.thong-ke'])

  <div class="bc-thanh-ky">
    <form method="get" action="{{ route('bao-cao.thong-ke') }}">
      <select name="thang" onchange="this.form.submit()" aria-label="Chọn tháng thống kê">
        <option value="">Toàn bộ các tháng</option>
        @foreach (array_reverse($dsThang) as $k)
          <option value="{{ $k->ma() }}" @selected($chon && $k->bang($chon))>{{ $k->ten() }}</option>
        @endforeach
      </select>
    </form>
    <span class="hint" style="margin:0">{{ $soTuan }} tuần · {{ $soThang }} tháng</span>
    <span style="flex:1"></span>
    <button type="button" class="btn" onclick="window.print()">In thống kê</button>
  </div>

  <div class="tk-in-tieu-de">
    <b>THỐNG KÊ TÌNH HÌNH GỬI BÁO CÁO CỦA CÁC CHỦ ĐẦU TƯ</b>
    <div><i>({{ \Illuminate\Support\Str::ucfirst($tenKy) }})</i></div>
  </div>

  <div class="bc-kpi">
    @foreach ($oKpi as [$lop, $icon, $hienThi, $coSo, $nhan])
      <div class="bc-kpi-o {{ $lop }} {{ $coSo ? '' : 'rong' }}">
        <span class="bc-kpi-icon">@include('bao-cao._icon', ['ten' => $icon, 'co' => 21])</span>
        <div class="bc-kpi-noi"><b>{{ $hienThi }}</b><span>{{ $nhan }}</span></div>
      </div>
    @endforeach
  </div>

  <div class="sheet">
    <div class="sheet-hd bc-phan-hd">
      <div class="bc-phan-giua">
        <h2>Thống kê theo chủ đầu tư <span class="bc-phan-ky">({{ $khoangKy }})</span></h2>
      </div>
    </div>
    <div class="tablewrap" style="border:0;border-radius:0">
      <table class="tk-bang tk-tong">
        <colgroup>
          <col style="width:4%"><col style="width:20%">
          @for ($c = 0; $c < 14; $c++)<col style="width:5.43%">@endfor
        </colgroup>
        <thead>
          <tr>
            <th rowspan="2" class="giua">STT</th><th rowspan="2" class="trai">Chủ đầu tư</th>
            <th colspan="7" class="tk-nhom">Báo cáo tuần</th>
            <th colspan="7" class="tk-nhom">Báo cáo tháng</th>
          </tr>
          <tr>
            @foreach ([1, 2] as $_)
              <th class="giua" title="Chỉ tính các kỳ từ mốc áp dụng; kỳ đang diễn ra chỉ tính khi đã gửi">Phải gửi</th><th class="giua">Đã gửi</th><th class="giua">Tiếp nhận</th><th class="giua">Chờ</th>
              <th class="giua">Bổ sung</th><th class="giua">Chưa gửi</th><th class="giua">Tỷ lệ</th>
            @endforeach
          </tr>
        </thead>
        <tbody>
        @forelse ($hang as $i => $h)
          @php $u = $h['nguoi']; @endphp
          <tr>
            <td class="giua">{{ $i + 1 }}</td>
            <td>
              <b>{{ $u->don_vi ?: $u->name }}</b>
              <div class="hint" style="margin:0">
                {{ collect([$u->bc_tuan ? 'Báo cáo tuần' : null, $u->bc_thang ? 'Báo cáo tháng' : null])->filter()->join(', ') ?: 'Không phải báo cáo' }}
              </div>
              @if ($h['tuan']['ds'] || $h['thang']['ds'] || $h['tuan']['thieu'] || $h['thang']['thieu'])
                <details class="tk-chi-tiet">
                  <summary>Các kỳ đã gửi / chưa gửi</summary>
                  @foreach ($h['tuan']['ds'] as $b)
                    <a href="{{ route('bao-cao.show', $b->id) }}" class="chip {{ $b->lop_chip }}">{{ $b->ky()->ten() }}</a>
                  @endforeach
                  @foreach ($h['thang']['ds'] as $b)
                    <a href="{{ route('bao-cao.thang.show', $b->id) }}" class="chip {{ $b->lop_chip }}">{{ $b->ky()->ten() }}</a>
                  @endforeach
                  @foreach ($h['tuan']['thieu'] as $k)<span class="chip tk-thieu" title="Chưa gửi">✕ {{ $k->ten() }}</span>@endforeach
                  @foreach ($h['thang']['thieu'] as $k)<span class="chip tk-thieu" title="Chưa gửi">✕ {{ $k->ten() }}</span>@endforeach
                </details>
              @endif
            </td>
            @foreach (['tuan', 'thang'] as $loai)
              @php $d = $h[$loai]; @endphp
              <td class="giua tk-dau-nhom">{{ $d['phai'] }}</td>
              <td class="giua"><b>{{ $d['gui'] }}</b></td>
              <td class="giua" style="color:var(--ok)">{{ $d['da_tiep_nhan'] ?: '' }}</td>
              <td class="giua" style="color:var(--primary)">{{ $d['da_nop'] ?: '' }}</td>
              <td class="giua" style="color:var(--danger)">{{ $d['yeu_cau_bo_sung'] ?: '' }}</td>
              <td class="giua" style="color:var(--warn)">{{ $d['chua'] ?: '' }}</td>
              <td class="giua">{{ $tiLe($d) }}</td>
            @endforeach
          </tr>
        @empty
          <tr><td colspan="16" class="trong">Chưa có chủ đầu tư nào phải báo cáo.</td></tr>
        @endforelse
        </tbody>
        @if ($hang->isNotEmpty())
          <tfoot>
            <tr>
              <td></td><td><b>Tổng cộng</b></td>
              @foreach ([$tongTuan, $tongThang] as $d)
                <td class="giua tk-dau-nhom"><b>{{ $d['phai'] }}</b></td><td class="giua"><b>{{ $d['gui'] }}</b></td>
                <td class="giua">{{ $d['da_tiep_nhan'] }}</td><td class="giua">{{ $d['da_nop'] }}</td><td class="giua">{{ $d['yeu_cau_bo_sung'] }}</td>
                <td class="giua">{{ $d['chua'] }}</td><td class="giua"><b>{{ $tiLe($d) }}</b></td>
              @endforeach
            </tr>
          </tfoot>
        @endif
      </table>
    </div>
  </div>

  @unless ($chon)
    <div class="sheet">
      <div class="sheet-hd bc-phan-hd">
        <div class="bc-phan-giua">
          <h2>Thống kê theo từng tháng</h2>
        </div>
      </div>
      <div class="tablewrap" style="border:0;border-radius:0">
        <table class="tk-bang tk-thang">
          <colgroup><col style="width:24%"></colgroup>
          <thead>
            <tr>
              <th class="trai">Chủ đầu tư</th>
              @foreach ($dsThang as $k)
                <th class="giua"><a href="{{ route('bao-cao.thong-ke', ['thang' => $k->ma()]) }}">T{{ $k->thang }}/{{ $k->nam }}</a></th>
              @endforeach
            </tr>
          </thead>
          <tbody>
          @foreach ($hang as $h)
            <tr>
              <td><b>{{ $h['nguoi']->don_vi ?: $h['nguoi']->name }}</b></td>
              @foreach ($dsThang as $k)
                @php $o = $h['theo_thang'][$k->ma()]; $bt = $o['thang']; @endphp
                <td class="giua">
                  <div title="Báo cáo tuần đã gửi/phải gửi">
                    @if ($o['tuan']['phai'])
                      <span class="{{ $o['tuan']['chua'] ? 'tk-thieu-chu' : 'tk-du-chu' }}">Tuần {{ $o['tuan']['gui'] }}/{{ $o['tuan']['phai'] }}</span>
                    @else
                      <span class="hint" style="margin:0">Tuần –</span>
                    @endif
                  </div>
                  <div>
                    @if ($bt)
                      <a href="{{ route('bao-cao.thang.show', $bt->id) }}" class="chip {{ $bt->lop_chip }}">Tháng: {{ $bt->ten_trang_thai }}</a>
                    @elseif ($o['thang_phai'])
                      <span class="chip tk-thieu">Tháng: chưa gửi</span>
                    @else
                      <span class="hint" style="margin:0">Tháng –</span>
                    @endif
                  </div>
                </td>
              @endforeach
            </tr>
          @endforeach
          </tbody>
        </table>
      </div>
    </div>
  @endunless
</div>
@endsection
