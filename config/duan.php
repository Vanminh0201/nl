<?php

return [
    /* Tâm và mức phóng mặc định của bản đồ */
    'ban_do' => [
        'vi_do' => env('DUAN_BAN_DO_LAT', 17.30),
        'kinh_do' => env('DUAN_BAN_DO_LNG', 106.75),
        'muc_phong' => env('DUAN_BAN_DO_ZOOM', 8),

        // Khung toạ độ toàn tỉnh Quảng Trị (sau sáp nhập với Quảng Bình 07/2025), dùng để trang
        // Bản đồ chính tự canh vừa khít cả tỉnh khi mở trang — không phụ thuộc kích thước màn hình
        // như setView theo mức phóng cố định. [tây-nam, đông-bắc], gồm cả đảo Cồn Cỏ ở phía đông.
        // Sát địa giới thực tế (không đệm rộng) vì màn hình ngang khiến hướng đông-tây tự nới ra
        // nhiều hơn cần thiết so với hướng bắc-nam — đệm rộng sẽ làm bản đồ phóng nhỏ quá mức.
        'gioi_han' => [[16.20, 105.55], [18.10, 107.45]],

        // Loại hình dự án nguồn điện (mã trong bảng loai_hinh_du_an): dự án chưa vận hành vẽ màu
        // nhạt hơn dự án đã vận hành; điện gió đang vận hành có cánh quạt quay.
        'loai_hinh_nguon_dien' => ['thuy_dien', 'dien_gio', 'dien_mat_troi', 'nhiet_dien', 'dien_rac', 'sinh_khoi'],

        // Lớp nền mặc định, dùng cho bản đồ nhỏ ở trang thêm/sửa và chi tiết dự án
        // (không có bộ chọn lớp, chỉ trang Bản đồ chính mới có) — dùng ảnh vệ tinh để dễ đối
        // chiếu thực địa (thấy rõ tua-bin/tấm pin/đập...) hơn là kiểu bản đồ đường phố.
        'tile' => env('DUAN_BAN_DO_TILE', 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}'),
        'ghi_nguon' => env('DUAN_BAN_DO_ATTR', 'Tiles © Esri — Nguồn: Esri, Maxar, Earthstar Geographics'),

        // Các lớp nền cho bộ chọn ở trang Bản đồ chính và bản đồ chi tiết dự án (public/js/nen-ban-do.js).
        // Nền Đường phố, Địa hình của Esri vẫn vẽ địa giới cũ (còn cấp huyện, ranh tỉnh Quảng Bình) nên từ
        // 02/10/2026 chuyển sang OpenStreetMap / OpenTopoMap — đã kiểm tra hiện đúng xã, phường sau sắp xếp
        // 01/7/2025. Máy chủ ô bản đồ OpenStreetMap từng không kết nối được từ mạng người dùng, nên mỗi nền có
        // du_phong: không tải được ô nào thì tự chuyển sang nền đó.
        // max_native: mức phóng cao nhất nguồn có ảnh, phóng sâu hơn thì phóng to ảnh của mức đó.
        // wayback: ảnh vệ tinh Esri — có ô chọn ảnh lưu trữ theo thời điểm (Esri Wayback) và nhãn ngày chụp.
        'lop_nen' => [
            'pho' => [
                'ten' => 'Đường phố',
                'tile' => 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                'ghi_nguon' => '© <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">Những người đóng góp OpenStreetMap</a>',
                'max_native' => 19,
                'du_phong' => 've_tinh',
            ],
            've_tinh' => [
                'ten' => 'Vệ tinh',
                'tile' => 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
                'ghi_nguon' => 'Tiles © Esri — Nguồn: Esri, Maxar, Earthstar Geographics',
                'wayback' => true,
            ],
            've_tinh_s2' => [
                'ten' => 'Vệ tinh Sentinel-2 năm 2025 (10 m)',
                'tile' => 'https://tiles.maps.eox.at/wmts/1.0.0/s2cloudless-2025_3857/default/g/{z}/{y}/{x}.jpg',
                'ghi_nguon' => '<a href="https://s2maps.eu" target="_blank" rel="noopener">Sentinel-2 cloudless 2025</a> © EOX IT Services GmbH (dữ liệu Copernicus Sentinel 2025 đã xử lý)',
                'max_native' => 15,
                'du_phong' => 've_tinh',
            ],
            'dia_hinh' => [
                'ten' => 'Địa hình',
                'tile' => 'https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png',
                'ghi_nguon' => 'Bản đồ © <a href="https://opentopomap.org" target="_blank" rel="noopener">OpenTopoMap</a> (CC-BY-SA) · dữ liệu © OpenStreetMap, SRTM',
                'max_native' => 17,
                'du_phong' => 'pho',
            ],
        ],
    ],

    /* Báo cáo tuần của chủ đầu tư — giá trị điền sẵn khi lập báo cáo lần đầu */
    'bao_cao' => [
        'kinh_gui' => 'Sở Công Thương tỉnh Quảng Trị',
        'dia_danh' => 'Quảng Trị',
        'noi_nhan' => "- Như trên;\n- Lưu: VT.",
        // Chức vụ người ký khi chủ đầu tư để trống ô "Chức vụ người ký"
        'chuc_vu_ky' => "KT. GIÁM ĐỐC\nPHÓ GIÁM ĐỐC",
        // Tên cơ quan ở đầu bảng tổng hợp tuần do quản trị tải về
        'don_vi_tong_hop' => 'Sở Công Thương tỉnh Quảng Trị',
        // Số tuần hiện ở bảng theo dõi nộp báo cáo và danh sách chọn tuần để lập báo cáo bù
        'so_tuan_theo_doi' => 8,
        // Tuần đầu tiên bắt buộc báo cáo (tuần ISO): các tuần trước đó không tính là "không nộp"
        // và không hiện trong danh sách lập báo cáo bù — tránh cả loạt dấu ✕ ngay khi mới triển khai.
        'tuan_bat_dau' => env('DUAN_BAO_CAO_TUAN_BAT_DAU', '2026-W40'),
    ],

    /* Báo cáo tháng theo mẫu "Tình hình triển khai các dự án năng lượng trên địa bàn tỉnh" (nhóm theo xã) */
    'bao_cao_thang' => [
        // Tháng đầu tiên bắt buộc báo cáo — các tháng trước không tính là "không nộp"
        'thang_bat_dau' => env('DUAN_BAO_CAO_THANG_BAT_DAU', '2026-09'),
        // Hạn nộp mặc định (ngày trong tháng) khi quản trị chưa chỉnh ở trang Cài đặt báo cáo
        'han_nop' => 20,
        'so_thang_theo_doi' => 6,
    ],

    /* Số đối tượng hạ tầng tối đa trả về cho một khung nhìn */
    'gioi_han_ha_tang' => env('DUAN_GIOI_HAN_HA_TANG', 4000),
];
