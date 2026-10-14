@extends('layouts.app')
@section('tieu_de', 'Danh sách dự án công nghiệp')

@php
  $cot = [
    'ten' => 'Tên dự án',
    'cong_suat_mw' => 'Công suất (MW)',
    'loai_hinh' => 'Loại hình',
    'dia_diem_truoc_day' => 'Địa điểm trước đây',
    'dia_diem' => 'Địa điểm hiện nay',
    'chu_dau_tu' => 'Chủ đầu tư',
    'trang_thai' => 'Trạng thái',
    'von_dang_ky' => 'Vốn (tỷ)',
    'dien_tich' => 'Diện tích (ha)',
    'tien_do_van_hanh' => 'Hoàn thành',
    'van_ban_phap_ly' => 'Văn bản pháp lý',
    'tien_do' => 'Tiến độ (%)',
  ];

  // Cac cot du lieu dang so — can le phai ca tieu de lan gia tri de thang hang voi nhau
  $cotSo = ['cong_suat_mw', 'von_dang_ky', 'dien_tich', 'tien_do'];

  $hoSoPhapLy = App\Models\DuAn::HO_SO_PHAP_LY;
  $thuTuHoSo = array_flip(array_keys($hoSoPhapLy));

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
    <label>Xã/phường
      <select name="dia_diem">
        <option value="">Tất cả</option>
        @foreach ($diaDiem as $dd)
          <option value="{{ $dd }}" @selected(request('dia_diem') === $dd)>{{ $dd }}</option>
        @endforeach
        @if ($diaDiemCu->isNotEmpty())
          <optgroup label="Địa điểm cũ trong dữ liệu">
            @foreach ($diaDiemCu as $ddCu)
              <option value="{{ $ddCu }}" @selected(request('dia_diem') === $ddCu)>{{ $ddCu }}</option>
            @endforeach
          </optgroup>
        @endif
      </select>
    </label>
    <label>Địa điểm trước đây
      <select name="dia_diem_truoc_day">
        <option value="">Tất cả</option>
        @foreach ($diaDiemTruocDay as $tinh => $dsHuyen)
          <optgroup label="{{ $tinh }}">
            @foreach ($dsHuyen as $huyen => $dsDiaDiem)
              @foreach ($dsDiaDiem as $ddCu)
                <option value="{{ $ddCu }}" @selected(request('dia_diem_truoc_day') === $ddCu)>{{ explode(' — ', $ddCu, 2)[0] }} ({{ $huyen }})</option>
              @endforeach
            @endforeach
          </optgroup>
        @endforeach
        @if ($diaDiemTruocDayCu->isNotEmpty())
          <optgroup label="Giá trị cũ chưa đối chiếu">
            @foreach ($diaDiemTruocDayCu as $ddCu)
              <option value="{{ $ddCu }}" @selected(request('dia_diem_truoc_day') === $ddCu)>{{ $ddCu }}</option>
            @endforeach
          </optgroup>
        @endif
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
              @if (in_array($ma, ['van_ban_phap_ly', 'dia_diem_truoc_day'], true))
                <span>{{ $nhan }}</span>
              @else
                <a href="{{ route('du-an.index', array_merge(request()->query(), ['sap_xep' => $ma, 'chieu' => $chieuMoi])) }}">
                  <span>{{ $nhan }}</span>
                  <span class="fleche @if ($dangSap) dang-sap @endif">{{ $dangSap ? ($chieu === 'asc' ? '↑' : '↓') : '⇅' }}</span>
                </a>
              @endif
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
          <td>{{ implode(', ', $d->diaDiemTruocDayDanhSach()) }}</td>
          <td>{{ $d->dia_diem }}</td>
          <td>{{ $d->chu_dau_tu }}</td>
          <td>{{ $d->trang_thai }}</td>
          <td class="num">{{ $soGon($d->von_dang_ky) }}</td>
          <td class="num">{{ $soGon($d->dien_tich) }}</td>
          <td>{{ $d->tien_do_van_hanh ?: $d->ngay_hoan_thanh?->format('d/m/Y') }}</td>
          <td class="du-an-van-ban-cell" onclick="event.stopPropagation()">
            @php
              // Văn bản pháp lý và tệp hồ sơ kiểu cũ, nhóm theo hồ sơ đúng thứ tự 8 hồ sơ — hiện trong hộp "Xem"
              $nhomVanBan = $d->vanBanPhapLy
                ->map(fn ($vb) => ['ho_so' => $vb->ho_so, 'loai' => $vb->loai, 'so' => $vb->so_van_ban ?: ($vb->ten_tep ?: 'Văn bản chưa có số'),
                  'ngay' => $vb->ngay_van_ban, 'url' => $vb->tep_url])
                ->concat($d->hoSoTep->map(fn ($tep) => ['ho_so' => $tep->loai, 'loai' => null, 'so' => $tep->so_van_ban ?: $tep->ten_tep,
                  'ngay' => $tep->ngay_van_ban, 'url' => route('ho-so-tep.show', $tep)]))
                ->groupBy('ho_so')
                ->sortBy(fn ($ds, $hoSo) => $thuTuHoSo[$hoSo] ?? 99);
            @endphp
            @if ($nhomVanBan->isEmpty())
              <span class="du-an-van-ban-empty">Chưa có văn bản</span>
            @else
              <button type="button" class="btn sm du-an-van-ban-xem" data-xem-van-ban="{{ $d->id }}" data-ten="{{ $d->ten }}" data-url="{{ route('du-an.show', $d) }}">
                Xem<span>{{ $nhomVanBan->flatten(1)->count() }}</span>
              </button>
              <template id="vanBan{{ $d->id }}">
                @foreach ($nhomVanBan as $hoSo => $dsVanBan)
                  <section class="hop-van-ban-nhom">
                    <h3>{{ $hoSoPhapLy[$hoSo] ?? $hoSo }}<span>{{ $dsVanBan->count() }}</span></h3>
                    <ul>
                      @foreach ($dsVanBan as $vb)
                        <li>
                          <div class="hop-van-ban-so">
                            @if ($vb['url'])
                              <a href="{{ $vb['url'] }}" target="_blank" rel="noopener">{{ $vb['so'] }}</a>
                            @else
                              {{ $vb['so'] }}
                            @endif
                            @if ($vb['loai'])<small>{{ $vb['loai'] }}</small>@endif
                          </div>
                          <span class="hop-van-ban-ngay">{{ $vb['ngay']?->format('d/m/Y') }}</span>
                          @if ($vb['url'])
                            <a href="{{ $vb['url'] }}" target="_blank" rel="noopener" class="btn sm">Mở tệp</a>
                          @else
                            <span class="hop-van-ban-chua-tep">Chưa có tệp</span>
                          @endif
                        </li>
                      @endforeach
                    </ul>
                  </section>
                @endforeach
              </template>
            @endif
          </td>
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

  <dialog id="hopVanBan" class="hop-van-ban" aria-labelledby="hopVanBanTen">
    <div class="sheet-hd hop-van-ban-dau">
      <div><small>Văn bản pháp lý</small><h2 id="hopVanBanTen"></h2></div>
      <button type="button" class="hop-van-ban-x" data-dong aria-label="Đóng">×</button>
    </div>
    <div class="sheet-bd hop-van-ban-than"></div>
    <div class="sheet-ft">
      <a href="#" class="btn" data-mo-du-an>Mở trang dự án</a>
      <button type="button" class="btn primary" data-dong>Đóng</button>
    </div>
  </dialog>
</div>
@endsection

@push('cuoi_trang')
<script>
// Nút "Xem" văn bản pháp lý: mở hộp liệt kê văn bản của dự án (nội dung lấy từ <template> của dòng đó)
(function () {
  var hop = document.getElementById('hopVanBan');
  var than = hop.querySelector('.hop-van-ban-than'), ten = document.getElementById('hopVanBanTen'), moDuAn = hop.querySelector('[data-mo-du-an]');
  document.querySelectorAll('[data-xem-van-ban]').forEach(function (nut) {
    nut.addEventListener('click', function () {
      than.replaceChildren(document.getElementById('vanBan' + nut.dataset.xemVanBan).content.cloneNode(true));
      ten.textContent = nut.dataset.ten;
      moDuAn.href = nut.dataset.url;
      hop.showModal();
      than.scrollTop = 0;
    });
  });
  // Bấm ra nền tối bên ngoài hoặc nút Đóng thì đóng hộp
  hop.addEventListener('click', function (e) {
    if (e.target === hop || e.target.closest('[data-dong]')) hop.close();
  });
})();
</script>
@endpush
