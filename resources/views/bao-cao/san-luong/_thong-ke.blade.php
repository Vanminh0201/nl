{{-- Thống kê sản lượng cả năm: 4 biểu đồ, bảng từng dự án × 12 tháng và hộp chi tiết 1 dự án (?du_an=).
     Dùng chung cho trang Sở và trang chủ đầu tư. Cần số liệu của BaoCaoSanLuongController::duLieuThongKe(),
     $loc (tham số giữ lại khi mở / đóng chi tiết dự án) và $thangChon (tháng tô nổi trong bảng, có thể null). --}}
@php
  use App\Support\BaoCaoSanLuong as SL;
  $tr = fn (?float $kwh, int $le = 2) => $kwh === null ? '' : SL::so($kwh / 1e6, $le);
  // Tổng các giá trị có số liệu; không có giá trị nào thì null (ô trống)
  $cong = fn ($ds) => ($ds = collect($ds)->filter(fn ($v) => $v !== null))->isEmpty() ? null : $ds->sum();
  $phanTram = fn (?float $p) => $p === null ? '' : ($p >= 0 ? '+' : '−').SL::so(abs($p), 1).'%';
  $lopPt = fn (?float $p) => $p === null ? '' : ($p >= 0 ? 'sl-tang' : 'sl-giam');
  $namTruoc = $nam - 1;
  $coUocTinhNam = collect($uocTinhTheoThang)->filter()->isNotEmpty();
  $chinhThucThang = [];
  for ($t = 1; $t <= 12; $t++) {
    $chinhThucThang[$t] = $cong($bang->map(fn ($h) => $h['thang_chinh_thuc'][$t] ?? null));
  }
  // Dữ liệu biểu đồ (triệu kWh)
  $mang = fn (array $a) => array_values(array_map(fn ($v) => $v === null ? null : round($v / 1e6, 3), $a));
  $luyKe = function (array $a, int $den) { $t = 0; $r = []; foreach ($a as $th => $v) { $t += $v ?? 0; $r[] = $th <= $den ? round($t / 1e6, 3) : null; } return $r; };
  $chiTietBieuDo = $chiTiet ? collect($chiTiet['nam'])->map(function ($a, $n) use ($mang, $chiTiet) {
    $coUocTinh = (int) $n === 2026 && $chiTiet['thang_bo_sung'] !== [];
    if ($coUocTinh) $a = array_replace($a, $chiTiet['thang_bo_sung']);
    return ['nam' => $n, 'nhan' => $coUocTinh ? $n.' (gồm ước tính)' : null, 'so' => $mang($a), 'uoc_tinh' => $coUocTinh];
  })->values() : collect();
  if ($chiTiet && $chiTiet['thang_bo_sung'] && ! isset($chiTiet['nam'][2026])) {
    $thangNam = array_replace(array_fill(1, 12, null), $chiTiet['thang_bo_sung']);
    $chiTietBieuDo->push(['nam' => 2026, 'nhan' => '2026 (gồm ước tính)', 'so' => $mang($thangNam), 'uoc_tinh' => true]);
  }
  $bieuDo = [
    'nam' => $nam,
    'thang' => [
      'chinh_thuc' => $mang($chinhThucThang),
      'uoc_tinh' => $coUocTinhNam ? $mang($uocTinhTheoThang) : null,
      'truoc' => $mang($theoThang[$namTruoc]),
    ],
    'luy_ke' => ['nay' => $luyKe($theoThang[$nam], $thangCuoi ?: 0), 'truoc' => $luyKe($theoThang[$namTruoc], 12)],
    'loai_hinh' => collect($theoLoaiHinh)->map(fn ($a, $lh) => [
      'nhan' => $dsLoaiHinh[$lh]->ten ?? $lh, 'mau' => $dsLoaiHinh[$lh]->mau ?? '#5d6b61',
      'so' => array_map(fn ($t) => isset($a[$t]) ? round($a[$t] / 1e6, 3) : null, range(1, 12)),
    ])->values(),
    'top' => $bang->filter(fn ($h) => $h['tong'] > 0)->sortByDesc('tong')->take(12)->map(fn ($h) => [
      'nhan' => $h['ten'], 'so' => round($h['tong'] / 1e6, 3), 'mau' => $dsLoaiHinh[$h['loai_hinh']]->mau ?? '#5d6b61',
    ])->values(),
    'chi_tiet' => $chiTietBieuDo,
  ];
  $soDuAnCoSo = $bang->filter(fn ($h) => $h['so_thang'] > 0)->count();
  $stt = 0;
@endphp

@if ($kpi['luy_ke'] <= 0)
  <section class="sheet tk-so-sanh">
    <div class="sheet-hd"><div><h2>Thống kê sản lượng năm {{ $nam }}</h2></div></div>
    <p class="trong">Chưa có số liệu sản lượng năm {{ $nam }}{{ $loaiHinh ? ' của loại hình này' : '' }}.</p>
  </section>
@else
  <div class="tk-bieu-do-luoi sl-bieu-do-luoi">
    <section class="sheet tk-bieu-do">
      <div class="sheet-hd">
        <div>
          <span class="tk-bieu-do-so">01</span>
          <h2>Sản lượng theo tháng</h2>
          <p class="hint">Năm {{ $nam }} so với năm {{ $namTruoc }}{{ $coUocTinhNam ? '; phần gạch chéo là số ước tính' : '' }}.</p>
        </div>
        <span class="tk-don-vi">Triệu kWh</span>
      </div>
      <div class="sheet-bd"><div class="tk-khung"><canvas id="bdThang" aria-label="Biểu đồ sản lượng theo tháng"></canvas></div></div>
    </section>
    <section class="sheet tk-bieu-do">
      <div class="sheet-hd">
        <div>
          <span class="tk-bieu-do-so">02</span>
          <h2>Sản lượng lũy kế</h2>
          <p class="hint">Cộng dồn từ tháng 1{{ $thangCuoi ? ' đến tháng '.$thangCuoi.'/'.$nam : '' }}, so với cùng kỳ năm {{ $namTruoc }}.</p>
        </div>
        <span class="tk-don-vi">Triệu kWh</span>
      </div>
      <div class="sheet-bd"><div class="tk-khung"><canvas id="bdLuyKe" aria-label="Biểu đồ sản lượng lũy kế"></canvas></div></div>
    </section>
    <section class="sheet tk-bieu-do">
      <div class="sheet-hd">
        <div>
          <span class="tk-bieu-do-so">03</span>
          <h2>Cơ cấu theo loại hình nguồn điện</h2>
          <p class="hint">Sản lượng từng tháng năm {{ $nam }}, chia theo loại hình.</p>
        </div>
        <span class="tk-don-vi">Triệu kWh</span>
      </div>
      <div class="sheet-bd"><div class="tk-khung"><canvas id="bdLoaiHinh" aria-label="Biểu đồ sản lượng theo loại hình"></canvas></div></div>
    </section>
    <section class="sheet tk-bieu-do">
      <div class="sheet-hd">
        <div>
          <span class="tk-bieu-do-so">04</span>
          <h2>Dự án có sản lượng lớn nhất</h2>
          <p class="hint">{{ $bieuDo['top']->count() }} dự án đứng đầu theo tổng sản lượng năm {{ $nam }}.</p>
        </div>
        <span class="tk-don-vi">Triệu kWh</span>
      </div>
      <div class="sheet-bd"><div class="tk-khung"><canvas id="bdTop" aria-label="Biểu đồ dự án có sản lượng lớn nhất"></canvas></div></div>
    </section>
  </div>

  <section class="sheet tk-so-sanh sl-muc" id="bang-nam">
    <div class="sheet-hd">
      <div>
        <h2>Sản lượng từng dự án năm {{ $nam }}</h2>
        <p class="hint">
          Đơn vị: triệu kWh · {{ $soDuAnCoSo }}/{{ $bang->count() }} dự án có số liệu
          @if ($thangCuoi) · cùng kỳ: tháng 1–{{ $thangCuoi }}/{{ $namTruoc }} @endif
          · giờ phát quy đổi = sản lượng ÷ công suất
        </p>
      </div>
      @if ($nam === \App\Support\SanLuongDienUocTinh::NAM && $coUocTinhNam)
        <div class="tk-chu-giai sl-chu-giai" title="Các giá trị được tính vào tổng năm và KPI thống kê, không ghi vào báo cáo tháng chính thức">
          <span><span class="sl-uoc-tinh">~</span> Ước tính</span>
          <span><span class="sl-bao-cao-nguon">†</span> Theo tài liệu cung cấp</span>
        </div>
      @endif
    </div>
    <div class="tablewrap">
      <table class="sl-bang sl-so-bang sl-bang-nam">
        <thead>
          <tr>
            <th class="giua co-dinh co-dinh-1" rowspan="2">STT</th>
            <th class="co-dinh co-dinh-2" rowspan="2">Dự án</th>
            <th class="num" rowspan="2">Công suất<br>(MW)</th>
            <th class="giua sl-nhom-cot" colspan="12">Sản lượng các tháng năm {{ $nam }}</th>
            <th class="giua sl-nhom-cot" colspan="3">Cả năm</th>
            <th class="giua sl-nhom-cot" colspan="2">Hiệu suất</th>
          </tr>
          <tr>
            @for ($t = 1; $t <= 12; $t++)<th class="num {{ $t === $thangChon ? 'thang-chon' : '' }}">T{{ $t }}</th>@endfor
            <th class="num">{{ $nam }}</th><th class="num">Cùng kỳ {{ $namTruoc }}</th><th class="num">±%</th>
            <th class="num">Giờ phát</th><th class="num" title="So sản lượng năm theo hồ sơ dự án">% thiết kế</th>
          </tr>
        </thead>
        <tbody>
        @foreach ($nhomBang as $lh => $ds)
          @php $tN = $ds->sum('so_thang') ? $ds->sum('tong') : null; $cN = $ds->sum('cung_ky') ?: null; $pN = $cN > 0 && $tN > 0 ? ($tN / $cN - 1) * 100 : null; @endphp
          <tr class="sl-nhom" data-nhom-dau="{{ $lh }}">
            <td class="co-dinh co-dinh-1"></td>
            <td class="co-dinh co-dinh-2">{{ $dsLoaiHinh[$lh]->ten ?? $lh }} <span class="sl-nhom-dem">{{ $ds->count() }} dự án</span></td>
            <td class="num">{{ SL::so($ds->sum('mw'), 1) }}</td>
            @for ($t = 1; $t <= 12; $t++)<td class="num {{ $t === $thangChon ? 'thang-chon' : '' }}">{{ $tr($cong($ds->map(fn ($h) => $h['thang'][$t] ?? null))) }}</td>@endfor
            <td class="num">{{ $tr($tN) }}</td><td class="num">{{ $tr($cN) }}</td><td class="num {{ $lopPt($pN) }}">{{ $phanTram($pN) }}</td><td></td><td></td>
          </tr>
          @foreach ($ds as $h)
            <tr class="{{ $chiTiet && $chiTiet['du_an']->id === $h['id'] ? 'sl-chon' : '' }}" data-nhom="{{ $lh }}" data-tim="{{ $h['ten'] }} {{ $h['chu_dau_tu'] }}">
              <td class="giua co-dinh co-dinh-1">{{ ++$stt }}</td>
              <td class="co-dinh co-dinh-2">
                <a href="{{ route('bao-cao.san-luong.index', $loc + ['du_an' => $h['id']]) }}" class="sl-ten" data-chi-tiet>{{ $h['ten'] }}</a>
                @if ($h['thu'])<div class="sl-phu">Phát điện thử nghiệm</div>@endif
              </td>
              <td class="num">{{ SL::so($h['mw'], 1) }}</td>
              @for ($t = 1; $t <= 12; $t++)
                @php
                  $giaTriThang = $h['thang_chinh_thuc'][$t] ?? null;
                  $giaTriUocTinh = $h['uoc_tinh']['thang'][$t] ?? null;
                  $giaTriTheoTaiLieu = $h['uoc_tinh']
                    && $t === $h['uoc_tinh']['thang_gan_nhat']
                    && $h['uoc_tinh']['thang_gan_nhat_theo_tai_lieu']
                    ? $h['uoc_tinh']['san_luong_thang_gan_nhat']
                    : null;
                @endphp
                <td class="num {{ $t === $thangChon ? 'thang-chon' : '' }}">
                  @if ($giaTriThang !== null){{ $tr($giaTriThang) }}
                  @elseif ($giaTriUocTinh !== null)<span class="sl-uoc-tinh" title="Số ước tính, không phải số liệu báo cáo chính thức">~{{ $tr($giaTriUocTinh) }}</span>
                  @elseif ($giaTriTheoTaiLieu !== null)<span class="sl-bao-cao-nguon" title="Số liệu theo tài liệu cung cấp, chưa ghi nhận trong báo cáo tháng chính thức">{{ $tr($giaTriTheoTaiLieu) }}†</span>@endif
                </td>
              @endfor
              <td class="num"><b>{{ $tr($h['so_thang'] ? $h['tong'] : null) }}</b></td>
              <td class="num sl-cu">{{ $tr($h['cung_ky'] ?: null) }}</td>
              <td class="num {{ $lopPt($h['so_cung_ky']) }}">{{ $phanTram($h['so_cung_ky']) }}</td>
              <td class="num">{{ $h['gio'] ? SL::so($h['gio']) : '' }}</td>
              <td class="num">{{ $h['thiet_ke'] ? SL::so($h['thiet_ke'], 1).'%' : '' }}</td>
            </tr>
          @endforeach
        @endforeach
        </tbody>
        <tfoot>
          <tr class="sl-tong">
            <td class="co-dinh co-dinh-1"></td><td class="co-dinh co-dinh-2">Tổng cộng</td><td class="num">{{ SL::so($bang->sum('mw'), 1) }}</td>
            @for ($t = 1; $t <= 12; $t++)<td class="num {{ $t === $thangChon ? 'thang-chon' : '' }}">{{ $tr($theoThang[$nam][$t]) }}</td>@endfor
            <td class="num">{{ $tr($kpi['luy_ke']) }}</td><td class="num">{{ $tr($kpi['cung_ky'] ?: null) }}</td>
            <td class="num {{ $lopPt($kpi['so_cung_ky']) }}">{{ $phanTram($kpi['so_cung_ky']) }}</td><td></td><td></td>
          </tr>
        </tfoot>
      </table>
    </div>
  </section>
@endif

@if ($chiTiet)
  @php
    $ctNam = $chiTiet['nam'];
    if ($chiTiet['uoc_tinh'] && ! array_key_exists(2026, $ctNam)) $ctNam[2026] = array_fill(1, 12, null);
    krsort($ctNam);
    $d = $chiTiet['du_an'];
    $tk = $d->taiKhoanChuDauTu;
  @endphp
  <dialog id="slChiTiet" class="sl-chi-tiet" aria-labelledby="slChiTietTen" open><div class="sl-chi-tiet-noi">
    <div class="sl-chi-tiet-dau">
      <div>
        <span class="sl-chi-tiet-nhan">Chi tiết sản lượng dự án</span>
        <h2 id="slChiTietTen">{{ $d->ten }}</h2>
        <div class="sl-chi-tiet-phu">
          <span>{{ $dsLoaiHinh[$d->loai_hinh]->ten ?? $d->loai_hinh }}</span>
          <span>{{ SL::so($d->cong_suat_mw, 2) }} MW</span>
          @if ($tk)<span>{{ $tk->don_vi ?: $tk->name }}</span>@endif
          @if (SL::phatDienThu($d))<span>Phát điện thử nghiệm từ {{ mb_strtolower(\App\Support\KyThang::tuMa($d->phat_dien_tu)?->ten() ?? '') }}</span>@else<span>{{ $d->trang_thai }}</span>@endif
        </div>
      </div>
      <a href="{{ route('bao-cao.san-luong.index', $loc) }}" class="btn" data-dong-chi-tiet>Đóng</a>
    </div>
    @if ($chiTiet['uoc_tinh'])
      @php
        $uocTinh = $chiTiet['uoc_tinh'];
        $binhQuanUocTinh = $uocTinh['thang'] ? array_sum($uocTinh['thang']) / count($uocTinh['thang']) : null;
      @endphp
      <div class="sl-uoc-tinh-thong-tin">
        <div><span>Hòa đồng bộ</span><b>{{ $uocTinh['ngay_hoa_dong_bo'] }}</b></div>
        <div><span>Lũy kế theo tài liệu đến 30/09/2026</span><b>{{ $tr($uocTinh['luy_ke']) }} triệu kWh</b></div>
        <div>
          <span>Sản lượng tháng {{ $uocTinh['thang_gan_nhat'] }}/2026</span>
          <b>{{ $tr($uocTinh['san_luong_thang_gan_nhat']) }} triệu kWh</b>
          @if ($uocTinh['thang_gan_nhat_theo_tai_lieu'])<small>Theo tài liệu cung cấp</small>@endif
        </div>
        @if ($binhQuanUocTinh !== null)
          <div><span>Bình quân các tháng ước tính</span><b>{{ $tr($binhQuanUocTinh) }} triệu kWh/tháng</b></div>
        @endif
        <p>
          Các ô có dấu <b>~</b> là số ước tính cho tháng chưa có số liệu, phân bổ đều phần lũy kế còn lại.
          Các giá trị <b>~</b> ước tính và <b>†</b> theo tài liệu được tính vào tổng năm và KPI thống kê,
          nhưng không ghi vào dữ liệu báo cáo tháng chính thức.
          @if ($uocTinh['canh_bao'])<strong>{{ $uocTinh['canh_bao'] }}</strong>@endif
        </p>
      </div>
    @endif
    @if ($ctNam)
      <div class="sl-chi-tiet-bieu-do"><div class="tk-khung"><canvas id="bdChiTiet" aria-label="Biểu đồ sản lượng các năm của dự án"></canvas></div></div>
      <div class="tablewrap sl-chi-tiet-bang">
        <table class="sl-bang sl-so-bang">
          <thead><tr><th>Năm</th>@for ($t = 1; $t <= 12; $t++)<th class="num">T{{ $t }}</th>@endfor<th class="num">Cả năm</th><th class="num">So năm trước</th></tr></thead>
          <tbody>
          @foreach ($ctNam as $n => $a)
            @php
              $thangTongHop = (int) $n === 2026 ? array_replace($a, $chiTiet['thang_bo_sung']) : $a;
              $tongN = $cong($thangTongHop);
              $soThang = count(array_filter($thangTongHop, fn ($v) => $v !== null));
              $soThangUocTinh = (int) $n === 2026
                ? count(array_intersect_key($chiTiet['uoc_tinh']['thang'] ?? [], $chiTiet['thang_bo_sung']))
                : 0;
              $soThangTheoTaiLieu = (int) $n === 2026 && ($chiTiet['uoc_tinh']['thang_gan_nhat_theo_tai_lieu'] ?? false) ? 1 : 0;
              $truocN = $ctNam[$n - 1] ?? null;
              $ckN = $truocN ? array_sum(array_intersect_key(array_filter($truocN), array_filter($thangTongHop, fn ($v) => $v !== null))) : 0;
              $ptN = $ckN > 0 ? ($tongN / $ckN - 1) * 100 : null;
            @endphp
            <tr>
              <td><b>{{ $n }}</b>@if ($soThang < 12)<div class="sl-phu">{{ $soThang }} tháng{{ $soThangUocTinh ? ' · '.$soThangUocTinh.' ước tính' : '' }}{{ $soThangTheoTaiLieu ? ' · '.$soThangTheoTaiLieu.' theo tài liệu' : '' }}</div>@endif</td>
              @for ($t = 1; $t <= 12; $t++)
                @php
                  $giaTriThang = $chiTiet['nam'][$n][$t] ?? null;
                  $giaTriUocTinh = (int) $n === 2026 ? ($chiTiet['uoc_tinh']['thang'][$t] ?? null) : null;
                  $giaTriTheoTaiLieu = (int) $n === 2026
                    && $t === ($chiTiet['uoc_tinh']['thang_gan_nhat'] ?? null)
                    && ($chiTiet['uoc_tinh']['thang_gan_nhat_theo_tai_lieu'] ?? false)
                    ? $chiTiet['uoc_tinh']['san_luong_thang_gan_nhat']
                    : null;
                @endphp
                <td class="num">
                  @if ($giaTriThang !== null){{ $tr($giaTriThang) }}
                  @elseif ($giaTriUocTinh !== null)<span class="sl-uoc-tinh" title="Số ước tính, không phải số liệu báo cáo chính thức">~{{ $tr($giaTriUocTinh) }}</span>
                  @elseif ($giaTriTheoTaiLieu !== null)<span class="sl-bao-cao-nguon" title="Số liệu theo tài liệu cung cấp, chưa ghi nhận trong báo cáo tháng chính thức">{{ $tr($giaTriTheoTaiLieu) }}†</span>@endif
                </td>
              @endfor
              <td class="num"><b>{{ $tr($tongN) }}</b></td>
              <td class="num {{ $lopPt($ptN) }}" title="So cùng các tháng năm trước">{{ $phanTram($ptN) }}</td>
            </tr>
          @endforeach
          </tbody>
        </table>
      </div>
    @else
      <p class="trong">Dự án chưa có số liệu sản lượng.</p>
    @endif
  </div></dialog>
@endif

@push('cuoi_trang')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.5.1/chart.umd.min.js"></script>
<script>
(function () {
  // Hộp chi tiết dự án: mở dạng hộp thoại; đóng thì bỏ ?du_an= khỏi địa chỉ, không tải lại trang
  var hop = document.getElementById('slChiTiet');
  if (hop && hop.showModal) {
    hop.close();
    hop.showModal();
    var dong = function () { if (hop.open) hop.close(); };
    hop.querySelector('[data-dong-chi-tiet]').addEventListener('click', function (e) { e.preventDefault(); dong(); });
    hop.addEventListener('click', function (e) { if (e.target === hop) dong(); });
    hop.addEventListener('close', function () {
      // Sự kiện close của lệnh close() ngay trên kia cũng tới đây (chạy sau): hộp vẫn đang mở thì bỏ qua
      if (hop.open) return;
      try { history.replaceState(null, '', hop.querySelector('[data-dong-chi-tiet]').href); } catch (e) {}
    });
  }
  // Mở chi tiết thì trang tải lại: nhớ vị trí cuộn để quay về đúng chỗ đang xem
  document.addEventListener('click', function (e) {
    if (e.target.closest('a[data-chi-tiet]')) { try { sessionStorage.setItem('slCuon', String(window.scrollY)); } catch (x) {} }
  });
  try {
    var cuon = sessionStorage.getItem('slCuon');
    if (cuon !== null) { sessionStorage.removeItem('slCuon'); window.scrollTo(0, +cuon); }
  } catch (x) {}

  if (typeof Chart === 'undefined') return;
  Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
  Chart.defaults.color = '#4a5866';
  var bd = @json($bieuDo);
  var thang = ['T1', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'T8', 'T9', 'T10', 'T11', 'T12'];
  var so = function (v) { return v === null || v === undefined ? '—' : v.toLocaleString('vi-VN', { maximumFractionDigits: 2 }); };
  var truc = { beginAtZero: true, grid: { color: '#e2e6e1' }, ticks: { callback: function (v) { return v.toLocaleString('vi-VN'); } } };
  var goiY = { callbacks: { label: function (c) { return (c.dataset.label ? c.dataset.label + ': ' : '') + so(c.parsed.y !== undefined && c.chart.options.indexAxis !== 'y' ? c.parsed.y : c.parsed.x) + ' triệu kWh'; } } };
  var chuGiai = { position: 'bottom', labels: { boxWidth: 12, boxHeight: 12, padding: 14 } };
  var chuGiaiDuong = { position: 'bottom', labels: { usePointStyle: true, pointStyle: 'line', boxWidth: 28, padding: 14 } };
  function ve(id, cauHinh) { var el = document.getElementById(id); if (el) new Chart(el, cauHinh); }
  var chung = { responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false } };
  // Nền gạch chéo cho phần số ước tính
  function gachCheo(mau) {
    var c = document.createElement('canvas'); c.width = c.height = 8;
    var g = c.getContext('2d'); g.fillStyle = '#fbe9cf'; g.fillRect(0, 0, 8, 8);
    g.strokeStyle = mau; g.lineWidth = 2; g.beginPath(); g.moveTo(0, 8); g.lineTo(8, 0); g.moveTo(-2, 2); g.lineTo(2, -2); g.moveTo(6, 10); g.lineTo(10, 6); g.stroke();
    return g.createPattern(c, 'repeat');
  }

  var dsThang = [{ label: String(bd.nam), data: bd.thang.chinh_thuc, backgroundColor: '#1f4b6e', borderRadius: 3, maxBarThickness: 26, stack: 'nay' }];
  if (bd.thang.uoc_tinh) dsThang.push({ label: bd.nam + ' · ước tính', data: bd.thang.uoc_tinh, backgroundColor: gachCheo('#b4531c'), borderColor: '#b4531c', borderWidth: 1, borderRadius: 3, maxBarThickness: 26, stack: 'nay' });
  dsThang.push({ label: String(bd.nam - 1), data: bd.thang.truoc, backgroundColor: '#b7c6d2', borderRadius: 3, maxBarThickness: 26, stack: 'truoc' });
  ve('bdThang', { type: 'bar', data: { labels: thang, datasets: dsThang },
    options: Object.assign({}, chung, { plugins: { tooltip: goiY, legend: chuGiai }, scales: { y: Object.assign({ stacked: true }, truc), x: { stacked: true, grid: { display: false } } } }) });

  ve('bdLuyKe', { type: 'line', data: { labels: thang, datasets: [
    { label: bd.thang.uoc_tinh ? bd.nam + ' (gồm ước tính)' : String(bd.nam), data: bd.luy_ke.nay, borderColor: '#1f4b6e', backgroundColor: 'rgba(31,75,110,.12)', fill: true, tension: .25, pointRadius: 3 },
    { label: String(bd.nam - 1), data: bd.luy_ke.truoc, borderColor: '#96a8b6', borderDash: [5, 4], tension: .25, pointRadius: 2 }
  ] }, options: Object.assign({}, chung, { plugins: { tooltip: goiY, legend: chuGiaiDuong }, scales: { y: truc, x: { grid: { display: false } } } }) });

  ve('bdLoaiHinh', { type: 'bar', data: { labels: thang, datasets: bd.loai_hinh.map(function (l) {
    return { label: l.nhan, data: l.so, backgroundColor: l.mau, borderRadius: 2, maxBarThickness: 30 };
  }) }, options: Object.assign({}, chung, { plugins: { tooltip: goiY, legend: chuGiai }, scales: { x: { stacked: true, grid: { display: false } }, y: Object.assign({ stacked: true }, truc) } }) });

  ve('bdTop', { type: 'bar', data: { labels: bd.top.map(function (r) { return r.nhan; }), datasets: [
    { data: bd.top.map(function (r) { return r.so; }), backgroundColor: bd.top.map(function (r) { return r.mau; }), borderRadius: 3, maxBarThickness: 18 }
  ] }, options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: goiY }, scales: { x: truc, y: { grid: { display: false }, ticks: { autoSkip: false } } } } });

  var bangMau = ['#1f4b6e', '#c2185b', '#2f6b4f', '#b4531c', '#7a60fb', '#96701a', '#1d6f86', '#5d6b61'];
  ve('bdChiTiet', { type: 'line', data: { labels: thang, datasets: (bd.chi_tiet || []).map(function (n, i) {
    var mau = n.uoc_tinh ? '#b4531c' : bangMau[i % bangMau.length];
    return { label: n.nhan || String(n.nam), data: n.so, borderColor: mau, backgroundColor: mau, borderDash: n.uoc_tinh ? [6, 4] : [], tension: .25, pointRadius: 3, borderWidth: i === 0 ? 3 : 2 };
  }) }, options: Object.assign({}, chung, { plugins: { tooltip: goiY, legend: chuGiaiDuong }, scales: { y: truc, x: { grid: { display: false } } } }) });
})();
</script>
@endpush
