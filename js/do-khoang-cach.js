/*
 * Công cụ đo khoảng cách cho bản đồ Leaflet.
 * Bấm nút thước rồi bấm lần lượt các điểm trên bản đồ: mỗi điểm có nhãn khoảng cách cộng dồn, bảng bên cạnh nút ghi tổng.
 * Kết thúc: bấm lại điểm cuối, nhấp đúp, phím Enter hoặc nút ✓. Backspace bỏ điểm vừa thêm, Esc huỷ.
 * Kết quả giữ trên bản đồ đến khi bấm ✕ hoặc đo lần mới.
 * Khoảng cách tính theo đường trắc địa trên ellipsoid WGS-84 (công thức Vincenty).
 *
 * Dùng: DoKhoangCach.gan(map, { position: 'bottomleft' })
 * Trong lúc đo, một lớp trong suốt phủ lên bản đồ để các đối tượng (dự án, lưới điện) không nhận cú bấm;
 * kéo, cuộn phóng bản đồ vẫn dùng bình thường.
 */
(function () {
  if (!window.L) return;

  var ICON = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
    + '<path d="M3 16.5L16.5 3l4.5 4.5L7.5 21z"/><path d="M7 12.5l2 2M10 9.5l2 2M13 6.5l2 2"/></svg>';

  /** Khoảng cách trắc địa (m) giữa 2 điểm trên ellipsoid WGS-84 — Vincenty; không hội tụ thì dùng công thức cầu */
  function khoangCach(a, b) {
    var A = 6378137, F = 1 / 298.257223563, B = A * (1 - F), rad = Math.PI / 180;
    var L1 = (b.lng - a.lng) * rad, U1 = Math.atan((1 - F) * Math.tan(a.lat * rad)), U2 = Math.atan((1 - F) * Math.tan(b.lat * rad));
    var sU1 = Math.sin(U1), cU1 = Math.cos(U1), sU2 = Math.sin(U2), cU2 = Math.cos(U2);
    var lam = L1, lamCu, n = 0, sS, cS, sig, sA, c2A, c2Sm, C;
    do {
      var sL = Math.sin(lam), cL = Math.cos(lam);
      sS = Math.sqrt((cU2 * sL) * (cU2 * sL) + (cU1 * sU2 - sU1 * cU2 * cL) * (cU1 * sU2 - sU1 * cU2 * cL));
      if (sS === 0) return 0;
      cS = sU1 * sU2 + cU1 * cU2 * cL;
      sig = Math.atan2(sS, cS);
      sA = cU1 * cU2 * sL / sS;
      c2A = 1 - sA * sA;
      c2Sm = c2A ? cS - 2 * sU1 * sU2 / c2A : 0;
      C = F / 16 * c2A * (4 + F * (4 - 3 * c2A));
      lamCu = lam;
      lam = L1 + (1 - C) * F * sA * (sig + C * sS * (c2Sm + C * cS * (-1 + 2 * c2Sm * c2Sm)));
    } while (Math.abs(lam - lamCu) > 1e-12 && ++n < 200);
    if (n >= 200) return L.CRS.Earth.distance(a, b);
    var u2 = c2A * (A * A - B * B) / (B * B);
    var KA = 1 + u2 / 16384 * (4096 + u2 * (-768 + u2 * (320 - 175 * u2)));
    var KB = u2 / 1024 * (256 + u2 * (-128 + u2 * (74 - 47 * u2)));
    var dS = KB * sS * (c2Sm + KB / 4 * (cS * (-1 + 2 * c2Sm * c2Sm) - KB / 6 * c2Sm * (-3 + 4 * sS * sS) * (-3 + 4 * c2Sm * c2Sm)));
    return B * KA * (sig - dS);
  }

  function chuKc(m) {
    function s(v, le) { return v.toLocaleString('vi-VN', { minimumFractionDigits: le, maximumFractionDigits: le }); }
    if (m < 1000) return s(Math.round(m), 0) + ' m';
    return s(m / 1000, m < 100000 ? 2 : 1) + ' km';
  }

  function gan(map, tuyChon) {
    tuyChon = tuyChon || {};
    if (!map.getPane('doKhoangCach')) {
      var pane = map.createPane('doKhoangCach');
      pane.style.zIndex = 645; // trên lớp dự án, lưới điện, vị trí của tôi; dưới nhãn, popup
      pane.style.pointerEvents = 'none';
    }
    var ve = L.svg({ pane: 'doKhoangCach', padding: 0.5 });
    var dangDo = false, diem = [], tong = 0;
    var nen = null, duong = null, keoNen = null, keo = null, nhanKeo = null, moc = [], nhan = [];
    var nut, bang, chuTong, nutXong, nutXoa, lopChan, zoomDup;

    function nhanTai(ll, chu, cuoi) {
      return L.tooltip({ permanent: true, direction: 'right', offset: [8, 0], className: 'dk-nhan' + (cuoi ? ' dk-nhan-cuoi' : '') })
        .setLatLng(ll).setContent(chu).addTo(map);
    }

    function veLai() {
      var lls = diem.slice();
      if (!duong) {
        nen = L.polyline(lls, { pane: 'doKhoangCach', renderer: ve, interactive: false, color: '#fff', weight: 6, opacity: .85 }).addTo(map);
        duong = L.polyline(lls, { pane: 'doKhoangCach', renderer: ve, interactive: false, color: '#e8590c', weight: 3 }).addTo(map);
      } else {
        nen.setLatLngs(lls);
        duong.setLatLngs(lls);
      }
      chuTong.textContent = chuKc(tong);
      bang.hidden = false;
      nutXong.hidden = !dangDo;
    }

    function themDiem(ll) {
      if (diem.length) tong += khoangCach(diem[diem.length - 1], ll);
      diem.push(ll);
      moc.push(L.circleMarker(ll, { pane: 'doKhoangCach', renderer: ve, interactive: false, radius: 4.5,
        color: '#e8590c', weight: 2, fillColor: '#fff', fillOpacity: 1 }).addTo(map));
      if (nhan.length) nhan[nhan.length - 1].getElement() && L.DomUtil.removeClass(nhan[nhan.length - 1].getElement(), 'dk-nhan-cuoi');
      nhan.push(nhanTai(ll, diem.length === 1 ? 'Bắt đầu' : chuKc(tong), diem.length > 1));
      veLai();
    }

    function boDiemCuoi() {
      if (!diem.length) return;
      var ll = diem.pop();
      map.removeLayer(moc.pop());
      map.removeLayer(nhan.pop());
      if (diem.length) tong -= khoangCach(diem[diem.length - 1], ll);
      if (diem.length < 2) tong = 0;
      if (nhan.length > 1 && nhan[nhan.length - 1].getElement()) L.DomUtil.addClass(nhan[nhan.length - 1].getElement(), 'dk-nhan-cuoi');
      xoaKeo();
      veLai();
    }

    function xoaKeo() {
      if (keo) { map.removeLayer(keo); map.removeLayer(keoNen); map.removeLayer(nhanKeo); }
      keo = keoNen = nhanKeo = null;
    }

    function veKeo(ll) {
      if (!dangDo || !diem.length) return;
      var dau = diem[diem.length - 1], d = tong + khoangCach(dau, ll);
      if (!keo) {
        keoNen = L.polyline([dau, ll], { pane: 'doKhoangCach', renderer: ve, interactive: false, color: '#fff', weight: 5, opacity: .7 }).addTo(map);
        keo = L.polyline([dau, ll], { pane: 'doKhoangCach', renderer: ve, interactive: false, color: '#e8590c', weight: 2, dashArray: '6 6' }).addTo(map);
        nhanKeo = L.tooltip({ permanent: true, direction: 'right', offset: [12, 0], className: 'dk-nhan dk-nhan-keo' }).setLatLng(ll).addTo(map);
      } else {
        keoNen.setLatLngs([dau, ll]);
        keo.setLatLngs([dau, ll]);
        nhanKeo.setLatLng(ll);
      }
      nhanKeo.setContent(chuKc(d));
    }

    function xoaHet() {
      xoaKeo();
      [nen, duong].concat(moc, nhan).forEach(function (l) { if (l) map.removeLayer(l); });
      nen = duong = null; moc = []; nhan = []; diem = []; tong = 0;
      bang.hidden = true;
    }

    function batDau() {
      xoaHet();
      dangDo = true;
      map.closePopup();
      lopChan = L.DomUtil.create('div', 'dk-lop-chan', map.getContainer());
      zoomDup = map.doubleClickZoom.enabled();
      if (zoomDup) map.doubleClickZoom.disable();
      map.on('click', khiBam);
      map.on('mousemove', khiRe);
      map.on('dblclick', ketThuc);
      document.addEventListener('keydown', khiPhim);
      nut.classList.add('dang-do');
      nut.setAttribute('aria-pressed', 'true');
      nut.title = 'Đang đo — bấm để kết thúc';
      bang.hidden = true;
    }

    function ketThuc() {
      if (!dangDo) return;
      dangDo = false;
      xoaKeo();
      if (lopChan) { L.DomUtil.remove(lopChan); lopChan = null; }
      if (zoomDup) map.doubleClickZoom.enable();
      map.off('click', khiBam);
      map.off('mousemove', khiRe);
      map.off('dblclick', ketThuc);
      document.removeEventListener('keydown', khiPhim);
      nut.classList.remove('dang-do');
      nut.setAttribute('aria-pressed', 'false');
      nut.title = 'Đo khoảng cách';
      if (diem.length < 2) xoaHet(); else veLai();
    }

    function khiBam(e) {
      if (diem.length) {
        // bấm lại vào điểm cuối (hoặc lần bấm thứ 2 của nhấp đúp) thì kết thúc
        var p = map.latLngToContainerPoint(diem[diem.length - 1]);
        if (p.distanceTo(e.containerPoint) < 8) return ketThuc();
      }
      themDiem(e.latlng);
    }

    function khiRe(e) { veKeo(e.latlng); }

    function khiPhim(e) {
      var t = e.target && e.target.tagName;
      if (t === 'INPUT' || t === 'TEXTAREA' || t === 'SELECT') return;
      if (e.key === 'Escape') { e.preventDefault(); ketThucHuy(); }
      else if (e.key === 'Enter') { e.preventDefault(); ketThuc(); }
      else if (e.key === 'Backspace') { e.preventDefault(); boDiemCuoi(); }
    }

    function ketThucHuy() {
      ketThuc();
      xoaHet();
    }

    var NutDo = L.Control.extend({
      options: { position: tuyChon.position || 'topleft' },
      onAdd: function () {
        var khung = L.DomUtil.create('div', 'leaflet-bar dk-khung');
        nut = L.DomUtil.create('a', 'dk-nut', khung);
        nut.href = '#';
        nut.setAttribute('role', 'button');
        nut.setAttribute('aria-label', 'Đo khoảng cách');
        nut.setAttribute('aria-pressed', 'false');
        nut.title = 'Đo khoảng cách';
        nut.innerHTML = ICON;
        bang = L.DomUtil.create('div', 'dk-bang', khung);
        bang.hidden = true;
        chuTong = L.DomUtil.create('b', '', bang);
        nutXong = L.DomUtil.create('button', 'dk-xong', bang);
        nutXong.type = 'button'; nutXong.title = 'Kết thúc đo'; nutXong.textContent = '✓';
        nutXoa = L.DomUtil.create('button', 'dk-xoa', bang);
        nutXoa.type = 'button'; nutXoa.title = 'Xoá kết quả đo'; nutXoa.textContent = '✕';
        L.DomEvent.disableClickPropagation(khung);
        L.DomEvent.disableScrollPropagation(khung);
        L.DomEvent.on(nut, 'click', function (e) {
          L.DomEvent.preventDefault(e);
          if (dangDo) ketThuc(); else batDau();
        });
        L.DomEvent.on(nutXong, 'click', ketThuc);
        L.DomEvent.on(nutXoa, 'click', function () { if (dangDo) ketThucHuy(); else xoaHet(); });
        return khung;
      }
    });
    new NutDo().addTo(map);

    return { batDau: batDau, ketThuc: ketThuc, xoa: xoaHet, khoangCach: khoangCach };
  }

  window.DoKhoangCach = { gan: gan, khoangCach: khoangCach };
})();
