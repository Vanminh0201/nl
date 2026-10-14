@extends('layouts.app')
@section('tieu_de', 'Sản lượng điện')

@php
  use App\Support\BaoCaoSanLuong as SL;
  use App\Http\Controllers\BaoCaoSanLuongController as SLC;
  $laKyCanBao = $ky->bang($kyCanBao);
  $quaHan = SL::quaHan($ky);
  $hanGui = SL::hanNop($ky);
  $conNgay = (int) \Carbon\CarbonImmutable::now(\App\Support\KyBaoCao::MUI_GIO)->startOfDay()->diffInDays($hanGui->startOfDay(), false);
  // Tham số giữ lại trên mọi liên kết của trang (tháng đang xem, loại hình đang lọc)
  $loc = array_filter(['ky' => $laKyCanBao ? null : $ky->ma(), 'loai_hinh' => $loaiHinh]);
  $nhomKy = [];
  foreach (array_reverse($dsKyChon) as $k) {
    $nhomKy['Năm '.$k->nam][] = [
      'url' => route('bao-cao.san-luong.index', array_filter(['ky' => $k->ma(), 'loai_hinh' => $loaiHinh])),
      'ten' => 'Tháng '.$k->thang,
      'phu' => 'Hạn gửi '.SL::hanNop($k)->format('d/m/Y'),
      'chon' => $k->bang($ky),
      'hien_tai' => $k->bang($kyCanBao),
      'tinh_hinh' => $tinhHinhKy[$k->ma()] ?? null,
    ];
  }
  $tr = fn (?float $kwh, int $le = 2) => $kwh === null ? '' : SL::so($kwh / 1e6, $le);
  $phanTram = fn (?float $p) => $p === null ? '' : ($p >= 0 ? '▲ +' : '▼ −').SL::so(abs($p), 1).'%';
  $lopBien = fn (?float $p) => $p === null ? '' : ($p >= 0 ? 'tang' : 'giam');
  $bienDong = fn (?float $nay, ?float $truoc) => $nay !== null && $truoc > 0 ? ($nay / $truoc - 1) * 100 : null;
  $gio = fn ($t) => $t ? $t->copy()->setTimezone(\App\Support\KyBaoCao::MUI_GIO)->format('d/m/Y H:i') : '';
  $tenLoaiHinh = $loaiHinh ? ($dsLoaiHinh[$loaiHinh]->ten ?? $loaiHinh) : null;
  $choTiep = $demTinhTrang['cho'] ?? 0;
  $chuaGui = ($demTinhTrang['chua_gui'] ?? 0) + ($demTinhTrang['dang_soan'] ?? 0);
  $tongDong = array_sum($demTinhTrang);
  $soCot = $coTheXuLy ? 9 : 8;
  $stt = 0;
@endphp

@section('noi_dung')
<div class="pad bc-trang sl-trang">
  @include('bao-cao._tab', ['dangChon' => 'bao-cao.san-luong.index'])
  @if ($errors->any())
    <div class="loi" style="margin:0">{{ $errors->first() }}</div>
  @endif

  <div class="bc-thanh-ky bc-ky sl-thanh-cong-cu">
    @include('bao-cao._chon-ky', [
      'ten' => 'Sản lượng '.mb_strtolower($ky->ten()),
      'phu' => 'Hạn gửi '.$hanGui->format('d/m/Y'),
      'truoc' => ['url' => route('bao-cao.san-luong.index', array_filter(['ky' => $ky->truoc()->ma(), 'loai_hinh' => $loaiHinh])), 'ten' => 'Tháng trước'],
      'sau' => $laKyCanBao ? null : ['url' => route('bao-cao.san-luong.index', array_filter(['ky' => $ky->sau()->ma(), 'loai_hinh' => $loaiHinh])), 'ten' => 'Tháng sau'],
      'nhom' => $nhomKy,
    ])
    @unless ($laKyCanBao)
      <a href="{{ route('bao-cao.san-luong.index', array_filter(['loai_hinh' => $loaiHinh])) }}" class="btn bc-nut">Về tháng cần báo cáo</a>
    @endunless
    <form method="get" class="sl-loc-loai-hinh">
      @unless ($laKyCanBao)<input type="hidden" name="ky" value="{{ $ky->ma() }}">@endunless
      <select name="loai_hinh" onchange="this.form.submit()" aria-label="Loại hình nguồn điện">
        <option value="">Tất cả nguồn điện</option>
        @foreach ($loaiHinhCo as $lh)<option value="{{ $lh }}" @selected($lh === $loaiHinh)>{{ $dsLoaiHinh[$lh]->ten ?? $lh }}</option>@endforeach
      </select>
      <noscript><button class="btn bc-nut">Lọc</button></noscript>
    </form>
    <span class="bc-ky-gian"></span>
    <input type="search" class="sl-tim" placeholder="Tìm dự án, chủ đầu tư…" aria-label="Tìm dự án hoặc chủ đầu tư" data-sl-tim>
    <a href="{{ route('bao-cao.san-luong.excel', array_filter(['nam' => $nam, 'loai_hinh' => $loaiHinh])) }}" class="btn primary bc-nut">@include('bao-cao._icon', ['ten' => 'tai'])Tải Excel năm {{ $nam }}</a>
  </div>

  <section class="tk-tong-quan sl-tong-quan" aria-label="Tổng quan sản lượng điện">
    <div class="tk-tong-quan-dau">
      <span class="tk-tong-quan-nhan">SẢN LƯỢNG ĐIỆN · {{ $tenLoaiHinh ? mb_strtoupper($tenLoaiHinh) : 'TẤT CẢ NGUỒN ĐIỆN' }}</span>
      <h1>{{ $ky->ten() }}</h1>
      <p>
        Hạn gửi báo cáo {{ $hanGui->format('d/m/Y') }}
        @if ($quaHan)<b class="sl-han qua">· đã quá hạn</b>@elseif ($conNgay === 0)<b class="sl-han">· hạn chót hôm nay</b>@else<b class="sl-han">· còn {{ $conNgay }} ngày</b>@endif
      </p>
      <p>Số liệu tổng hợp từ báo cáo sản lượng chủ đầu tư đã gửi Sở.</p>
    </div>
    <div class="tk-tong-quan-chi-so">
      <article class="tk-chi-so">
        <span class="tk-chi-so-nhan">Sản lượng tháng {{ $ky->thang }}/{{ $ky->nam }}</span>
        <strong>{{ $kpiThang['tong'] !== null ? $tr($kpiThang['tong']) : '—' }}@if ($kpiThang['tong'] !== null)<span> triệu kWh</span>@endif</strong>
        <small>{{ $kpiThang['so_du_an'] }} dự án có số liệu{{ $kpiThang['uoc_tinh'] > 0 ? ' · gồm '.$tr($kpiThang['uoc_tinh']).' ước tính' : '' }}</small>
        @if ($kpiThang['so_truoc'] !== null || $kpiThang['so_cung_ky'] !== null)
          <small class="sl-chi-so-ss" title="Chỉ so sánh các dự án có số liệu ở cả hai tháng">
            @if ($kpiThang['so_truoc'] !== null)<b class="{{ $lopBien($kpiThang['so_truoc']) }}">{{ $phanTram($kpiThang['so_truoc']) }}</b> so T{{ $kpiThang['ky_truoc']->thang }}@endif
            @if ($kpiThang['so_truoc'] !== null && $kpiThang['so_cung_ky'] !== null) · @endif
            @if ($kpiThang['so_cung_ky'] !== null)<b class="{{ $lopBien($kpiThang['so_cung_ky']) }}">{{ $phanTram($kpiThang['so_cung_ky']) }}</b> so cùng kỳ@endif
          </small>
        @endif
      </article>
      <article class="tk-chi-so">
        <span class="tk-chi-so-nhan">Lũy kế năm {{ $nam }}{{ $thangCuoi ? ' (T1–T'.$thangCuoi.')' : '' }}</span>
        <strong>{{ $kpi['luy_ke'] > 0 ? $tr($kpi['luy_ke']) : '—' }}@if ($kpi['luy_ke'] > 0)<span> triệu kWh</span>@endif</strong>
        <small>{{ collect($uocTinhTheoThang)->filter()->isNotEmpty() ? 'Gồm số ước tính · ' : '' }}Cùng kỳ {{ $nam - 1 }}: {{ $kpi['cung_ky'] > 0 ? $tr($kpi['cung_ky']) : 'chưa có số liệu' }}</small>
        @if ($kpi['so_cung_ky'] !== null)
          <small class="sl-chi-so-ss"><b class="{{ $lopBien($kpi['so_cung_ky']) }}">{{ $phanTram($kpi['so_cung_ky']) }}</b> so cùng kỳ</small>
        @endif
      </article>
      <article class="tk-chi-so">
        <span class="tk-chi-so-nhan">Đã gửi báo cáo tháng {{ $ky->thang }}</span>
        <strong>{{ $dem['da_gui'] }}<span>/{{ $dem['phai'] }} dự án</span></strong>
        <span class="sl-chi-so-thanh" role="img" aria-label="Đã gửi {{ $dem['da_gui'] }}/{{ $dem['phai'] }} dự án"><i style="width:{{ $dem['phai'] ? round($dem['da_gui'] / $dem['phai'] * 100) : 0 }}%"></i></span>
        <small>{{ $choTiep }} chờ tiếp nhận · {{ $chuaGui }} chưa gửi{{ $quaHan && $chuaGui ? ' (quá hạn)' : '' }}{{ ($demTinhTrang['khong_yeu_cau'] ?? 0) ? ' · '.$demTinhTrang['khong_yeu_cau'].' không yêu cầu' : '' }}</small>
      </article>
      <article class="tk-chi-so">
        <span class="tk-chi-so-nhan">Công suất đang phát điện</span>
        <strong>{{ SL::so($kpi['cong_suat'], 1) }}<span> MW</span></strong>
        <small>{{ $kpi['so_du_an'] }} dự án{{ $dem['chua_gan'] ? ' · '.$dem['chua_gan'].' chưa gán tài khoản' : '' }}</small>
      </article>
    </div>
  </section>

  <section class="sheet tk-so-sanh sl-muc" id="tinh-hinh-gui">
    <div class="sheet-hd">
      <div>
        <h2>Tình hình gửi báo cáo sản lượng {{ mb_strtolower($ky->ten()) }}</h2>
        <p class="hint">Sản lượng tính bằng kWh · đối chiếu với {{ mb_strtolower($ky->truoc()->ten()) }} và tháng {{ $ky->thang }}/{{ $ky->nam - 1 }} của cùng dự án</p>
      </div>
      @if ($coTheXuLy && $choTiepNhan->isNotEmpty())
        <form method="post" action="{{ route('bao-cao.san-luong.xu-ly') }}">
          @csrf
          @foreach ($choTiepNhan as $id)<input type="hidden" name="ids[]" value="{{ $id }}">@endforeach
          <button class="btn primary bc-nut" name="hanh_dong" value="tiep_nhan">@include('bao-cao._icon', ['ten' => 'xong'])Tiếp nhận tất cả ({{ $choTiepNhan->count() }})</button>
        </form>
      @endif
    </div>
    @if ($tongDong)
      <div class="sl-loc-tt" role="group" aria-label="Lọc theo tình trạng gửi">
        <button type="button" class="sl-loc-nut chon" data-loc-tt="" aria-pressed="true">Tất cả <b>{{ $tongDong }}</b></button>
        @foreach (SLC::TINH_TRANG_GUI as $khoa => [$nhan, $lop])
          @if ($demTinhTrang[$khoa] ?? 0)
            <button type="button" class="sl-loc-nut {{ $lop }}" data-loc-tt="{{ $khoa }}" aria-pressed="false">{{ $khoa === 'chua_gui' && $quaHan ? 'Quá hạn, chưa gửi' : $nhan }} <b>{{ $demTinhTrang[$khoa] }}</b></button>
          @endif
        @endforeach
      </div>
    @endif
    <div class="tablewrap">
      <table class="sl-bang sl-bang-gui">
        <thead>
          <tr>
            <th class="giua">STT</th>
            <th>Dự án · chủ đầu tư</th>
            <th class="num">Công suất<br>(MW)</th>
            <th class="num">Sản lượng<br>tháng {{ $ky->thang }}/{{ $ky->nam }}</th>
            <th class="num">{{ $ky->truoc()->ten() }}</th>
            <th class="num">Tháng {{ $ky->thang }}/{{ $ky->nam - 1 }}</th>
            <th>Ghi chú</th>
            <th>Trạng thái</th>
            @if ($coTheXuLy)<th class="giua">Xử lý</th>@endif
          </tr>
        </thead>
        <tbody>
        @forelse ($nhom as $lh => $ds)
          @php
            $tongNhom = $ds->filter(fn ($h) => $h['dong']?->daCo())->sum(fn ($h) => $h['dong']->san_luong);
            $phaiNhom = $ds->filter(fn ($h) => $h['yeu_cau']);
          @endphp
          <tr class="sl-nhom" data-nhom-dau="{{ $lh }}">
            <td colspan="{{ $soCot }}">
              <span>{{ $dsLoaiHinh[$lh]->ten ?? $lh }} <span class="sl-nhom-dem">{{ $ds->count() }} dự án</span></span>
              <span class="sl-nhom-phai">
                @if ($phaiNhom->isNotEmpty())Đã gửi {{ $phaiNhom->filter(fn ($h) => $h['dong']?->daCo())->count() }}/{{ $phaiNhom->count() }}@else Không yêu cầu báo cáo @endif
                @if ($tongNhom) · {{ $tr($tongNhom) }} triệu kWh @endif
              </span>
            </td>
          </tr>
          @foreach ($ds as $h)
            @php
              $d = $h['du_an']; $dong = $h['dong']; $tk = $d->taiKhoanChuDauTu; $co = $dong?->daCo();
              $tt = $h['tinh_trang'];
              $nay = $co ? $dong->san_luong : null;
              $truoc = $h['truoc']?->daCo() ? $h['truoc']->san_luong : null;
              $cungKy = $h['cung_ky']?->daCo() ? $h['cung_ky']->san_luong : null;
              [$nhanTt, $lopTt] = SLC::TINH_TRANG_GUI[$tt];
              if ($tt === 'chua_gui' && $quaHan) { [$nhanTt, $lopTt] = ['Quá hạn, chưa gửi', 'do']; }
            @endphp
            <tr class="sl-dong-{{ $tt }}" data-tt="{{ $tt }}" data-nhom="{{ $lh }}" data-tim="{{ $d->ten }} {{ $tk?->don_vi }} {{ $tk?->name }}">
              <td class="giua">{{ ++$stt }}</td>
              <td class="sl-o-du-an">
                <a href="{{ route('bao-cao.san-luong.index', $loc + ['du_an' => $d->id]) }}" class="sl-ten" data-chi-tiet>{{ $d->ten }}</a>
                <div class="sl-phu">
                  @if ($tk){{ $tk->don_vi ?: $tk->name }}@else<i>Chưa gán tài khoản chủ đầu tư</i>@endif
                  @if (SL::phatDienThu($d)) · phát điện thử nghiệm từ {{ mb_strtolower(\App\Support\KyThang::tuMa($d->phat_dien_tu)?->ten() ?? '') }}@elseif ($d->trang_thai !== SL::TRANG_THAI_VAN_HANH) · {{ $d->trang_thai }}@endif
                </div>
              </td>
              <td class="num">{{ SL::so($d->cong_suat_mw, 2) }}</td>
              <td class="num">@if ($co)<b>{{ SL::so($dong->san_luong) }}</b>@elseif ($dong?->trang_thai === 'tra_lai')<s class="sl-cu" title="Số liệu bị trả lại">{{ SL::so($dong->san_luong) }}</s>@else<span class="sl-trong">—</span>@endif</td>
              <td class="num sl-o-ss">
                <span class="sl-cu">{{ SL::so($truoc) }}</span>
                @if (($p = $bienDong($nay, $truoc)) !== null)<small class="sl-bien {{ $lopBien($p) }}">{{ $phanTram($p) }}</small>@endif
              </td>
              <td class="num sl-o-ss">
                <span class="sl-cu">{{ SL::so($cungKy) }}</span>
                @if (($p = $bienDong($nay, $cungKy)) !== null)<small class="sl-bien {{ $lopBien($p) }}">{{ $phanTram($p) }}</small>@endif
              </td>
              <td class="sl-o-ghi-chu">@if ($dong?->trang_thai === 'tra_lai')<span class="sl-tra-lai">Sở trả lại: {{ $dong->y_kien_xu_ly }}</span>@elseif ($co){{ $dong->ghi_chu }}@endif</td>
              <td class="sl-o-tt">
                <span class="chip {{ $lopTt }}">{{ $nhanTt }}</span>
                @if ($dong?->gui_luc && in_array($tt, ['cho', 'co'], true))<small>Gửi {{ $gio($dong->gui_luc) }}</small>@endif
                @if ($tt === 'co' && $dong->xu_ly_luc)<small>Tiếp nhận {{ $gio($dong->xu_ly_luc) }}{{ $dong->nguoiXuLy ? ' · '.$dong->nguoiXuLy->name : '' }}</small>@endif
                @if ($tt === 'tra_lai' && $dong->xu_ly_luc)<small>Trả lại {{ $gio($dong->xu_ly_luc) }}{{ $dong->nguoiXuLy ? ' · '.$dong->nguoiXuLy->name : '' }}</small>@endif
              </td>
              @if ($coTheXuLy)
                <td class="giua">
                  <div class="sl-tt">
                    @if ($tt === 'cho')
                      <form method="post" action="{{ route('bao-cao.san-luong.xu-ly') }}">
                        @csrf<input type="hidden" name="ids[]" value="{{ $dong->id }}">
                        <button class="btn sm primary" name="hanh_dong" value="tiep_nhan">Tiếp nhận</button>
                      </form>
                    @endif
                    @if (in_array($tt, ['cho', 'co'], true))
                      <button type="button" class="btn sm danger" data-tra-lai="{{ $dong->id }}" data-ten="{{ $d->ten }}">Trả lại</button>
                    @endif
                  </div>
                </td>
              @endif
            </tr>
          @endforeach
        @empty
          <tr><td colspan="{{ $soCot }}" class="trong">Chưa có dự án nguồn điện nào ở trạng thái "{{ SL::TRANG_THAI_VAN_HANH }}"{{ $tenLoaiHinh ? ' thuộc loại hình '.$tenLoaiHinh : '' }}.</td></tr>
        @endforelse
          <tr class="sl-khong-khop" hidden><td colspan="{{ $soCot }}" class="trong">Không có dự án nào khớp điều kiện lọc.</td></tr>
        </tbody>
      </table>
    </div>
  </section>

  @include('bao-cao.san-luong._thong-ke', ['loc' => $loc, 'thangChon' => $ky->thang])

  @if ($coTheXuLy)
    <dialog id="slTraLai" class="sl-hop">
      <form method="post" action="{{ route('bao-cao.san-luong.xu-ly') }}">
        @csrf
        <input type="hidden" name="ids[]" id="slTraLaiId">
        <input type="hidden" name="hanh_dong" value="tra_lai">
        <h3 id="slTraLaiTen">Trả lại sản lượng</h3>
        <textarea name="y_kien" rows="4" maxlength="2000" required placeholder="Lý do trả lại"></textarea>
        <div class="sl-hop-nut">
          <button type="button" class="btn" data-dong-hop>Huỷ</button>
          <button class="btn danger">Trả lại</button>
        </div>
      </form>
    </dialog>
  @endif
</div>
@endsection

@push('cuoi_trang')
<script>
(() => {
  // Lọc bảng tình hình gửi theo tình trạng, và tìm dự án / chủ đầu tư trên cả hai bảng của trang
  const boDau = s => (s || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/đ/g, 'd');
  const oTim = document.querySelector('[data-sl-tim]');
  let locTt = '';
  const apDung = () => {
    const tu = boDau(oTim ? oTim.value.trim() : '');
    document.querySelectorAll('table.sl-bang-gui, table.sl-bang-nam').forEach(bang => {
      const theoTt = bang.classList.contains('sl-bang-gui');
      let conHien = 0;
      bang.querySelectorAll('tbody tr[data-tim]').forEach(tr => {
        const hien = (!tu || boDau(tr.dataset.tim).includes(tu)) && (!theoTt || !locTt || tr.dataset.tt === locTt);
        tr.hidden = !hien;
        if (hien) conHien++;
      });
      bang.querySelectorAll('tbody tr[data-nhom-dau]').forEach(dau => {
        dau.hidden = !bang.querySelector('tbody tr[data-nhom="' + dau.dataset.nhomDau + '"]:not([hidden])');
      });
      const khong = bang.querySelector('.sl-khong-khop');
      if (khong) khong.hidden = conHien > 0;
    });
  };
  if (oTim) oTim.addEventListener('input', apDung);
  document.querySelectorAll('[data-loc-tt]').forEach(nut => nut.addEventListener('click', () => {
    locTt = nut.dataset.locTt;
    document.querySelectorAll('[data-loc-tt]').forEach(n => { n.classList.toggle('chon', n === nut); n.setAttribute('aria-pressed', n === nut ? 'true' : 'false'); });
    apDung();
  }));

  const hop = document.getElementById('slTraLai');
  if (!hop) return;
  const oId = hop.querySelector('#slTraLaiId'), oTen = hop.querySelector('#slTraLaiTen'), oLyDo = hop.querySelector('textarea');
  document.addEventListener('click', e => {
    const nut = e.target.closest('[data-tra-lai]');
    if (nut) {
      oId.value = nut.dataset.traLai;
      oTen.textContent = 'Trả lại sản lượng — ' + nut.dataset.ten;
      oLyDo.value = '';
      hop.showModal();
      oLyDo.focus();
    } else if (e.target.closest('[data-dong-hop]')) {
      hop.close();
    }
  });
})();
</script>
@endpush
