@extends('layouts.app')
@section('tieu_de', $duAn->exists ? 'Sửa dự án' : 'Thêm dự án')

@push('dau_trang')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
@endpush

@section('noi_dung')
<div class="pad">
  <form method="post" class="sheet" enctype="multipart/form-data"
        action="{{ $duAn->exists ? route('du-an.update', $duAn) : route('du-an.store') }}">
    @csrf
    @if ($duAn->exists) @method('put') @endif

    <div class="sheet-hd"><h2>{{ $duAn->exists ? 'Sửa dự án' : 'Thêm dự án' }}</h2></div>

    <div class="sheet-bd">
      @if ($errors->any())
        <div class="loi">Chưa lưu được, cần sửa các điểm sau:
          <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
      @endif

      <fieldset>
        <legend>Thông tin chung</legend>
        <div class="grid">
          <label class="f full">Tên dự án
            <input name="ten" value="{{ old('ten', $duAn->ten) }}" required maxlength="255">
          </label>
          <label class="f">Chủ đầu tư
            <input name="chu_dau_tu" value="{{ old('chu_dau_tu', $duAn->chu_dau_tu) }}">
          </label>
          <label class="f">Tài khoản chủ đầu tư (nếu có)
            <select name="chu_dau_tu_id">
              <option value="">— Không liên kết tài khoản —</option>
              @foreach ($dsChuDauTu as $u)
                <option value="{{ $u->id }}" @selected((string) old('chu_dau_tu_id', $duAn->chu_dau_tu_id) === (string) $u->id)>{{ $u->name }} ({{ $u->email }})</option>
              @endforeach
            </select>
            <span class="hint">Gán tài khoản để chủ đầu tư đăng nhập chỉ thấy riêng dự án này.</span>
          </label>
          <label class="f">Loại hình
            <select name="loai_hinh" id="loaiHinh">
              @foreach ($dsLoaiHinh as $ma => $l)
                <option value="{{ $ma }}" data-mau="{{ $l->mau }}" data-bieu-tuong="{{ $l->bieu_tuong }}" @selected(old('loai_hinh', $duAn->loai_hinh) === $ma)>{{ $l->ten }}</option>
              @endforeach
            </select>
          </label>
          <label class="f">Địa điểm (xã, phường)
            <input name="dia_diem" value="{{ old('dia_diem', $duAn->dia_diem) }}" placeholder="VD: phường Đông Hà" list="ds-dia-diem">
          </label>
          <label class="f">Trạng thái
            <select name="trang_thai">
              @foreach ($dsTrangThai as $ten => $t)
                <option value="{{ $ten }}" @selected(old('trang_thai', $duAn->trang_thai) === $ten)>{{ $ten }}</option>
              @endforeach
            </select>
          </label>
        </div>
      </fieldset>

      <fieldset>
        <legend>Quy mô và tiến độ</legend>
        <div class="grid">
          <label class="f">Vốn đăng ký (tỷ đồng)
            <input type="number" step="0.01" min="0" name="von_dang_ky" value="{{ old('von_dang_ky', $duAn->von_dang_ky) }}">
          </label>
          <label class="f">Diện tích (ha)
            <input type="number" step="0.01" min="0" name="dien_tich" value="{{ old('dien_tich', $duAn->dien_tich) }}">
          </label>
          <label class="f">Công suất (MW) — chỉ áp dụng dự án điện, dùng để cộng tổng/thống kê
            <input type="number" step="0.01" min="0" name="cong_suat_mw" value="{{ old('cong_suat_mw', $duAn->cong_suat_mw) }}" placeholder="VD: 50">
          </label>
          <label class="f">Sản lượng điện (kWh/năm) — chỉ áp dụng dự án đã vận hành
            <input type="number" step="0.01" min="0" name="san_luong_dien" value="{{ old('san_luong_dien', $duAn->san_luong_dien) }}" placeholder="VD: 20787964">
          </label>
          <label class="f">Phát điện lên lưới từ tháng — dự án chạy thử, chưa vận hành thương mại vẫn báo cáo sản lượng
            <input type="month" name="phat_dien_tu" value="{{ old('phat_dien_tu', $duAn->phat_dien_tu) }}">
          </label>
          <label class="f">Công suất (mô tả) — hiển thị trong danh sách
            <input name="cong_suat" value="{{ old('cong_suat', $duAn->cong_suat) }}" placeholder="VD: 2x701,5 MW; 20.000 tấn/năm">
          </label>
          <label class="f">Tiến độ thực hiện (%)
            <input type="text" inputmode="decimal" maxlength="6" name="tien_do" placeholder="VD: 99,65"
                   value="{{ old('tien_do', $duAn->tien_do !== null ? \App\Models\BaoCaoTuanDuAn::so($duAn->tien_do, 2) : '') }}">
          </label>
          <label class="f">Khởi công
            <input type="date" name="ngay_khoi_cong" value="{{ old('ngay_khoi_cong', $duAn->ngay_khoi_cong?->format('Y-m-d')) }}">
          </label>
          <label class="f">Dự kiến hoàn thành
            <input type="date" name="ngay_hoan_thanh" value="{{ old('ngay_hoan_thanh', $duAn->ngay_hoan_thanh?->format('Y-m-d')) }}">
          </label>
          <label class="f full">Tiến độ hoàn thành / vận hành thương mại
            <input name="tien_do_van_hanh" value="{{ old('tien_do_van_hanh', $duAn->tien_do_van_hanh) }}" placeholder="VD: Vận hành thương mại Quý IV/2028; hoặc Tổ máy 1: 11/2026, Tổ máy 2: 12/2026">
          </label>
        </div>
      </fieldset>

      @php
        $dsHoSo = App\Models\DuAn::HO_SO_PHAP_LY;
        $dayDu = App\Models\DuAn::HO_SO_PHAP_LY_DAY_DU;
        $soHoSoCo = collect(array_keys($dsHoSo))->filter(fn ($c) => (bool) old($c, $duAn->$c))->count();
      @endphp
      <fieldset class="pl-khoi">
        <legend>Hồ sơ pháp lý đã có <span class="pl-dem" id="plDem">{{ $soHoSoCo }}/{{ count($dsHoSo) }}</span></legend>
        <div class="pl-luoi">
          @foreach ($dsHoSo as $cot => $nhan)
            <label class="pl-o" @isset($dayDu[$cot]) title="{{ $dayDu[$cot] }}" @endisset>
              <input type="checkbox" name="{{ $cot }}" value="1" @checked(old($cot, $duAn->$cot))>
              <span class="pl-so">{{ $loop->iteration }}</span>
              <span class="pl-ten">{{ $nhan }}</span>
              <span class="pl-dau" aria-hidden="true">✓</span>
            </label>
          @endforeach
        </div>
      </fieldset>
      <script>
      // Cập nhật số hồ sơ đã có ngay khi tích/bỏ tích
      (function () {
        var dem = document.getElementById('plDem');
        var o = Array.prototype.slice.call(document.querySelectorAll('.pl-o input[type=checkbox]'));
        o.forEach(function (c) {
          c.addEventListener('change', function () {
            dem.textContent = o.filter(function (x) { return x.checked; }).length + '/' + o.length;
          });
        });
      })();
      </script>

      @php
        // Văn bản xếp theo thứ tự hồ sơ, trong mỗi hồ sơ văn bản mới nhất lên trước
        $thuTuHs = array_flip(array_keys($dsHoSo));
        $dsVb = $duAn->vanBanPhapLy->sortBy(fn ($v) => [$thuTuHs[$v->ho_so] ?? 99, -($v->ngay_van_ban?->timestamp ?? 0), $v->thu_tu])->values();
        $nhanTep = '.pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.zip,.rar';
      @endphp
      <fieldset>
        <legend>Văn bản hồ sơ pháp lý</legend>
        <div id="dsVanBan">
          @foreach ($dsVb as $i => $vb)
            <div class="van-ban-hang" style="border:1px solid var(--line);border-radius:var(--radius);padding:11px;margin-bottom:9px">
              <input type="hidden" name="van_ban[{{ $i }}][id]" value="{{ $vb->id }}">
              <div class="grid">
                <label class="f">Hồ sơ
                  <select name="van_ban[{{ $i }}][ho_so]" class="vb-ho-so" required>
                    @foreach ($dsHoSo as $cot => $nhan)
                      <option value="{{ $cot }}" @selected($vb->ho_so === $cot)>{{ $loop->iteration }}. {{ $nhan }}</option>
                    @endforeach
                  </select>
                </label>
                <label class="f">Loại văn bản
                  <input name="van_ban[{{ $i }}][loai]" value="{{ $vb->loai }}" placeholder="VD: Chấp thuận lần đầu, Điều chỉnh lần 1...">
                </label>
                <label class="f">Số văn bản
                  <input name="van_ban[{{ $i }}][so_van_ban]" value="{{ $vb->so_van_ban }}" placeholder="VD: 1234/QĐ-UBND">
                </label>
                <label class="f">Ngày văn bản
                  <input type="date" name="van_ban[{{ $i }}][ngay_van_ban]" value="{{ $vb->ngay_van_ban?->format('Y-m-d') }}">
                </label>
                <label class="f">Tệp (tối đa 50 MB)
                  <input type="file" name="van_ban[{{ $i }}][tep]" accept="{{ $nhanTep }}">
                  @if ($vb->tep)
                    <a href="{{ $vb->tep_url }}" target="_blank" rel="noopener" style="display:block;margin-top:5px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $vb->ten_tep ?: 'Xem tệp hiện tại' }}</a>
                  @endif
                </label>
              </div>
              <button type="button" class="btn sm danger xoa-hang-van-ban" style="margin-top:9px">Xoá văn bản này</button>
            </div>
          @endforeach
        </div>
        <button type="button" id="btnThemVanBan" class="btn sm">+ Thêm văn bản</button>
      </fieldset>

      <template id="mauVanBan">
        <div class="van-ban-hang" style="border:1px solid var(--line);border-radius:var(--radius);padding:11px;margin-bottom:9px">
          <div class="grid">
            <label class="f">Hồ sơ
              <select name="van_ban[__I__][ho_so]" class="vb-ho-so" required>
                <option value="" disabled selected>Chọn hồ sơ</option>
                @foreach ($dsHoSo as $cot => $nhan)
                  <option value="{{ $cot }}">{{ $loop->iteration }}. {{ $nhan }}</option>
                @endforeach
              </select>
            </label>
            <label class="f">Loại văn bản
              <input name="van_ban[__I__][loai]" placeholder="VD: Chấp thuận lần đầu, Điều chỉnh lần 1...">
            </label>
            <label class="f">Số văn bản
              <input name="van_ban[__I__][so_van_ban]" placeholder="VD: 1234/QĐ-UBND">
            </label>
            <label class="f">Ngày văn bản
              <input type="date" name="van_ban[__I__][ngay_van_ban]">
            </label>
            <label class="f">Tệp (tối đa 50 MB)
              <input type="file" name="van_ban[__I__][tep]" accept="{{ $nhanTep }}">
            </label>
          </div>
          <button type="button" class="btn sm danger xoa-hang-van-ban" style="margin-top:9px">Xoá văn bản này</button>
        </div>
      </template>

      <fieldset>
        <legend>Vị trí trên bản đồ</legend>
        <div class="grid">
          <label class="f">Vĩ độ (toạ độ đại diện)
            <input type="number" step="0.0000001" name="vi_do" id="viDo" value="{{ old('vi_do', $duAn->vi_do) }}" placeholder="16.8163">
          </label>
          <label class="f">Kinh độ (toạ độ đại diện)
            <input type="number" step="0.0000001" name="kinh_do" id="kinhDo" value="{{ old('kinh_do', $duAn->kinh_do) }}" placeholder="107.1003">
          </label>
          <label class="f full">Nhập lớp từ file KML, KMZ, GeoJSON — giữ nguyên mọi điểm/đường/vùng có trong file, kể cả khi file có nhiều lớp
            <input type="file" id="tepViTri" accept=".kml,.kmz,.geojson,.json">
          </label>
        </div>
        <p class="hint" id="ketQuaTepViTri"></p>
        <p class="hint">
          @if ($duAn->exists)
            Đã có toạ độ đại diện — bấm xem/phóng bản đồ thoải mái, sẽ KHÔNG tự đổi vị trí. Muốn đặt lại thì bấm
            <button type="button" class="btn sm" id="datLaiViTri">Đặt lại vị trí</button> rồi bấm 1 điểm trên bản đồ.
          @else
            Bấm vào bản đồ (khi không bật chế độ vẽ) để đặt toạ độ đại diện — dùng để tính khoảng cách tới trạm
            biến áp.
          @endif
          Nếu dự án đã có lớp đường/vùng, chấm đại diện sẽ tự ẩn trên bản đồ hiển thị cho đỡ rối.
        </p>
        <p class="hint">
          Vẽ thêm 1 lớp:
          <select id="loaiVe" class="btn sm" style="padding:3px 6px">
            <option value="diem">Điểm</option>
            <option value="duong">Đường (tuyến hở, VD đường dây)</option>
            <option value="vung" selected>Vùng (khép kín, VD ranh giới cụm)</option>
          </select>
          <span id="bieuTuongVe" class="bt-chon" style="display:none" data-chon="diem_moc"></span>
          <button type="button" class="btn sm" id="veLop">Vẽ</button>
          <button type="button" class="btn sm" id="xongVe" style="display:none">Xong</button>
          <button type="button" class="btn sm" id="chinhViTri" hidden
                  title="Kéo biểu tượng các điểm (VD vị trí tua-bin) tới đúng chỗ trên ảnh vệ tinh">Chỉnh vị trí điểm</button>
          <span id="trangThaiVe"></span>
          <span id="demDaChinh" class="hint" style="margin:0"></span>
        </p>
        <div id="mapchon"></div>

        <div class="tablewrap" style="margin-top:10px" id="oDsLop" hidden>
          <table style="min-width:0">
            <thead><tr><th>Loại</th><th>Biểu tượng</th><th>Tên (tuỳ chọn)</th><th class="num">Số điểm</th><th></th><th></th></tr></thead>
            <tbody id="dsLopBody"></tbody>
          </table>
        </div>
        {{-- Toàn bộ lớp hình học gửi thành 1 ô JSON (xem LuuDuAnRequest::prepareForValidation) --}}
        <div id="oHinhHocAn"><input type="hidden" name="hinh_hoc_json" id="hinhHocJson"></div>
      </fieldset>

      <fieldset>
        <legend>Ghi chú</legend>
        <label class="f">Vướng mắc, kiến nghị, nội dung cần theo dõi
          <textarea name="ghi_chu">{{ old('ghi_chu', $duAn->ghi_chu) }}</textarea>
        </label>
      </fieldset>
    </div>

    <div class="sheet-ft">
      <a href="{{ $duAn->exists ? route('du-an.show', $duAn) : route('du-an.index') }}" class="btn">Huỷ</a>
      <button class="btn primary">Lưu dự án</button>
    </div>
  </form>
</div>

<datalist id="ds-dia-diem">
  @foreach ($diaDiem as $dd)
    <option value="{{ $dd }}">
  @endforeach
</datalist>
@endsection

@push('cuoi_trang')
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>
<script>
  window.DS_BIEU_TUONG = @json($dsBieuTuong);
</script>
<script src="{{ asset('js/bieu-tuong.js') }}?v={{ filemtime(public_path('js/bieu-tuong.js')) }}"></script>
<script>
(function () {
  var cfg = @json(config('duan.ban_do'));
  var viDo = document.getElementById('viDo');
  var kinhDo = document.getElementById('kinhDo');
  var loaiHinhEl = document.getElementById('loaiHinh');

  var map = L.map('mapchon').setView(
    [viDo.value || cfg.vi_do, kinhDo.value || cfg.kinh_do],
    viDo.value ? 13 : cfg.muc_phong
  );
  // Phóng quá mức ảnh gốc (18) thêm 2 bậc để kéo đặt điểm cho chính xác
  L.tileLayer(cfg.tile, { maxNativeZoom: 18, maxZoom: 20, attribution: cfg.ghi_nguon }).addTo(map);

  function thoatHtml(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  /* danh sách lớp hình học của dự án: điểm/đường/vùng, nạp sẵn nếu đang sửa dự án đã có lớp */
  var dsLop = @json($hinhHocBanDau);
  var lopVe = L.layerGroup().addTo(map);
  var diem = null;

  function coHinhDang() {
    return dsLop.some(function (l) { return l.hien_thi && (l.loai === 'duong' || l.loai === 'vung'); });
  }

  function mauLoaiHinh() {
    var tuyChon = loaiHinhEl.options[loaiHinhEl.selectedIndex];
    return tuyChon ? tuyChon.dataset.mau : '#b4531c';
  }

  function bieuTuongLoaiHinh() {
    var tuyChon = loaiHinhEl.options[loaiHinhEl.selectedIndex];
    return tuyChon ? tuyChon.dataset.bieuTuong : 'khac';
  }

  function veDiem() {
    if (diem) { map.removeLayer(diem); diem = null; }
    if (!viDo.value || !kinhDo.value || coHinhDang()) return;
    diem = L.marker([viDo.value, kinhDo.value], {
      icon: BieuTuongLoaiHinh.divIcon(bieuTuongLoaiHinh(), mauLoaiHinh(), 30)
    }).addTo(map);
  }

  /* ---------- chỉnh vị trí từng điểm (kéo thả trên bản đồ, nhập toạ độ, bấm đặt trên bản đồ) ---------- */
  var dangChinh = false;      // chế độ kéo thả điểm đang bật
  var dangDatNhom = null;     // [i, j] khi đang chờ bấm bản đồ để đặt 1 điểm
  var mocDiem = {};           // "i,j" → marker của điểm đó trên bản đồ

  function daChinh(l, j) {
    return !!(l._goc && Object.prototype.hasOwnProperty.call(l._goc, j));
  }

  function iconDiem(l, j) {
    return BieuTuongLoaiHinh.divIcon(l.bieu_tuong || 'diem_moc', mauLoaiHinh(), 26, daChinh(l, j) ? 'da-chinh' : '');
  }

  function chuToaDo(p) {
    return Number(p[0]).toFixed(7) + ', ' + Number(p[1]).toFixed(7);
  }

  function capNhatDemChinh() {
    var n = 0;
    dsLop.forEach(function (l) { if (l._goc) n += Object.keys(l._goc).length; });
    document.getElementById('demDaChinh').textContent = n ? 'Đã chỉnh vị trí ' + n + ' điểm — bấm "Lưu dự án" để ghi lại.' : '';
  }

  /** Đặt toạ độ mới cho điểm j của lớp i; giữ toạ độ gốc để hoàn tác */
  function datToaDo(i, j, lat, lng) {
    var l = dsLop[i];
    l._goc = l._goc || {};
    if (!daChinh(l, j)) l._goc[j] = l.toa_do[j].slice();
    l.toa_do[j] = [+Number(lat).toFixed(7), +Number(lng).toFixed(7)];
    if (l._goc[j][0] === l.toa_do[j][0] && l._goc[j][1] === l.toa_do[j][1]) delete l._goc[j];
    capNhatMotDiem(i, j);
  }

  function hoanTac(i, j) {
    var l = dsLop[i];
    if (!daChinh(l, j)) return;
    l.toa_do[j] = l._goc[j];
    delete l._goc[j];
    capNhatMotDiem(i, j);
  }

  /** Cập nhật riêng 1 điểm (marker, ô toạ độ trong bảng) — không vẽ lại cả danh sách cho khỏi mất chỗ đang cuộn */
  function capNhatMotDiem(i, j) {
    var l = dsLop[i];
    var m = mocDiem[i + ',' + j];
    if (m) { m.setLatLng(l.toa_do[j]); m.setIcon(iconDiem(l, j)); if (dangChinh) m.dragging.enable(); }
    var o = document.querySelector('[data-toa="' + i + ',' + j + '"]');
    if (o) {
      o.value = chuToaDo(l.toa_do[j]);
      o.classList.toggle('da-chinh', daChinh(l, j));
      o.classList.remove('sai');
      var ht = document.querySelector('[data-hoan-tac="' + i + ',' + j + '"]');
      if (ht) ht.hidden = !daChinh(l, j);
    }
    dongBoO();
    capNhatDemChinh();
  }

  function batTatChinh(bat) {
    dangChinh = bat;
    Object.keys(mocDiem).forEach(function (k) {
      var m = mocDiem[k];
      if (m.dragging) { if (bat) m.dragging.enable(); else m.dragging.disable(); }
    });
    var nut = document.getElementById('chinhViTri');
    nut.textContent = bat ? 'Tắt chỉnh vị trí' : 'Chỉnh vị trí điểm';
    nut.classList.toggle('primary', bat);
    trangThaiVe.textContent = bat
      ? 'Kéo biểu tượng tới đúng vị trí trên ảnh vệ tinh (phóng to hết cỡ để đặt chính xác).'
      : '';
  }

  function veTatCaLop() {
    lopVe.clearLayers();
    mocDiem = {};
    var mau = mauLoaiHinh();
    dsLop.forEach(function (l, i) {
      if (!l.hien_thi) return;
      if (l.loai === 'diem') {
        // Nhiều điểm gộp chung 1 lớp (VD nhập từ file khảo sát): vẽ từng điểm riêng,
        // cả nhóm vẫn ẩn/hiện cùng nhau vì chỉ 1 dòng "hiện" điều khiển chung.
        l.toa_do.forEach(function (p, j) {
          var m = L.marker(p, { icon: iconDiem(l, j), autoPan: true });
          var nhan = (l.nhan_diem && l.nhan_diem[j]) || l.ten;
          if (nhan) m.bindTooltip(thoatHtml(nhan));
          m.on('dragend', function () {
            var ll = m.getLatLng();
            datToaDo(i, j, ll.lat, ll.lng);
          });
          m.addTo(lopVe);
          if (dangChinh) m.dragging.enable();
          mocDiem[i + ',' + j] = m;
        });
        return;
      }
      var mauLop = l.mau || mau;
      var lop = l.loai === 'duong'
        ? L.polyline(l.toa_do, { color: mauLop, weight: 3 })
        : L.polygon(l.toa_do, { color: mauLop, weight: 2, fillOpacity: 0.15 });
      var nhanLop = (l.nhan_diem && l.nhan_diem[0]) || l.ten;
      if (nhanLop) lop.bindTooltip(thoatHtml(nhanLop));
      lop.addTo(lopVe);
    });
  }

  /* Toàn bộ lớp gửi kèm biểu mẫu thành 1 ô JSON — dự án lớn có hàng chục nghìn toạ độ, mỗi toạ độ
     1 ô ẩn sẽ vượt giới hạn số ô của PHP (max_input_vars) và bị cắt bớt khi lưu. */
  var oJson = document.getElementById('hinhHocJson');
  function dongBoO() {
    oJson.value = JSON.stringify(dsLop.map(function (l) {
      var o = { loai: l.loai, ten: l.ten || null, hien_thi: !!l.hien_thi, toa_do: l.toa_do };
      if (l.loai === 'diem') {
        o.bieu_tuong = l.bieu_tuong || 'diem_moc';
        if (l.toa_do.length > 1) {
          o.nhan_diem = l.toa_do.map(function (p, j) { return (l.nhan_diem && l.nhan_diem[j]) || null; });
        }
      } else {
        // Màu riêng và nhãn của đường/vùng (VD ký hiệu lô đất nhập từ bản vẽ quy hoạch) giữ nguyên khi lưu lại
        if (l.mau) o.mau = l.mau;
        if (l.nhan_diem && l.nhan_diem[0]) o.nhan_diem = [l.nhan_diem[0]];
      }
      return o;
    }));
  }

  /* xoá 1 điểm khỏi 1 lớp nhóm điểm; xoá luôn cả lớp nếu chỉ còn 0 điểm */
  function xoaDiemTrongNhom(i, j) {
    var l = dsLop[i];
    l.toa_do.splice(j, 1);
    if (l.nhan_diem) l.nhan_diem.splice(j, 1);
    // Chỉ số các điểm phía sau lùi 1 — dời theo toạ độ gốc đã lưu để hoàn tác
    if (l._goc) {
      var goc = {};
      Object.keys(l._goc).forEach(function (k) { if (+k < j) goc[k] = l._goc[k]; else if (+k > j) goc[+k - 1] = l._goc[k]; });
      l._goc = goc;
    }
    if (l.toa_do.length === 0) dsLop.splice(i, 1);
    capNhatTatCa();
  }

  function veDanhSachDiem(l, i) {
    return '<div style="max-height:320px;overflow:auto;border:1px solid #d7dde3;border-radius:6px;margin-top:6px">'
      + '<table style="min-width:0"><thead><tr><th style="width:44px">STT</th><th>Tên điểm</th>'
      + '<th>Toạ độ (vĩ độ, kinh độ) — sửa trực tiếp, kéo trên bản đồ hoặc bấm "Đặt"</th><th></th></tr></thead><tbody>'
      + l.toa_do.map(function (p, j) {
        var ten = (l.nhan_diem && l.nhan_diem[j]) || '';
        var k = i + ',' + j;
        return '<tr>'
          + '<td>' + (j + 1) + '</td>'
          + '<td><input data-i="' + i + '" data-j="' + j + '" data-truong="nhan_diem" value="' + thoatHtml(ten) + '" placeholder="(không tên)" style="width:100%"></td>'
          + '<td style="white-space:nowrap"><input class="o-toa-do' + (daChinh(l, j) ? ' da-chinh' : '') + '" data-toa="' + k + '" value="' + chuToaDo(p) + '"'
          + ' title="Dán toạ độ dạng: 17.2943810, 106.7648880" spellcheck="false">'
          + ' <button type="button" class="btn sm" data-xem-diem="' + k + '" title="Phóng tới điểm này trên bản đồ">Xem</button>'
          + ' <button type="button" class="btn sm" data-dat-diem="' + k + '" title="Bấm 1 chỗ trên bản đồ để đặt điểm này">Đặt</button>'
          + ' <button type="button" class="btn sm" data-hoan-tac="' + k + '" title="Trả về toạ độ ban đầu"' + (daChinh(l, j) ? '' : ' hidden') + '>↺</button></td>'
          + '<td><button type="button" class="btn sm danger" data-xoa-diem="' + k + '">Xoá</button></td>'
          + '</tr>';
      }).join('')
      + '</tbody></table></div>';
  }

  function veDanhSachLop() {
    var box = document.getElementById('oDsLop');
    var than = document.getElementById('dsLopBody');
    box.hidden = dsLop.length === 0;

    than.innerHTML = dsLop.map(function (l, i) {
      var laNhomDiem = l.loai === 'diem' && l.toa_do.length > 1;
      var nhan = l.loai === 'diem'
        ? (laNhomDiem ? 'Điểm (nhóm ' + l.toa_do.length + ')' : 'Điểm')
        : (l.loai === 'duong' ? 'Đường' : 'Vùng');
      if (laNhomDiem) {
        nhan += ' <button type="button" class="btn sm" data-mo-nhom="' + i + '">' + (l._moRong ? 'Ẩn danh sách ▴' : 'Xem danh sách ▾') + '</button>';
      }
      var oBieuTuong = l.loai === 'diem'
        ? '<span class="bt-chon" data-i="' + i + '" data-truong="bieu_tuong" data-chon="' + (l.bieu_tuong || 'diem_moc') + '"></span>'
        : '';
      var hangChinh = '<tr>'
        + '<td>' + nhan + '</td>'
        + '<td>' + oBieuTuong + '</td>'
        + '<td><input data-i="' + i + '" data-truong="ten" value="' + thoatHtml(l.ten || '') + '" placeholder="(không tên)" style="width:100%"></td>'
        + '<td class="num">' + l.toa_do.length + '</td>'
        + '<td style="white-space:nowrap"><label><input type="checkbox" data-i="' + i + '" data-truong="hien_thi"' + (l.hien_thi ? ' checked' : '') + '> hiện</label></td>'
        + '<td><button type="button" class="btn sm danger" data-xoa="' + i + '">Xoá</button></td>'
        + '</tr>';
      var hangChiTiet = laNhomDiem && l._moRong
        ? '<tr><td colspan="6">' + veDanhSachDiem(l, i) + '</td></tr>'
        : '';
      return hangChinh + hangChiTiet;
    }).join('');

    Array.prototype.forEach.call(than.querySelectorAll('[data-truong="ten"]'), function (el) {
      el.addEventListener('input', function () { dsLop[+el.dataset.i].ten = el.value; veTatCaLop(); dongBoO(); });
    });
    Array.prototype.forEach.call(than.querySelectorAll('[data-truong="bieu_tuong"]'), function (el) {
      BieuTuongLoaiHinh.khoiTaoBoChon(el, 'bieu_tuong');
      el.addEventListener('change', function () { dsLop[+el.dataset.i].bieu_tuong = el.querySelector('.bt-chon-gt').value; veTatCaLop(); dongBoO(); });
    });
    Array.prototype.forEach.call(than.querySelectorAll('[data-truong="hien_thi"]'), function (el) {
      el.addEventListener('change', function () { dsLop[+el.dataset.i].hien_thi = el.checked; capNhatTatCa(); });
    });
    Array.prototype.forEach.call(than.querySelectorAll('[data-xoa]'), function (el) {
      el.onclick = function () { dsLop.splice(+el.dataset.xoa, 1); capNhatTatCa(); };
    });
    Array.prototype.forEach.call(than.querySelectorAll('[data-mo-nhom]'), function (el) {
      el.onclick = function () { dsLop[+el.dataset.moNhom]._moRong = !dsLop[+el.dataset.moNhom]._moRong; veDanhSachLop(); };
    });
    Array.prototype.forEach.call(than.querySelectorAll('[data-truong="nhan_diem"]'), function (el) {
      el.addEventListener('input', function () {
        var l = dsLop[+el.dataset.i];
        l.nhan_diem = l.nhan_diem || [];
        l.nhan_diem[+el.dataset.j] = el.value;
        dongBoO();
      });
    });
    Array.prototype.forEach.call(than.querySelectorAll('[data-xoa-diem]'), function (el) {
      el.onclick = function () {
        var cap = el.dataset.xoaDiem.split(',');
        xoaDiemTrongNhom(+cap[0], +cap[1]);
      };
    });

    // Sửa toạ độ trực tiếp: dán "vĩ độ, kinh độ" (VD chép từ Google Maps)
    Array.prototype.forEach.call(than.querySelectorAll('[data-toa]'), function (el) {
      el.addEventListener('change', function () {
        var cap = el.dataset.toa.split(',');
        var m = String(el.value).trim().match(/^(-?\d+(?:\.\d+)?)\s*[,;\s]\s*(-?\d+(?:\.\d+)?)$/);
        if (!m || Math.abs(+m[1]) > 90 || Math.abs(+m[2]) > 180) {
          el.classList.add('sai');
          el.title = 'Toạ độ không hợp lệ — ghi dạng: 17.2943810, 106.7648880';
          return;
        }
        datToaDo(+cap[0], +cap[1], +m[1], +m[2]);
      });
    });
    Array.prototype.forEach.call(than.querySelectorAll('[data-xem-diem]'), function (el) {
      el.onclick = function () {
        var cap = el.dataset.xemDiem.split(',');
        var p = dsLop[+cap[0]].toa_do[+cap[1]];
        document.getElementById('mapchon').scrollIntoView({ behavior: 'smooth', block: 'center' });
        map.setView(p, Math.max(map.getZoom(), 18));
        var mk = mocDiem[el.dataset.xemDiem];
        if (mk && mk.getTooltip()) mk.openTooltip();
      };
    });
    Array.prototype.forEach.call(than.querySelectorAll('[data-dat-diem]'), function (el) {
      el.onclick = function () {
        var cap = el.dataset.datDiem.split(',');
        var l = dsLop[+cap[0]];
        dangDatNhom = [+cap[0], +cap[1]];
        trangThaiVe.textContent = 'Bấm lên bản đồ để đặt "' + (((l.nhan_diem && l.nhan_diem[+cap[1]]) || l.ten || 'điểm ' + (+cap[1] + 1))) + '" (Esc để huỷ).';
        document.getElementById('mapchon').scrollIntoView({ behavior: 'smooth', block: 'center' });
        map.setView(l.toa_do[+cap[1]], Math.max(map.getZoom(), 17));
      };
    });
    Array.prototype.forEach.call(than.querySelectorAll('[data-hoan-tac]'), function (el) {
      el.onclick = function () {
        var cap = el.dataset.hoanTac.split(',');
        hoanTac(+cap[0], +cap[1]);
      };
    });
  }

  function capNhatTatCa() {
    veTatCaLop();
    veDanhSachLop();
    dongBoO();
    veDiem();
    capNhatDemChinh();
    var coDiem = dsLop.some(function (l) { return l.loai === 'diem'; });
    document.getElementById('chinhViTri').hidden = !coDiem;
    if (!coDiem && dangChinh) batTatChinh(false);
  }

  /* ---------- vẽ tay thêm 1 lớp ---------- */
  var loaiVeEl = document.getElementById('loaiVe');
  var bieuTuongVeEl = document.getElementById('bieuTuongVe');
  var veLopBtn = document.getElementById('veLop');
  var xongVeBtn = document.getElementById('xongVe');
  var trangThaiVe = document.getElementById('trangThaiVe');
  var dangVe = false, dangVeMang = [], lopTamThoi = null;

  BieuTuongLoaiHinh.khoiTaoBoChon(bieuTuongVeEl, 'bieu_tuong');
  function capNhatHienBieuTuongVe() {
    bieuTuongVeEl.style.display = loaiVeEl.value === 'diem' ? 'inline-block' : 'none';
  }
  loaiVeEl.addEventListener('change', capNhatHienBieuTuongVe);
  capNhatHienBieuTuongVe();

  function xongVe() {
    dangVe = false;
    xongVeBtn.style.display = 'none';
    veLopBtn.style.display = '';
    trangThaiVe.textContent = '';
    if (lopTamThoi) { map.removeLayer(lopTamThoi); lopTamThoi = null; }

    var loai = loaiVeEl.value;
    var toiThieu = loai === 'diem' ? 1 : (loai === 'duong' ? 2 : 3);
    if (dangVeMang.length < toiThieu) { dangVeMang = []; return; }

    var ten = window.prompt('Tên lớp (có thể để trống):', '') || null;
    dsLop.push({
      loai: loai, ten: ten, toa_do: dangVeMang.slice(), hien_thi: true,
      bieu_tuong: loai === 'diem' ? bieuTuongVeEl.querySelector('.bt-chon-gt').value : null
    });
    dangVeMang = [];
    capNhatTatCa();
  }

  veLopBtn.onclick = function () {
    dangVe = true;
    dangVeMang = [];
    veLopBtn.style.display = 'none';
    xongVeBtn.style.display = '';
    trangThaiVe.textContent = loaiVeEl.value === 'diem'
      ? 'Bấm 1 điểm trên bản đồ.'
      : 'Bấm nhiều điểm trên bản đồ, xong bấm "Xong".';
  };
  xongVeBtn.onclick = xongVe;

  // Du an DA CO toa do dai dien tu truoc thi bam thuong vao ban do (de xem/kiem tra vi tri) se
  // KHONG tu doi diem nua — tranh vo tinh de mat diem dung khi chi dinh xem anh ve tinh. Chi khi
  // bam nut "Dat lai vi tri" (dangDatDiem = true, tu tat lai ngay sau khi dung 1 lan) hoac du an
  // CHUA co toa do nao thi bam thang vao ban do moi co tac dung dat diem.
  var dangDatDiem = false;
  var datLaiViTriBtn = document.getElementById('datLaiViTri');
  if (datLaiViTriBtn) {
    datLaiViTriBtn.addEventListener('click', function () {
      dangDatDiem = true;
      trangThaiVe.textContent = 'Bấm 1 điểm trên bản đồ để đặt lại vị trí đại diện.';
    });
  }

  document.getElementById('chinhViTri').addEventListener('click', function () { batTatChinh(!dangChinh); });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && dangDatNhom) { dangDatNhom = null; batTatChinh(dangChinh); }
  });

  map.on('click', function (e) {
    if (dangDatNhom) {
      datToaDo(dangDatNhom[0], dangDatNhom[1], e.latlng.lat, e.latlng.lng);
      dangDatNhom = null;
      batTatChinh(dangChinh); // trả lại dòng hướng dẫn đúng chế độ đang bật
      return;
    }
    if (dangVe) {
      dangVeMang.push([+e.latlng.lat.toFixed(7), +e.latlng.lng.toFixed(7)]);
      if (lopTamThoi) map.removeLayer(lopTamThoi);
      lopTamThoi = (loaiVeEl.value !== 'duong' && dangVeMang.length >= 3
        ? L.polygon(dangVeMang, { color: '#1f6f43', weight: 2, dashArray: '5,4', fillOpacity: 0.1 })
        : L.polyline(dangVeMang, { color: '#1f6f43', weight: 2, dashArray: '5,4' })
      ).addTo(map);
      if (loaiVeEl.value === 'diem') xongVe();
    } else if (!viDo.value || dangDatDiem) {
      viDo.value = e.latlng.lat.toFixed(7);
      kinhDo.value = e.latlng.lng.toFixed(7);
      veDiem();
      if (dangDatDiem) { dangDatDiem = false; trangThaiVe.textContent = ''; }
    }
  });

  loaiHinhEl.addEventListener('change', veDiem);
  [viDo, kinhDo].forEach(function (el) { el.addEventListener('change', veDiem); });

  /* ---------- nhập lớp từ file KML/KMZ/GeoJSON: cộng thêm vào danh sách đang có ---------- */
  var thongBaoTep = document.getElementById('ketQuaTepViTri');
  document.getElementById('tepViTri').addEventListener('change', function (e) {
    var tep = e.target.files[0];
    if (!tep) return;

    thongBaoTep.textContent = 'Đang đọc file…';
    thongBaoTep.style.color = '';

    var fd = new FormData();
    fd.append('tep', tep);
    fd.append('_token', document.querySelector('meta[name=csrf-token]').content);

    fetch(@json(route('du-an.vi-tri-tu-file')), { method: 'POST', body: fd, headers: { Accept: 'application/json' } })
      .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
      .then(function (res) {
        if (!res.ok) {
          thongBaoTep.textContent = res.j.message || 'Không đọc được file.';
          thongBaoTep.style.color = 'var(--danger)';
          return;
        }

        if (!viDo.value || !kinhDo.value) {
          viDo.value = res.j.vi_do;
          kinhDo.value = res.j.kinh_do;
        }
        res.j.lop.forEach(function (l) {
          dsLop.push({ loai: l.loai, ten: l.ten, toa_do: l.toa_do, nhan_diem: l.nhan_diem || null, hien_thi: true, bieu_tuong: l.bieu_tuong || 'diem_moc' });
        });
        capNhatTatCa();

        var toanBo = null;
        lopVe.eachLayer(function (l) {
          var b = l.getBounds ? l.getBounds() : l.getLatLng().toBounds(20);
          toanBo = toanBo ? toanBo.extend(b) : b;
        });
        if (toanBo) map.fitBounds(toanBo, { padding: [20, 20], maxZoom: 16 });

        thongBaoTep.textContent = 'Đã thêm ' + res.j.lop.length + ' lớp từ file vào danh sách bên dưới. Kiểm tra lại rồi lưu.';
        thongBaoTep.style.color = 'var(--ok)';
      })
      .catch(function () {
        thongBaoTep.textContent = 'Không gửi được file lên máy chủ.';
        thongBaoTep.style.color = 'var(--danger)';
      })
      .finally(function () { e.target.value = ''; });
  });

  capNhatTatCa();

  // --- Danh sách văn bản chủ trương đầu tư (thêm/xoá dòng động) ---
  var dsVanBan = document.getElementById('dsVanBan');
  var mauVanBan = document.getElementById('mauVanBan');
  var demVanBan = dsVanBan.children.length;

  document.getElementById('btnThemVanBan').addEventListener('click', function () {
    var html = mauVanBan.innerHTML.split('__I__').join(String(demVanBan));
    var boc = document.createElement('div');
    boc.innerHTML = html.trim();
    dsVanBan.appendChild(boc.firstElementChild);
    demVanBan++;
  });

  dsVanBan.addEventListener('click', function (e) {
    if (e.target.classList.contains('xoa-hang-van-ban')) {
      e.target.closest('.van-ban-hang').remove();
    }
  });

  // Chọn hồ sơ cho văn bản thì tích luôn hồ sơ đó là "đã có" (máy chủ cũng tự tích khi lưu)
  dsVanBan.addEventListener('change', function (e) {
    if (!e.target.classList.contains('vb-ho-so') || !e.target.value) return;
    var o = document.querySelector('.pl-o input[name="' + e.target.value + '"]');
    if (o && !o.checked) {
      o.checked = true;
      o.dispatchEvent(new Event('change'));
    }
  });
})();
</script>
@endpush
