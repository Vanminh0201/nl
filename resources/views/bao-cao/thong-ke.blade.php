@extends('layouts.app')
@section('tieu_de', $tabTienDo ? 'Tiến độ & giải ngân' : 'Thống kê báo cáo')

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
<div class="pad bc-trang tk-trang {{ $tabTienDo ? 'tk-tab-tien-do' : 'tk-tab-bao-cao' }}">
  @include('bao-cao._tab', ['dangChon' => $tabTienDo ? 'bao-cao.tien-do' : 'bao-cao.thong-ke'])

  <div class="bc-thanh-ky">
    <form method="get" action="{{ route($tabTienDo ? 'bao-cao.tien-do' : 'bao-cao.thong-ke') }}">
      <select name="thang" onchange="this.form.submit()" aria-label="Chọn tháng thống kê">
        <option value="">Toàn bộ các tháng</option>
        @foreach (array_reverse($dsThang) as $k)
          <option value="{{ $k->ma() }}" @selected($chon && $k->bang($chon))>{{ $k->ten() }}</option>
        @endforeach
      </select>
      @if ($tabTienDo)
        @php
          $tenDuAnDuocChon = $dsDuAn->firstWhere('id', $duAnDuocChon)?->ten ?? '';
        @endphp
        <div class="tk-loc-du-an" data-tk-project-picker>
          <label class="sr-only" for="tk-tim-du-an">Tìm dự án</label>
          <input id="tk-tim-du-an" type="search" value="{{ $tenDuAnDuocChon }}"
                 placeholder="Gõ để tìm dự án…" autocomplete="off"
                 role="combobox" aria-autocomplete="list" aria-expanded="false"
                 aria-controls="tk-goi-y-du-an" aria-label="Gõ để tìm và chọn dự án">
          <input type="hidden" name="du_an" value="{{ $duAnDuocChon ?? '' }}" data-tk-project-id>
          <button type="button" class="tk-xoa-loc-du-an" data-tk-clear-project @disabled($duAnDuocChon === null)>
            Tất cả dự án
          </button>
          <div id="tk-goi-y-du-an" class="tk-goi-y-du-an" role="listbox" aria-label="Kết quả tìm dự án" hidden>
            <p class="tk-khong-co-du-an" data-tk-no-project hidden>Không tìm thấy dự án.</p>
          </div>
          <template data-tk-project-options>
            @foreach ($dsDuAn as $duAn)
              <span data-project-id="{{ $duAn->id }}" data-project-name="{{ $duAn->ten }}"></span>
            @endforeach
          </template>
          <span class="sr-only" role="status" aria-live="polite" data-tk-project-count></span>
        </div>
      @endif
    </form>
    <span class="hint" style="margin:0">
      @if ($tabTienDo)
        {{ $soTuan }} tuần
      @else
        {{ $soTuan }} tuần · {{ $soThang }} tháng
      @endif
    </span>
    <span style="flex:1"></span>
    <button type="button" class="btn" onclick="window.print()">{{ $tabTienDo ? 'In tiến độ' : 'In thống kê' }}</button>
  </div>

  @unless ($tabTienDo)
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
  @endunless

  @if ($tabTienDo)
  @php
    $diemTienDoMoiNhat = collect($bieuDoTienDo)->filter(fn ($diem) => $diem['tien_do'] !== null)->last();
    $diemGiaiNganMoiNhat = collect($bieuDoTienDo)->filter(fn ($diem) => $diem['giai_ngan_nam'] !== null)->last();
  @endphp
  <section class="tk-tong-quan" aria-label="Tổng quan tiến độ và giải ngân">
    <div class="tk-tong-quan-dau">
      <span class="tk-tong-quan-nhan">TỔNG QUAN DỰ ÁN</span>
      <h1>Tiến độ &amp; giải ngân</h1>
      <p>Theo dõi số liệu toàn danh mục và tình hình cập nhật của từng dự án.</p>
    </div>
    <div class="tk-tong-quan-chi-so">
      <article class="tk-chi-so">
        <span class="tk-chi-so-nhan">Tiến độ bình quân gần nhất</span>
        <strong>{{ $diemTienDoMoiNhat ? \App\Models\BaoCaoTuanDuAn::so($diemTienDoMoiNhat['tien_do'], 2).'%' : '—' }}</strong>
        <small>{{ $diemTienDoMoiNhat['nhan'] ?? 'Chưa có dữ liệu' }}
          @if ($diemTienDoMoiNhat && $diemTienDoMoiNhat['so_du_an_tien_do'])
            · {{ $diemTienDoMoiNhat['so_du_an_tien_do'] }}/{{ $tongDuAnTrongPhamVi }} dự án có số liệu
          @endif
        </small>
      </article>
      <article class="tk-chi-so">
        <span class="tk-chi-so-nhan">Giải ngân lũy kế gần nhất</span>
        <strong>{{ $diemGiaiNganMoiNhat ? \App\Models\BaoCaoTuanDuAn::so($diemGiaiNganMoiNhat['giai_ngan_nam'], 3) : '—' }}</strong>
        <small>{{ $diemGiaiNganMoiNhat['nhan'] ?? 'Chưa có dữ liệu' }} · tỷ đồng
          @if ($diemGiaiNganMoiNhat && $diemGiaiNganMoiNhat['so_du_an_giai_ngan'])
            · {{ $diemGiaiNganMoiNhat['so_du_an_giai_ngan'] }}/{{ $tongDuAnTrongPhamVi }} dự án có số liệu
          @endif
        </small>
      </article>
      <article class="tk-chi-so tk-chi-so-bien-dong">
        <span class="tk-chi-so-nhan">Biến động giải ngân cùng nhóm dự án</span>
        @if ($diemGiaiNganMoiNhat && $diemGiaiNganMoiNhat['giai_ngan_chenh_lech'] !== null)
          <strong @class([
            'tang' => $diemGiaiNganMoiNhat['giai_ngan_chenh_lech'] > 0,
            'giam' => $diemGiaiNganMoiNhat['giai_ngan_chenh_lech'] < 0,
          ])>
            {{ $diemGiaiNganMoiNhat['giai_ngan_chenh_lech'] > 0 ? '+' : '' }}{{ \App\Models\BaoCaoTuanDuAn::so($diemGiaiNganMoiNhat['giai_ngan_chenh_lech'], 3) }}
            <span>tỷ đồng</span>
          </strong>
          <small>
            @if ($diemGiaiNganMoiNhat['giai_ngan_thay_doi_phan_tram'] !== null)
              {{ $diemGiaiNganMoiNhat['giai_ngan_thay_doi_phan_tram'] > 0 ? '+' : '' }}{{ \App\Models\BaoCaoTuanDuAn::so($diemGiaiNganMoiNhat['giai_ngan_thay_doi_phan_tram'], 1) }}% · {{ $diemGiaiNganMoiNhat['so_du_an_so_sanh_giai_ngan'] }} dự án đối chiếu
            @elseif ($diemGiaiNganMoiNhat['giai_ngan_ky_truoc'] === 0 && $diemGiaiNganMoiNhat['giai_ngan_chenh_lech'] > 0)
              Mới phát sinh · {{ $diemGiaiNganMoiNhat['so_du_an_so_sanh_giai_ngan'] }} dự án đối chiếu
            @else
              {{ $diemGiaiNganMoiNhat['so_du_an_so_sanh_giai_ngan'] }} dự án đối chiếu tuần trước
            @endif
          </small>
        @else
          <strong>—</strong>
          <small>Chưa đủ dữ liệu so sánh</small>
        @endif
      </article>
    </div>
  </section>
  <p class="tk-phuong-phap">
    Tiến độ danh mục là bình quân số học của các dự án có số liệu, không phải bình quân trọng số theo vốn.
    Biến động tuần chỉ đối chiếu những dự án có số liệu ở cả hai tuần.
  </p>

  <div class="tk-bieu-do-luoi" data-tk-du-lieu
       data-du-lieu="{{ json_encode($bieuDoTienDo, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) }}">
    <section class="sheet tk-bieu-do">
      <div class="sheet-hd">
        <div>
          <span class="tk-bieu-do-so">01</span>
          <h2>Tiến độ dự án theo tuần</h2>
          <p class="hint">{{ $duAnDuocChon ? 'Tiến độ của dự án được chọn.' : 'Bình quân số học các dự án có số liệu; màu biến động tính trên nhóm dự án chung.' }}</p>
        </div>
        <span class="tk-don-vi">Đơn vị: %</span>
      </div>
      <div class="sheet-bd">
        <div id="bd-tien-do-tuan" class="tk-bieu-do-khung" data-tk-chart="tien-do">
          <p class="tk-bieu-do-trang-thai">Đang dựng biểu đồ tiến độ…</p>
        </div>
        <div class="tk-bieu-do-chu-giai" aria-label="Chú giải biểu đồ tiến độ">
          <span><i></i>Tiến độ</span>
          <span><i class="nut tang"></i>Tăng so với tuần trước</span>
          <span><i class="nut giam"></i>Giảm so với tuần trước</span>
        </div>
      </div>
    </section>
    <section class="sheet tk-bieu-do">
      <div class="sheet-hd">
        <div>
          <span class="tk-bieu-do-so">02</span>
          <h2>Giải ngân theo tuần</h2>
          <p class="hint">So sánh giải ngân lũy kế với kế hoạch vốn năm.</p>
        </div>
        <span class="tk-don-vi">Đơn vị: tỷ đồng</span>
      </div>
      <div class="sheet-bd">
        <div id="bd-giai-ngan-tuan" class="tk-bieu-do-khung" data-tk-chart="giai-ngan">
          <p class="tk-bieu-do-trang-thai">Đang dựng biểu đồ giải ngân…</p>
        </div>
        <div class="tk-bieu-do-chu-giai" aria-label="Chú giải biểu đồ giải ngân">
          <span><i class="tang"></i>Đã giải ngân</span>
          <span><i class="ke-hoach net"></i>Kế hoạch vốn năm</span>
          <span><i class="nut tang"></i>Tăng so với tuần trước</span>
          <span><i class="nut giam"></i>Giảm so với tuần trước</span>
        </div>
      </div>
    </section>
  </div>

  <section class="sheet tk-so-sanh tk-du-an">
    <div class="sheet-hd">
      <div>
        <h2>Tiến độ và giải ngân theo từng dự án</h2>
        <p class="hint">Mỗi dòng lấy báo cáo gần nhất trong khoảng thời gian đang chọn; chênh lệch so với tuần liền trước của chính dự án đó.</p>
      </div>
      <span class="tk-so-du-lieu">{{ collect($tienDoTheoDuAn)->filter(fn ($dong) => $dong['ky'] !== null)->count() }}/{{ $tongDuAnTrongPhamVi }} có báo cáo</span>
    </div>
    <div class="tablewrap">
      <table class="tk-bang tk-bang-du-an">
        <thead>
          <tr>
            <th class="trai">Dự án</th>
            <th class="giua">Báo cáo gần nhất</th>
            <th class="giua">Tiến độ</th>
            <th class="giua">Tăng / giảm tiến độ</th>
            <th class="giua">Giải ngân năm (tỷ đồng)</th>
            <th class="giua">Tăng / giảm giải ngân</th>
            <th class="giua">Kế hoạch vốn năm (tỷ đồng)</th>
          </tr>
        </thead>
        <tbody>
        @forelse ($tienDoTheoDuAn as $duAn)
          <tr>
            <td class="tk-ten-du-an">
              <b>{{ $duAn['ten'] }}</b>
              @if ($duAn['trang_thai'])
                <small>{{ $duAn['trang_thai'] }}</small>
              @endif
            </td>
            <td class="giua">
              @if ($duAn['ky'])
                <span class="tk-ky-du-an">{{ $duAn['ky'] }}</span>
              @else
                <span class="tk-bien-dong trung-tinh">Chưa có báo cáo</span>
              @endif
            </td>
            <td class="giua">
              @if ($duAn['tien_do'] !== null)
                <b class="tk-gia-tri-tien-do">{{ \App\Models\BaoCaoTuanDuAn::so($duAn['tien_do'], 2) }}%</b>
                <span class="tk-thanh-tien-do" role="img" aria-label="Tiến độ {{ \App\Models\BaoCaoTuanDuAn::so($duAn['tien_do'], 2) }}%">
                  <i style="width:{{ max(0, min(100, $duAn['tien_do'])) }}%"></i>
                </span>
              @else
                —
              @endif
            </td>
            <td class="giua">
              @if ($duAn['tien_do_chenh_lech'] === null)
                <span class="tk-bien-dong trung-tinh">{{ $duAn['ky'] ? 'Chưa đủ kỳ so sánh' : '—' }}</span>
              @else
                <span class="tk-bien-dong {{ $duAn['tien_do_chenh_lech'] > 0 ? 'tang' : ($duAn['tien_do_chenh_lech'] < 0 ? 'giam' : 'trung-tinh') }}">
                  {{ $duAn['tien_do_chenh_lech'] > 0 ? '▲ +' : ($duAn['tien_do_chenh_lech'] < 0 ? '▼ ' : '• ') }}{{ \App\Models\BaoCaoTuanDuAn::so($duAn['tien_do_chenh_lech'], 2) }} điểm %
                </span>
              @endif
            </td>
            <td class="giua">{{ $duAn['giai_ngan_nam'] === null ? '—' : \App\Models\BaoCaoTuanDuAn::so($duAn['giai_ngan_nam'], 3) }}</td>
            <td class="giua">
              @if ($duAn['giai_ngan_chenh_lech'] === null)
                <span class="tk-bien-dong trung-tinh">{{ $duAn['ky'] ? 'Chưa đủ kỳ so sánh' : '—' }}</span>
              @else
                <span class="tk-bien-dong {{ $duAn['giai_ngan_chenh_lech'] > 0 ? 'tang' : ($duAn['giai_ngan_chenh_lech'] < 0 ? 'giam' : 'trung-tinh') }}">
                  {{ $duAn['giai_ngan_chenh_lech'] > 0 ? '▲ +' : ($duAn['giai_ngan_chenh_lech'] < 0 ? '▼ ' : '• ') }}{{ \App\Models\BaoCaoTuanDuAn::so($duAn['giai_ngan_chenh_lech'], 3) }} tỷ
                </span>
              @endif
            </td>
            <td class="giua">{{ $duAn['ke_hoach_von_nam'] === null ? '—' : \App\Models\BaoCaoTuanDuAn::so($duAn['ke_hoach_von_nam'], 3) }}</td>
          </tr>
        @empty
          <tr><td colspan="7" class="trong">Không có dự án trong phạm vi lọc.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </section>

  <div class="sheet tk-so-sanh">
    <div class="sheet-hd">
      <div>
        <h2>So sánh biến động theo tuần</h2>
        <p class="hint">Tiến độ thay đổi bình quân trong nhóm dự án có đủ cả hai tuần; giải ngân thay đổi theo cùng nhóm dự án trong năm tài chính.</p>
      </div>
      <div class="tk-chu-giai" aria-label="Chú giải biến động">
        <span><i class="tang"></i>Tăng</span>
        <span><i class="giam"></i>Giảm</span>
        <span><i class="trung-tinh"></i>Chưa đủ kỳ so sánh</span>
      </div>
    </div>
    <div class="tablewrap">
      <table class="tk-bang tk-bang-bien-dong">
        <thead>
          <tr>
            <th class="trai">Kỳ báo cáo</th>
            <th class="giua">Có số liệu / tổng</th>
            <th class="giua">Tiến độ</th>
            <th class="giua">Nhóm dự án đối chiếu</th>
            <th class="giua">Đã giải ngân (tỷ đồng)</th>
            <th class="giua">Tăng / giảm (tỷ đồng)</th>
            <th class="giua">Tăng / giảm (%)</th>
          </tr>
        </thead>
        <tbody>
        @foreach ($bieuDoTienDo as $diem)
          <tr>
            <td><b>{{ $diem['nhan'] }}</b></td>
            <td class="giua">{{ $diem['so_du_an_tien_do'] }}/{{ $diem['tong_du_an_trong_pham_vi'] }}</td>
            <td class="giua">{{ $diem['tien_do'] === null ? '—' : \App\Models\BaoCaoTuanDuAn::so($diem['tien_do'], 2).'%' }}</td>
            <td class="giua">
              @if ($diem['tien_do_chenh_lech'] === null)
                <span class="tk-bien-dong trung-tinh">{{ $diem['so_du_an_so_sanh_tien_do'] ? '—' : 'Chưa đủ kỳ so sánh' }}</span>
              @else
                <span class="tk-bien-dong {{ $diem['tien_do_chenh_lech'] > 0 ? 'tang' : ($diem['tien_do_chenh_lech'] < 0 ? 'giam' : 'trung-tinh') }}">
                  {{ $diem['tien_do_chenh_lech'] > 0 ? '▲ Tăng ' : ($diem['tien_do_chenh_lech'] < 0 ? '▼ Giảm ' : '• Không đổi ') }}{{ \App\Models\BaoCaoTuanDuAn::so(abs($diem['tien_do_chenh_lech']), 2) }} điểm %
                </span>
                @if ($diem['so_du_an_so_sanh_tien_do'])
                  <small class="tk-so-du-an-doi-chieu">{{ $diem['so_du_an_so_sanh_tien_do'] }} dự án</small>
                @endif
              @endif
            </td>
            <td class="giua">{{ $diem['giai_ngan_nam'] === null ? '—' : \App\Models\BaoCaoTuanDuAn::so($diem['giai_ngan_nam'], 3) }}</td>
            <td class="giua">
              @if ($diem['giai_ngan_chenh_lech'] === null)
                <span class="tk-bien-dong trung-tinh">Chưa đủ kỳ so sánh</span>
              @else
                <span class="tk-bien-dong {{ $diem['giai_ngan_chenh_lech'] > 0 ? 'tang' : ($diem['giai_ngan_chenh_lech'] < 0 ? 'giam' : 'trung-tinh') }}">
                  {{ $diem['giai_ngan_chenh_lech'] > 0 ? '▲ Tăng ' : ($diem['giai_ngan_chenh_lech'] < 0 ? '▼ Giảm ' : '• Không đổi ') }}{{ \App\Models\BaoCaoTuanDuAn::so(abs($diem['giai_ngan_chenh_lech']), 3) }}
                </span>
                @if ($diem['so_du_an_so_sanh_giai_ngan'])
                  <small class="tk-so-du-an-doi-chieu">{{ $diem['so_du_an_so_sanh_giai_ngan'] }} dự án</small>
                @endif
              @endif
            </td>
            <td class="giua">
              @if ($diem['giai_ngan_thay_doi_phan_tram'] !== null)
                {{ $diem['giai_ngan_thay_doi_phan_tram'] > 0 ? '+' : '' }}{{ \App\Models\BaoCaoTuanDuAn::so($diem['giai_ngan_thay_doi_phan_tram'], 1) }}%
              @elseif ($diem['giai_ngan_chenh_lech'] !== null && $diem['giai_ngan_ky_truoc'] === 0 && $diem['giai_ngan_chenh_lech'] > 0)
                Mới phát sinh
              @else
                —
              @endif
            </td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>
  </div>
  @endif
</div>
@endsection

@push('cuoi_trang')
@if ($tabTienDo)
<script src="{{ asset('js/bao-cao-tien-do.js') }}?v={{ filemtime(public_path('js/bao-cao-tien-do.js')) }}"></script>
@endif
@endpush
