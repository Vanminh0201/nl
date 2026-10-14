(function () {
  var mauMacDinh = '#1f6682';
  var mauTang = '#28734f';
  var mauGiam = '#b43c36';
  var mauKeHoach = '#8997a2';
  var chieuCao = 335;
  var le = { tren: 20, phai: 22, duoi: 58, trai: 64 };

  function taoPhanTu(ten, thuocTinh, noiDung) {
    var phanTu = document.createElementNS('http://www.w3.org/2000/svg', ten);
    Object.keys(thuocTinh || {}).forEach(function (khoa) {
      phanTu.setAttribute(khoa, thuocTinh[khoa]);
    });
    if (noiDung !== undefined) phanTu.textContent = noiDung;
    return phanTu;
  }

  function dinhDang(so, chuSoLe) {
    return Number(so).toLocaleString('vi-VN', { maximumFractionDigits: chuSoLe });
  }

  function mauBienDong(chenhLech, macDinh) {
    if (chenhLech > 0) return mauTang;
    if (chenhLech < 0) return mauGiam;
    return macDinh;
  }

  function buocTruc(toiDa) {
    var buocTho = toiDa / 5;
    var mu = Math.pow(10, Math.floor(Math.log10(buocTho)));
    var tyLe = buocTho / mu;
    var heSo = tyLe <= 1 ? 1 : (tyLe <= 2 ? 2 : (tyLe <= 5 ? 5 : 10));
    return heSo * mu;
  }

  function taoTrucY(svg, chieuRong, vungVe, cauHinh) {
    var buoc = cauHinh.coDinh ? 20 : buocTruc(cauHinh.toiDa);
    var toiDa = cauHinh.coDinh ? 100 : Math.max(buoc, Math.ceil(cauHinh.toiDa / buoc) * buoc);
    var diemTruc = [];

    for (var giaTri = 0; giaTri <= toiDa + buoc / 100; giaTri += buoc) {
      var y = le.tren + vungVe.cao * (1 - giaTri / toiDa);
      diemTruc.push({ giaTri: giaTri, y: y });
      svg.appendChild(taoPhanTu('line', {
        x1: le.trai,
        x2: chieuRong - le.phai,
        y1: y,
        y2: y,
        'class': giaTri === 0 ? 'tk-bieu-do-truc' : 'tk-bieu-do-grid'
      }));
      svg.appendChild(taoPhanTu('text', {
        x: le.trai - 10,
        y: y + 4,
        'text-anchor': 'end',
        'class': 'tk-bieu-do-nhan-truc'
      }, dinhDang(giaTri, 2) + (cauHinh.donViTruc || '')));
    }

    svg.appendChild(taoPhanTu('text', {
      x: 16,
      y: le.tren + vungVe.cao / 2,
      transform: 'rotate(-90 16 ' + (le.tren + vungVe.cao / 2) + ')',
      'text-anchor': 'middle',
      'class': 'tk-bieu-do-nhan-ten'
    }, cauHinh.tenTruc));

    return toiDa;
  }

  function chiSoNhan(soLuong, chieuRong) {
    var toiDaNhan = Math.max(2, Math.floor((chieuRong - le.trai - le.phai) / 86));
    var buoc = Math.max(1, Math.ceil((soLuong - 1) / (toiDaNhan - 1)));
    var cacChiSo = [];

    for (var i = 0; i < soLuong; i += buoc) cacChiSo.push(i);
    if (soLuong > 1 && cacChiSo[cacChiSo.length - 1] !== soLuong - 1) cacChiSo.push(soLuong - 1);
    return cacChiSo;
  }

  function nhanNgan(ky) {
    var ketQua = ky.match(/Tuần\s+(\d+)\/(\d{4})/);
    return ketQua ? ketQua[1] + '/' + ketQua[2].slice(-2) : ky;
  }

  function chuanHoaTimKiem(giaTri) {
    return giaTri.normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .replace(/[đĐ]/g, 'd')
      .toLocaleLowerCase('vi')
      .trim();
  }

  function khoiTaoTimDuAn() {
    var boLoc = document.querySelector('[data-tk-project-picker]');
    if (!boLoc) return;

    var form = boLoc.closest('form');
    var oTimKiem = boLoc.querySelector('[role="combobox"]');
    var oDuAn = boLoc.querySelector('[data-tk-project-id]');
    var nutXoa = boLoc.querySelector('[data-tk-clear-project]');
    var danhSach = boLoc.querySelector('[role="listbox"]');
    var thongBao = boLoc.querySelector('[data-tk-no-project]');
    var demKetQua = boLoc.querySelector('[data-tk-project-count]');
    var duLieuDuAn = boLoc.querySelector('[data-tk-project-options]');
    var cacDuAn = Array.from(duLieuDuAn.content.querySelectorAll('[data-project-id]')).map(function (duAn) {
      return { id: duAn.dataset.projectId, ten: duAn.dataset.projectName };
    });
    var tenDuAnDaChon = oTimKiem.value;
    var cacKetQua = [];
    var cacNutKetQua = [];
    var viTriDangChon = -1;
    var soGoiYToiDa = 20;

    function dongDanhSach() {
      danhSach.hidden = true;
      oTimKiem.setAttribute('aria-expanded', 'false');
      oTimKiem.removeAttribute('aria-activedescendant');
      cacNutKetQua.forEach(function (nut) {
        nut.classList.remove('dang-chon');
      });
      viTriDangChon = -1;
    }

    function hienKetQua() {
      var tuKhoa = chuanHoaTimKiem(oTimKiem.value);
      var tatCaKetQua = cacDuAn.filter(function (duAn) {
        return chuanHoaTimKiem(duAn.ten).includes(tuKhoa);
      });
      cacKetQua = tatCaKetQua.slice(0, soGoiYToiDa);
      cacNutKetQua = cacKetQua.map(function (duAn) {
        var nut = document.createElement('button');
        nut.type = 'button';
        nut.id = 'tk-goi-y-du-an-' + duAn.id;
        nut.setAttribute('role', 'option');
        nut.setAttribute('aria-selected', duAn.id === oDuAn.value ? 'true' : 'false');
        nut.dataset.projectId = duAn.id;
        nut.dataset.projectName = duAn.ten;
        nut.textContent = duAn.ten;
        nut.addEventListener('click', function () {
          chonDuAn(duAn);
        });
        return nut;
      });
      danhSach.replaceChildren.apply(danhSach, cacNutKetQua.concat(thongBao));
      thongBao.hidden = tatCaKetQua.length !== 0;
      demKetQua.textContent = tatCaKetQua.length > soGoiYToiDa
        ? 'Đang hiển thị ' + soGoiYToiDa + ' trong ' + tatCaKetQua.length + ' dự án. Nhập thêm để thu hẹp danh sách.'
        : tatCaKetQua.length
        ? 'Tìm thấy ' + tatCaKetQua.length + ' dự án.'
        : 'Không tìm thấy dự án phù hợp.';
      danhSach.hidden = false;
      oTimKiem.setAttribute('aria-expanded', 'true');
      oTimKiem.removeAttribute('aria-activedescendant');
      viTriDangChon = -1;
    }

    function chonDuAn(duAn) {
      oDuAn.value = duAn.id;
      oTimKiem.value = duAn.ten;
      tenDuAnDaChon = oTimKiem.value;
      nutXoa.disabled = !oDuAn.value;
      form.submit();
    }

    oTimKiem.addEventListener('focus', hienKetQua);
    oTimKiem.addEventListener('input', function () {
      if (chuanHoaTimKiem(oTimKiem.value) !== chuanHoaTimKiem(tenDuAnDaChon)) {
        oDuAn.value = '';
        nutXoa.disabled = false;
      }
      hienKetQua();
    });
    oTimKiem.addEventListener('keydown', function (suKien) {
      if (suKien.key === 'Escape') {
        dongDanhSach();
        return;
      }
      if (suKien.key === 'ArrowDown' || suKien.key === 'ArrowUp') {
        suKien.preventDefault();
        if (danhSach.hidden) hienKetQua();
        if (!cacKetQua.length) return;
        viTriDangChon = suKien.key === 'ArrowDown'
          ? (viTriDangChon + 1) % cacKetQua.length
          : (viTriDangChon <= 0 ? cacKetQua.length - 1 : viTriDangChon - 1);
        cacNutKetQua.forEach(function (nut, chiSo) {
          nut.classList.toggle('dang-chon', chiSo === viTriDangChon);
        });
        oTimKiem.setAttribute('aria-activedescendant', cacNutKetQua[viTriDangChon].id);
      }
      if (suKien.key === 'Enter' && !danhSach.hidden) {
        suKien.preventDefault();
        if (viTriDangChon >= 0) {
          chonDuAn(cacKetQua[viTriDangChon]);
        } else if (cacKetQua.length === 1) {
          chonDuAn(cacKetQua[0]);
        }
      }
    });
    nutXoa.addEventListener('click', function () {
      oDuAn.value = '';
      oTimKiem.value = '';
      form.submit();
    });
    document.addEventListener('click', function (suKien) {
      if (!boLoc.contains(suKien.target)) dongDanhSach();
    });
  }

  khoiTaoTimDuAn();

  function veBieuDo(khung, duLieu, loai) {
    var tienDo = loai === 'tien-do';
    var cauHinh = tienDo ? {
      khoa: 'tien_do',
      khoaChenhLech: 'tien_do_chenh_lech',
      tenTruc: 'Tiến độ (%)',
      donViTruc: '%',
      chuSoLe: 2,
      coDinh: true,
      tenDuLieu: 'Tiến độ bình quân'
    } : {
      khoa: 'giai_ngan_nam',
      khoaChenhLech: 'giai_ngan_chenh_lech',
      tenTruc: 'Giải ngân (tỷ đồng)',
      donViTruc: '',
      chuSoLe: 3,
      coDinh: false,
      tenDuLieu: 'Đã giải ngân'
    };
    var svg;

    function ve() {
      var chieuRong = Math.max(khung.clientWidth, 320);
      var vungVe = {
        rong: chieuRong - le.trai - le.phai,
        cao: chieuCao - le.tren - le.duoi
      };
      var giaTriChinh = duLieu.map(function (diem) {
        return diem[cauHinh.khoa] === null ? null : Number(diem[cauHinh.khoa]);
      });
      var giaTriKeHoach = tienDo ? [] : duLieu.map(function (diem) {
        return diem.ke_hoach_von_nam === null ? null : Number(diem.ke_hoach_von_nam);
      });
      var cacGiaTri = giaTriChinh.concat(giaTriKeHoach).filter(function (giaTri) {
        return giaTri !== null && Number.isFinite(giaTri);
      });

      khung.replaceChildren();
      if (!cacGiaTri.length) {
        var thongBao = document.createElement('p');
        thongBao.className = 'tk-bieu-do-trang-thai';
        thongBao.textContent = 'Chưa có dữ liệu báo cáo trong kỳ đã chọn.';
        khung.appendChild(thongBao);
        return;
      }

      svg = taoPhanTu('svg', {
        viewBox: '0 0 ' + chieuRong + ' ' + chieuCao,
        role: 'img',
        'aria-label': tienDo
          ? 'Biểu đồ đường tiến độ dự án theo tuần, đơn vị phần trăm'
          : 'Biểu đồ đường giải ngân và kế hoạch vốn theo tuần, đơn vị tỷ đồng',
        'class': 'tk-bieu-do-svg',
        preserveAspectRatio: 'xMidYMid meet'
      });
      khung.appendChild(svg);

      var toiDa = taoTrucY(svg, chieuRong, vungVe, {
        coDinh: cauHinh.coDinh,
        toiDa: Math.max(1, Math.max.apply(null, cacGiaTri) * 1.08),
        tenTruc: cauHinh.tenTruc,
        donViTruc: cauHinh.donViTruc
      });
      var xTai = function (chiSo) {
        return le.trai + (duLieu.length <= 1 ? vungVe.rong / 2 : vungVe.rong * chiSo / (duLieu.length - 1));
      };
      var yTai = function (giaTri) {
        return le.tren + vungVe.cao * (1 - giaTri / toiDa);
      };

      chiSoNhan(duLieu.length, chieuRong).forEach(function (chiSo) {
        var x = xTai(chiSo);
        svg.appendChild(taoPhanTu('line', {
          x1: x,
          x2: x,
          y1: chieuCao - le.duoi + 1,
          y2: chieuCao - le.duoi + 5,
          'class': 'tk-bieu-do-truc'
        }));
        svg.appendChild(taoPhanTu('text', {
          x: x,
          y: chieuCao - le.duoi + 21,
          'text-anchor': chiSo === 0 ? 'start' : (chiSo === duLieu.length - 1 ? 'end' : 'middle'),
          'class': 'tk-bieu-do-nhan-ky'
        }, nhanNgan(duLieu[chiSo].nhan)));
      });
      svg.appendChild(taoPhanTu('text', {
        x: le.trai + vungVe.rong / 2,
        y: chieuCao - 9,
        'text-anchor': 'middle',
        'class': 'tk-bieu-do-nhan-ten'
      }, 'Tuần / năm'));

      function veDuong(cacGiaTriDuong, laKeHoach) {
        for (var i = 1; i < cacGiaTriDuong.length; i++) {
          if (cacGiaTriDuong[i - 1] === null || cacGiaTriDuong[i] === null) continue;
          var mau = laKeHoach ? mauKeHoach : mauBienDong(
            duLieu[i][cauHinh.khoaChenhLech],
            mauMacDinh
          );
          svg.appendChild(taoPhanTu('line', {
            x1: xTai(i - 1),
            y1: yTai(cacGiaTriDuong[i - 1]),
            x2: xTai(i),
            y2: yTai(cacGiaTriDuong[i]),
            stroke: mau,
            'stroke-width': laKeHoach ? 2 : 3,
            'stroke-linecap': 'round'
          }));
          if (laKeHoach) svg.lastChild.setAttribute('stroke-dasharray', '6 5');
        }

        cacGiaTriDuong.forEach(function (giaTri, chiSo) {
          if (giaTri === null) return;
          var diem = taoPhanTu('circle', {
            cx: xTai(chiSo),
            cy: yTai(giaTri),
            r: laKeHoach ? 3 : 5,
            fill: laKeHoach ? mauKeHoach : mauBienDong(duLieu[chiSo][cauHinh.khoaChenhLech], mauMacDinh),
            stroke: '#fff',
            'stroke-width': laKeHoach ? 1 : 2,
            tabindex: '0'
          });
          var noiDungChuThich = duLieu[chiSo].nhan + '\n'
            + (laKeHoach ? 'Kế hoạch vốn năm: ' : cauHinh.tenDuLieu + ': ')
            + dinhDang(giaTri, cauHinh.chuSoLe)
            + (tienDo ? '%' : ' tỷ đồng');

          if (!laKeHoach && !tienDo) {
            var chenhLech = duLieu[chiSo].giai_ngan_chenh_lech;
            noiDungChuThich += chenhLech === null
              ? '\nChưa đủ kỳ so sánh'
              : '\n' + (chenhLech > 0 ? 'Tăng ' : (chenhLech < 0 ? 'Giảm ' : 'Không đổi '))
                + dinhDang(Math.abs(chenhLech), 3) + ' tỷ đồng · '
                + duLieu[chiSo].so_du_an_so_sanh_giai_ngan + ' dự án đối chiếu';
          } else if (!laKeHoach) {
            var thayDoi = duLieu[chiSo].tien_do_chenh_lech;
            noiDungChuThich += thayDoi === null
              ? '\nChưa đủ kỳ so sánh'
              : '\n' + (thayDoi > 0 ? 'Tăng ' : (thayDoi < 0 ? 'Giảm ' : 'Không đổi '))
                + dinhDang(Math.abs(thayDoi), 2) + ' điểm phần trăm · '
                + duLieu[chiSo].so_du_an_so_sanh_tien_do + ' dự án đối chiếu';
            noiDungChuThich += '\nCó số liệu: ' + duLieu[chiSo].so_du_an_tien_do
              + '/' + duLieu[chiSo].tong_du_an_trong_pham_vi + ' dự án';
          }

          diem.appendChild(taoPhanTu('title', {}, noiDungChuThich));
          svg.appendChild(diem);

          if (duLieu.length <= 10) {
            var viTriNhan = laKeHoach
              ? yTai(giaTri) - 12
              : (yTai(giaTri) > chieuCao - le.duoi - 18 ? yTai(giaTri) - 12 : yTai(giaTri) + 17);
            svg.appendChild(taoPhanTu('text', {
              x: xTai(chiSo),
              y: viTriNhan,
              'text-anchor': chiSo === 0 ? 'start' : (chiSo === duLieu.length - 1 ? 'end' : 'middle'),
              fill: laKeHoach ? mauKeHoach : mauBienDong(duLieu[chiSo][cauHinh.khoaChenhLech], mauMacDinh),
              'class': laKeHoach
                ? 'tk-bieu-do-gia-tri tk-bieu-do-gia-tri-ke-hoach'
                : 'tk-bieu-do-gia-tri'
            }, dinhDang(giaTri, laKeHoach ? 3 : cauHinh.chuSoLe)));
          }
        });
      }

      veDuong(giaTriChinh, false);
      if (!tienDo) veDuong(giaTriKeHoach, true);
    }

    ve();
    if (typeof ResizeObserver !== 'undefined') {
      new ResizeObserver(ve).observe(khung);
    } else {
      window.addEventListener('resize', ve);
    }
  }

  var khungDuLieu = document.querySelector('[data-tk-du-lieu]');
  var cacKhung = document.querySelectorAll('[data-tk-chart]');
  var duLieu;

  try {
    duLieu = JSON.parse(khungDuLieu.dataset.duLieu);
  } catch (loi) {
    console.error('Không thể đọc dữ liệu biểu đồ tiến độ:', loi);
    cacKhung.forEach(function (khung) {
      khung.textContent = 'Không thể hiển thị biểu đồ. Vui lòng xem bảng so sánh bên dưới.';
      khung.classList.add('tk-bieu-do-trang-thai');
    });
    return;
  }

  cacKhung.forEach(function (khung) {
    try {
      veBieuDo(khung, duLieu, khung.dataset.tkChart);
    } catch (loi) {
      console.error('Không thể hiển thị biểu đồ tiến độ:', loi);
      khung.textContent = 'Không thể hiển thị biểu đồ. Vui lòng xem bảng so sánh bên dưới.';
      khung.classList.add('tk-bieu-do-trang-thai');
    }
  });
})();
