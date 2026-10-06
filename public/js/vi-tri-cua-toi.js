/*
 * Nút "Vị trí của tôi" cho bản đồ Leaflet: dùng định vị của trình duyệt (cần HTTPS và người dùng cho phép),
 * hiện chấm xanh kèm vòng tròn sai số, đưa bản đồ tới vị trí đó và tiếp tục cập nhật khi người dùng di chuyển.
 * Toạ độ chỉ dùng trên trình duyệt, không gửi về máy chủ.
 *
 * Dùng: ViTriCuaToi.gan(map, { position: 'bottomleft' })
 * Bấm lần đầu: bật định vị và đưa tới vị trí. Đang bật mà vị trí nằm ngoài khung nhìn: đưa về vị trí.
 * Đang bật và vị trí đang hiện trên màn hình: tắt định vị.
 */
(function () {
  if (!window.L) return;

  var ICON = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">'
    + '<circle cx="12" cy="12" r="7"/><circle cx="12" cy="12" r="2.6" fill="currentColor" stroke="none"/>'
    + '<path d="M12 2.5v2.6M12 18.9v2.6M2.5 12h2.6M18.9 12h2.6"/></svg>';

  var LOI = {
    1: 'Trình duyệt chưa cho phép truy cập vị trí. Bật quyền vị trí cho trang này trong cài đặt trình duyệt rồi bấm lại.',
    2: 'Không xác định được vị trí — kiểm tra định vị (GPS) của thiết bị.',
    3: 'Hết thời gian chờ định vị — thử bấm lại.'
  };

  function soVN(v, le) {
    return v.toLocaleString('vi-VN', { minimumFractionDigits: le, maximumFractionDigits: le });
  }

  function gan(map, tuyChon) {
    tuyChon = tuyChon || {};
    if (!map.getPane('viTriCuaToi')) {
      map.createPane('viTriCuaToi').style.zIndex = 640; // trên các lớp dự án, lưới điện; dưới popup
    }
    var veSvg = L.svg({ pane: 'viTriCuaToi' });
    var dangBat = false, dauTien = true, maTheoDoi = null, viTri = null;
    var cham = null, vong = null, nut = null, thongBao = null, henAn = null;

    function bao(chu) {
      if (!thongBao) {
        thongBao = L.DomUtil.create('div', 'vt-thong-bao', map.getContainer());
        L.DomEvent.disableClickPropagation(thongBao);
        thongBao.addEventListener('click', function () { thongBao.hidden = true; });
      }
      thongBao.textContent = chu;
      thongBao.hidden = false;
      clearTimeout(henAn);
      henAn = setTimeout(function () { thongBao.hidden = true; }, 7000);
    }

    function trangThaiNut(lop) {
      nut.classList.remove('dang-tim', 'dang-bat');
      if (lop) nut.classList.add(lop);
      nut.setAttribute('aria-pressed', String(dangBat));
      nut.title = dangBat ? 'Đang hiện vị trí của bạn — bấm để tắt' : 'Vị trí của tôi';
    }

    function noiDung() {
      return '<b>Vị trí của bạn</b><br>' + soVN(viTri.lat, 6) + ', ' + soVN(viTri.lng, 6)
        + '<br><span class="vt-phu">Sai số khoảng ± ' + soVN(Math.round(viTri.saiSo), 0) + ' m</span>';
    }

    function capNhat(pos) {
      viTri = { lat: pos.coords.latitude, lng: pos.coords.longitude, saiSo: pos.coords.accuracy || 0 };
      var ll = [viTri.lat, viTri.lng];
      if (!cham) {
        vong = L.circle(ll, { radius: viTri.saiSo, pane: 'viTriCuaToi', renderer: veSvg, interactive: false,
          color: '#1a73e8', weight: 1, opacity: .5, fillColor: '#1a73e8', fillOpacity: .12 }).addTo(map);
        cham = L.circleMarker(ll, { radius: 7, pane: 'viTriCuaToi', renderer: veSvg,
          color: '#fff', weight: 2.5, fillColor: '#1a73e8', fillOpacity: 1 }).addTo(map);
        cham.bindPopup(noiDung, { pane: 'popupPane' });
      } else {
        cham.setLatLng(ll);
        vong.setLatLng(ll).setRadius(viTri.saiSo);
        if (cham.isPopupOpen()) cham.setPopupContent(noiDung());
      }
      if (dauTien) {
        dauTien = false;
        trangThaiNut('dang-bat');
        // Sai số lớn (định vị theo mạng) thì không phóng quá sâu
        var muc = viTri.saiSo > 2000 ? 12 : viTri.saiSo > 300 ? 14 : 16;
        map.setView(ll, Math.max(map.getZoom(), muc));
        if (viTri.saiSo > 1000) bao('Vị trí chỉ gần đúng (sai số khoảng ' + soVN(Math.round(viTri.saiSo / 1000), 0) + ' km) — bật GPS để chính xác hơn.');
      }
    }

    function loi(e) {
      var chuaCoViTri = !viTri;
      bao(LOI[e.code] || 'Không lấy được vị trí.');
      if (chuaCoViTri || e.code === 1) tat();
    }

    function bat() {
      if (!navigator.geolocation) {
        bao('Trình duyệt này không hỗ trợ xác định vị trí.');
        return;
      }
      if (!window.isSecureContext) {
        bao('Chỉ xác định được vị trí khi mở trang bằng https://.');
        return;
      }
      dangBat = true;
      dauTien = true;
      trangThaiNut('dang-tim');
      maTheoDoi = navigator.geolocation.watchPosition(capNhat, loi, { enableHighAccuracy: true, timeout: 20000, maximumAge: 10000 });
    }

    function tat() {
      dangBat = false;
      if (maTheoDoi !== null) navigator.geolocation.clearWatch(maTheoDoi);
      maTheoDoi = null;
      if (cham) { map.removeLayer(cham); map.removeLayer(vong); }
      cham = vong = viTri = null;
      trangThaiNut('');
    }

    var NutViTri = L.Control.extend({
      options: { position: tuyChon.position || 'topleft' },
      onAdd: function () {
        var khung = L.DomUtil.create('div', 'leaflet-bar vt-khung');
        nut = L.DomUtil.create('a', 'vt-nut', khung);
        nut.href = '#';
        nut.setAttribute('role', 'button');
        nut.setAttribute('aria-label', 'Vị trí của tôi');
        nut.innerHTML = ICON;
        trangThaiNut('');
        L.DomEvent.disableClickPropagation(khung);
        L.DomEvent.on(nut, 'click', function (e) {
          L.DomEvent.preventDefault(e);
          if (!dangBat) return bat();
          if (!viTri) return; // đang chờ định vị
          if (!map.getBounds().contains([viTri.lat, viTri.lng])) {
            map.setView([viTri.lat, viTri.lng], Math.max(map.getZoom(), 15));
          } else {
            tat();
          }
        });
        return khung;
      }
    });
    new NutViTri().addTo(map);

    return { bat: bat, tat: tat };
  }

  window.ViTriCuaToi = { gan: gan };
})();
