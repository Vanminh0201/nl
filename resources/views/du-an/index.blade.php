@extends('layouts.app')
@section('tieu_de', 'Danh sách dự án công nghiệp')

@php
  $cot = [
    'ten' => 'Tên dự án',
    'cong_suat_mw' => 'Công suất (MW)',
    'loai_hinh' => 'Loại hình',
    'dia_diem' => 'Địa điểm',
    'chu_dau_tu' => 'Chủ đầu tư',
    'so_van_ban' => 'Chủ trương đầu tư',
    'von_dang_ky' => 'Vốn (tỷ)',
    'dien_tich' => 'Diện tích (ha)',
    'tien_do_van_hanh' => 'Hoàn thành',
    'trang_thai' => 'Trạng thái',
    'tien_do' => 'Tiến độ (%)',
  ];

  // Cac cot du lieu dang so — can le phai ca tieu de lan gia tri de thang hang voi nhau
  $cotSo = ['cong_suat_mw', 'von_dang_ky', 'dien_tich', 'tien_do'];

  // Chi hien mo ta cong suat ben duoi con so khi no co thong tin gi do KHAC ngoai viec
  // lap lai chinh con so kem "MW" (VD "48 MW" thi khong hien, "10 MW (de nghi +5MW)" thi hien).
  $moTaCongSuatKhac = function ($d) {
    if (! $d->cong_suat) {
      return null;
    }
    $con = preg_replace('/^\s*[\d.,]+\s*MW\s*/i', '', $d->cong_suat);
    $con = trim($con, " \t\n\r\0\x0B-–—()");

    return $con !== '' ? $d->cong_suat : null;
  };

  // Dinh dang so: bo phan thap phan neu chi la ",00" (so nguyen), giu lai neu co so le thuc su.
  $soGon = function ($n, $soLe = 2) {
    if (! $n) {
      return '';
    }
    $n = (float) $n;
    $ntron = round($n, $soLe);

    return $ntron == (int) $ntron
      ? number_format($ntron, 0, ',', '.')
      : number_format($ntron, $soLe, ',', '.');
  };
@endphp

@section('noi_dung')
<div class="pad">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
    <h2 style="font-size:16px;margin:0">Danh sách dự án công nghiệp</h2>
    @if (auth()->user()->duocSua())
      <a href="{{ route('du-an.create') }}" class="btn primary">Thêm dự án</a>
    @endif
  </div>

  <form method="get" class="thanhloc">
    <label class="grow">Từ khoá
      <input type="search" name="tu_khoa" value="{{ is_scalar(request('tu_khoa')) ? request('tu_khoa') : '' }}" placeholder="Tên dự án, chủ đầu tư, địa điểm">
    </label>
    <label>Loại hình
      <select name="loai_hinh">
        <option value="">Tất cả</option>
        @foreach ($dsLoaiHinh as $ma => $l)
          <option value="{{ $ma }}" @selected(request('loai_hinh') === $ma)>{{ $l->ten }}</option>
        @endforeach
      </select>
    </label>
    <label>Trạng thái
      <select name="trang_thai">
        <option value="">Tất cả</option>
        @foreach ($dsTrangThai as $ten => $t)
          <option value="{{ $ten }}" @selected(request('trang_thai') === $ten)>{{ $ten }}</option>
        @endforeach
      </select>
    </label>
    <label>Địa điểm
      <select name="dia_diem">
        <option value="">Tất cả</option>
        @foreach ($diaDiem as $dd)
          <option value="{{ $dd }}" @selected(request('dia_diem') === $dd)>{{ $dd }}</option>
        @endforeach
      </select>
    </label>
    @php
      // Đường dẫn cũ (?pl_chu_truong=1/0) vẫn chọn đúng mục tương ứng
      $hoSoChon = request('ho_so') ?: (in_array(request('pl_chu_truong'), ['0', '1'], true) ? 'pl_chu_truong:'.request('pl_chu_truong') : '');
    @endphp
    <label>Hồ sơ pháp lý
      <select name="ho_so">
        <option value="">Tất cả</option>
        <optgroup label="Đã có hồ sơ">
          @foreach (App\Models\DuAn::HO_SO_PHAP_LY as $maHs => $tenHs)
            <option value="{{ $maHs }}:1" @selected($hoSoChon === $maHs.':1')>Đã có: {{ $tenHs }}</option>
          @endforeach
        </optgroup>
        <optgroup label="Còn thiếu hồ sơ">
          @foreach (App\Models\DuAn::HO_SO_PHAP_LY as $maHs => $tenHs)
            <option value="{{ $maHs }}:0" @selected($hoSoChon === $maHs.':0')>Chưa có: {{ $tenHs }}</option>
          @endforeach
        </optgroup>
      </select>
    </label>
    <button class="btn primary">Lọc</button>
    <a href="{{ route('du-an.index') }}" class="btn">Bỏ lọc</a>
    <a href="{{ route('xuat.excel', request()->query()) }}" class="btn">Xuất Excel</a>
    <a href="{{ route('xuat.geojson', request()->query()) }}" class="btn">Xuất GeoJSON</a>
  </form>

  <div class="tablewrap">
    <table>
      <thead>
        <tr>
          @foreach ($cot as $ma => $nhan)
            @php
              $dangSap = $sapXep === $ma;
              $chieuMoi = $dangSap && $chieu === 'asc' ? 'giam' : 'tang';
            @endphp
            <th class="@if (in_array($ma, $cotSo, true)) num @endif">
              <a href="{{ route('du-an.index', array_merge(request()->query(), ['sap_xep' => $ma, 'chieu' => $chieuMoi])) }}">
                <span>{{ $nhan }}</span>
                <span class="fleche @if ($dangSap) dang-sap @endif">{{ $dangSap ? ($chieu === 'asc' ? '↑' : '↓') : '⇅' }}</span>
              </a>
            </th>
          @endforeach
          @if (auth()->user()->duocSua())
            <th>Thao tác</th>
          @endif
        </tr>
      </thead>
      <tbody>
      @forelse ($duAn as $d)
        <tr onclick="location.href='{{ route('du-an.show', $d) }}'" style="cursor:pointer">
          <td><b>{{ $d->ten }}</b></td>
          <td class="num">
            {{ $soGon($d->cong_suat_mw) }}
            @if ($moTaCongSuatKhac($d))
              <div style="font-size:11px;color:var(--ink-2);font-weight:normal;text-align:left;white-space:normal">{{ $moTaCongSuatKhac($d) }}</div>
            @endif
          </td>
          <td><span class="tag"><i style="background:{{ $d->mau }}"></i>{{ $d->ten_loai_hinh }}</span></td>
          <td>{{ $d->dia_diem }}</td>
          <td>{{ $d->chu_dau_tu }}</td>
          <td onclick="event.stopPropagation()">
            @if ($d->vanBanChinh?->tep)
              <a href="{{ $d->vanBanChinh->tep_url }}" target="_blank" rel="noopener">{{ $d->vanBanChinh->so_van_ban ?: 'Xem văn bản' }}</a>
            @else
              {{ $d->vanBanChinh->so_van_ban ?? '' }}
            @endif
          </td>
          <td class="num">{{ $soGon($d->von_dang_ky) }}</td>
          <td class="num">{{ $soGon($d->dien_tich) }}</td>
          <td>{{ $d->tien_do_van_hanh ?: $d->ngay_hoan_thanh?->format('d/m/Y') }}</td>
          <td>{{ $d->trang_thai }}</td>
          <td class="num">{{ $d->tien_do !== null ? \App\Models\BaoCaoTuanDuAn::so($d->tien_do, 2) : '' }}</td>
          @if (auth()->user()->duocSua())
            <td style="white-space:nowrap">
              <a href="{{ route('du-an.edit', $d) }}" class="btn sm" onclick="event.stopPropagation()">Sửa</a>
            </td>
          @endif
        </tr>
      @empty
        <tr><td colspan="{{ count($cot) + (auth()->user()->duocSua() ? 1 : 0) }}" style="padding:26px;color:var(--ink-2)">
          Chưa có dự án nào khớp điều kiện lọc.
        </td></tr>
      @endforelse
      </tbody>
      @if ($duAn->isNotEmpty())
        <tfoot>
          <tr>
            @foreach ($cot as $ma => $nhan)
              @if ($ma === 'ten')
                <td><b>Tổng cộng</b></td>
              @elseif ($ma === 'cong_suat_mw')
                <td class="num"><b>{{ $soGon($tongCongSuat, 0) }}</b></td>
              @elseif ($ma === 'von_dang_ky')
                <td class="num"><b>{{ $soGon($tongVon) }}</b></td>
              @elseif ($ma === 'dien_tich')
                <td class="num"><b>{{ $soGon($tongDienTich) }}</b></td>
              @else
                <td></td>
              @endif
            @endforeach
            @if (auth()->user()->duocSua())
              <td></td>
            @endif
          </tr>
        </tfoot>
      @endif
    </table>
  </div>

  <div style="margin-top:12px">{{ $duAn->links('vendor.pagination.don-gian') }}</div>
</div>
@endsection
