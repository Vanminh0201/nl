/*
 * Nền bản đồ dùng chung cho bản đồ chính và bản đồ chi tiết dự án (cấu hình: config/duan.php → ban_do.lop_nen).
 * - Tự chuyển sang nền dự phòng (khoá du_phong) nếu nền đang chọn không tải được ô nào.
 * - Nền có khoá wayback (ảnh vệ tinh Esri): chọn ảnh lưu trữ theo thời điểm (Esri Wayback) và hiện ngày chụp
 *   ảnh tại tâm bản đồ (dữ liệu metadata của từng bản phát hành Wayback).
 *
 * Dùng: var nen = NenBanDo.gan(map, { dsNen: cfg.lop_nen, chon: <select>, thoiDiem: <select>, baoLoi: fn(chu) });
 *       nen.datNen('ve_tinh');
 */
(function () {
  if (!window.L) return;

  var CAU_HINH_WAYBACK = 'https://s3-us-west-2.amazonaws.com/config.maptiles.arcgis.com/waybackconfig.json';
  var dsBan = null, dangTai = null;

  // Các bản phát hành ảnh lưu trữ, mới nhất trước: { id, ngay: 'YYYY-MM-DD', url, meta }
  function taiWayback() {
    if (dsBan) return Promise.resolve(dsBan);
    if (!dangTai) {
      dangTai = fetch(CAU_HINH_WAYBACK).then(function (r) { return r.json(); }).then(function (cfg) {
        dsBan = Object.keys(cfg).map(function (k) {
          var v = cfg[k], m = /(\d{4}-\d{2}-\d{2})/.exec(v.itemTitle || '');
          return m && v.itemURL ? { id: k, ngay: m[1], url: v.itemURL, meta: v.metadataLayerUrl } : null;
        }).filter(Boolean).sort(function (a, b) { return a.ngay < b.ngay ? 1 : -1; });
        return dsBan;
      }).catch(function () { dangTai = null; return []; });
    }
    return dangTai;
  }

  function ngayVN(s) {
    var p = s.split('-');
    return p[2] + '/' + p[1] + '/' + p[0];
  }

  // SRC_DATE2 của metadata: số mili giây hoặc chuỗi "M/D/YYYY"
  function docNgay(v) {
    if (typeof v === 'number') {
      var d = new Date(v);
      return ('0' + d.getUTCDate()).slice(-2) + '/' + ('0' + (d.getUTCMonth() + 1)).slice(-2) + '/' + d.getUTCFullYear();
    }
    var m = /^(\d{1,2})\/(\d{1,2})\/(\d{4})/.exec(String(v || ''));
    return m ? ('0' + m[2]).slice(-2) + '/' + ('0' + m[1]).slice(-2) + '/' + m[3] : null;
  }

  function gan(map, o) {
    var dsNen = o.dsNen || {}, chon = o.chon, thoiDiem = o.thoiDiem;
    var lop = null, maHienTai = null, banChon = '', loi = 0, daTai = false;
    var hop = map.getContainer();

    var thongBao = null, henAn = null;
    function bao(chu) {
      if (o.baoLoi) return o.baoLoi(chu);
      if (!chu) { if (thongBao) thongBao.hidden = true; return; }
      if (!thongBao) {
        thongBao = L.DomUtil.create('div', 'vt-thong-bao', hop);
        L.DomEvent.disableClickPropagation(thongBao);
        thongBao.addEventListener('click', function () { thongBao.hidden = true; });
      }
      thongBao.textContent = chu;
      thongBao.hidden = false;
      clearTimeout(henAn);
      henAn = setTimeout(function () { thongBao.hidden = true; }, 8000);
    }

    // Nhãn ngày chụp ảnh vệ tinh tại tâm bản đồ
    var nhanNgay = L.DomUtil.create('div', 'nb-ngay-chup', hop);
    nhanNgay.hidden = true;
    L.DomEvent.disableClickPropagation(nhanNgay);
    var henNgay = null, luotHoi = 0;

    function banDangChon(ds) {
      return (banChon && ds.filter(function (b) { return b.id === banChon; })[0]) || null;
    }

    function datNen(ma, laDuPhong) {
      var c = dsNen[ma];
      if (!c) return;
      if (lop) map.removeLayer(lop);
      maHienTai = ma; loi = 0; daTai = false;
      if (!laDuPhong) bao('');

      var url = c.tile, ghiNguon = c.ghi_nguon;
      var ban = c.wayback && dsBan ? banDangChon(dsBan) : null;
      if (ban) {
        url = ban.url.replace('{level}', '{z}').replace('{row}', '{y}').replace('{col}', '{x}');
        ghiNguon += ' · ảnh lưu trữ Wayback ' + ngayVN(ban.ngay);
      }
      lop = L.tileLayer(url, {
        maxZoom: o.maxZoom || 19, maxNativeZoom: c.max_native || 18, subdomains: 'abc', attribution: ghiNguon
      }).on('tileload', function () {
        daTai = true;
      }).on('tileerror', function () {
        if (daTai || ++loi < 4) return;
        if (c.du_phong && dsNen[c.du_phong] && c.du_phong !== ma) {
          var tenCu = c.ten;
          if (chon) chon.value = c.du_phong;
          datNen(c.du_phong, true);
          bao('Không tải được nền "' + tenCu + '" — đã tạm chuyển sang "' + dsNen[c.du_phong].ten + '".');
        } else if (loi === 4) {
          bao('Không tải được nền bản đồ. Điểm dự án và lưới điện vẫn hiển thị đúng toạ độ.');
        }
      }).addTo(map);
      lop.bringToBack();

      if (thoiDiem) {
        thoiDiem.hidden = !c.wayback;
        if (c.wayback) napThoiDiem();
      }
      capNhatNgay();
    }

    function napThoiDiem() {
      if (thoiDiem.dataset.daNap) return;
      thoiDiem.dataset.daNap = '1';
      thoiDiem.innerHTML = '<option value="">Ảnh vệ tinh mới nhất</option>';
      taiWayback().then(function (ds) {
        if (!ds.length) { delete thoiDiem.dataset.daNap; return; }
        var nhom = {};
        // Bản phát hành mới nhất trùng ảnh "mới nhất" nên bỏ qua
        ds.slice(1).forEach(function (b) { (nhom[b.ngay.slice(0, 4)] = nhom[b.ngay.slice(0, 4)] || []).push(b); });
        Object.keys(nhom).sort().reverse().forEach(function (nam) {
          var g = document.createElement('optgroup');
          g.label = 'Năm ' + nam;
          nhom[nam].forEach(function (b) {
            var op = document.createElement('option');
            op.value = b.id;
            op.textContent = 'Ảnh lưu trữ ' + ngayVN(b.ngay);
            g.appendChild(op);
          });
          thoiDiem.appendChild(g);
        });
      });
    }

    if (thoiDiem) {
      thoiDiem.addEventListener('change', function () {
        banChon = thoiDiem.value;
        taiWayback().then(function () { datNen(maHienTai); });
      });
    }

    function capNhatNgay() {
      clearTimeout(henNgay);
      if (!dsNen[maHienTai] || !dsNen[maHienTai].wayback) { nhanNgay.hidden = true; luotHoi++; return; }
      henNgay = setTimeout(hoiNgay, 450);
    }

    function hoiNgay() {
      var luot = ++luotHoi;
      taiWayback().then(function (ds) {
        if (!ds.length || luot !== luotHoi) return;
        var ban = banDangChon(ds) || ds[0];
        // Lớp metadata theo độ phân giải: 0 = 1,9 cm … 13 = 150 m; mức phóng z dùng lớp (23 − z)
        var lopId = Math.max(0, Math.min(13, 23 - Math.round(map.getZoom())));
        thu(ban, map.getCenter(), lopId, luot);
      });
    }

    function thu(ban, tam, lopId, luot) {
      var url = ban.meta + '/' + lopId + '/query?f=json&returnGeometry=false&outFields=SRC_DATE2,SRC_RES'
        + '&geometryType=esriGeometryPoint&inSR=4326&spatialRel=esriSpatialRelIntersects&geometry=' + tam.lng + ',' + tam.lat;
      fetch(url).then(function (r) { return r.json(); }).then(function (kq) {
        if (luot !== luotHoi) return;
        var f = kq && kq.features && kq.features[0];
        if (!f && lopId < 13) return thu(ban, tam, lopId + 1, luot);
        var ngay = f && docNgay(f.attributes.SRC_DATE2);
        if (!ngay) { nhanNgay.hidden = true; return; }
        nhanNgay.textContent = 'Ảnh chụp ngày ' + ngay;
        var res = f.attributes.SRC_RES;
        nhanNgay.title = 'Ngày chụp ảnh vệ tinh tại tâm bản đồ' + (res ? ' · độ phân giải khoảng ' + Number(res).toLocaleString('vi-VN') + ' m' : '');
        nhanNgay.hidden = false;
      }).catch(function () { if (luot === luotHoi) nhanNgay.hidden = true; });
    }

    map.on('moveend', capNhatNgay);

    return { datNen: datNen };
  }

  window.NenBanDo = { gan: gan };
})();
