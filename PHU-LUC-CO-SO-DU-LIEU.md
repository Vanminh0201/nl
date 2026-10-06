# Phụ lục: cấu trúc cơ sở dữ liệu

## Bảng `du_an`

| Cột | Kiểu | Ý nghĩa |
|---|---|---|
| ten | string | Tên dự án |
| chu_dau_tu | string | Chủ đầu tư, tên hiển thị tự do |
| chu_dau_tu_id | fk users, nullable | Tài khoản vai_tro=chu_dau_tu được xem riêng dự án này (xem ghi chú bên dưới) |
| loai_hinh | string(30) | Mã loại hình, khoá ngoại theo `loai_hinh_du_an.ma` |
| dia_diem | string | Xã, phường |
| trang_thai | string(60) | Tên trạng thái, khoá ngoại theo `trang_thai_du_an.ten` |
| von_dang_ky | decimal(15,2) | Tỷ đồng |
| dien_tich | decimal(12,2) | Ha |
| cong_suat | string | Ghi tự do, VD "50 MW", "20.000 tấn/năm" |
| tien_do | tinyint | 0–100 |
| ngay_khoi_cong, ngay_hoan_thanh | date | |
| so_van_ban, ngay_van_ban | string, date | Văn bản chấp thuận chủ trương |
| pl_chu_truong, pl_dat_dai, pl_moi_truong, pl_pccc, pl_xay_dung | boolean | Mốc hồ sơ pháp lý |
| vi_do, kinh_do | decimal(10,7) | WGS84, toạ độ đại diện, dùng để đặt điểm trên bản đồ. Xem thêm bảng `du_an_hinh_hoc` bên dưới cho các lớp điểm/đường/vùng chi tiết |
| ghi_chu | text | |
| nguoi_cap_nhat_id | fk users | |
| deleted_at | timestamp | Xoá mềm, bản ghi vẫn còn trong cơ sở dữ liệu |

## Vai trò tài khoản và `chu_dau_tu_id`

`users.vai_tro` là một trong `quan_tri`, `nhap_lieu`, `xem`, `chu_dau_tu` (xem `User::VAI_TRO`).

Tài khoản vai trò `chu_dau_tu` bị áp một **global scope** trên model `DuAn`
(`DuAn::booted()`): mọi truy vấn `DuAn::query()` tự động thêm điều kiện
`where('chu_dau_tu_id', auth()->id())` khi người đăng nhập có vai trò này — áp dụng cho danh
sách, chi tiết (route model binding), bản đồ, và xuất CSV/GeoJSON vì tất cả đều đi qua Eloquent,
không cần sửa từng nơi. Vào thẳng URL dự án của người khác sẽ nhận 404 (bị lọc khỏi truy vấn),
không phải 403. Trang Thống kê và Lớp hạ tầng chặn hẳn ở tầng route vì đó là số liệu gộp của
toàn bộ danh mục, không có ý nghĩa lọc theo một dự án.

## Bảng `loai_hinh_du_an` và `trang_thai_du_an`

Danh mục loại hình và trạng thái dự án, quản lý tại trang **Danh mục** (chỉ tài khoản quản trị). Trước đây
là hằng số cứng trong `DuAn::LOAI_HINH`/`DuAn::TRANG_THAI`, nay CRUD được qua giao diện.

- `loai_hinh_du_an.ma`: mã ổn định, không đổi được sau khi tạo vì đây là giá trị lưu trong `du_an.loai_hinh`.
- `loai_hinh_du_an.bieu_tuong`: khoá tới `bieu_tuong.ma` (xem bảng `bieu_tuong` bên dưới) — icon vẽ điểm
  dự án trên bản đồ. Đổi ở trang Danh mục áp dụng ngay cho mọi dự án cùng loại hình, không cần sửa từng
  dự án.
- `trang_thai_du_an.ten`: chính là giá trị lưu trong `du_an.trang_thai`. Đổi tên ở đây sẽ tự động cập nhật
  (transaction) cho mọi dự án đang dùng trạng thái đó.
- `trang_thai_du_an.nhom` (`binh_thuong`, `hoan_thanh`, `cham_tre`): dùng để tính 2 ô đếm "đã hoạt động" và
  "chậm tiến độ" trên trang Thống kê mà không phụ thuộc vào việc so khớp nguyên văn tên trạng thái.
- Không cho xoá khi còn dự án (kể cả đã xoá mềm) đang dùng giá trị đó.

## Bảng `bieu_tuong`

Danh mục biểu tượng, CRUD tại trang **Danh mục** (chỉ quản trị) — dùng chung cho icon loại hình dự án
(`loai_hinh_du_an.bieu_tuong`) và icon từng điểm trong lớp hình học (`du_an_hinh_hoc.bieu_tuong`).

- `ma`: mã ổn định, không đổi được sau khi tạo (giống cơ chế `loai_hinh_du_an.ma`).
- `ten`: tên hiển thị trong các ô chọn biểu tượng, đổi được tự do.
- `hinh`: hình dự phòng, chọn 1 trong số hình vẽ sẵn định nghĩa cứng trong code (`BieuTuong::HINH_CO_SAN`
  và đối tượng `HINH` trong `public/js/bieu-tuong.js`) — chỉ dùng khi **chưa tải file icon riêng** (cột
  `tep` bên dưới rỗng). Nhiều biểu tượng có thể dùng chung 1 hình dự phòng.
- `tep`: đường dẫn file SVG/PNG tự tải lên (tối đa 300 KB), lưu trong `public/bieu-tuong-tai-len/` (thư
  mục thường, không qua ổ đĩa `storage` vì `php artisan storage:link` không chạy được trên host này do
  `exec`/`proc_open` bị khoá). **Có file này thì ưu tiên hiển thị file, bỏ qua `hinh`.** Hiển thị qua CSS
  `mask-image`, không bao giờ nhúng thẳng nội dung SVG vào trang — an toàn dù không lọc nội dung file vì
  trình duyệt không chạy script bên trong SVG khi dùng làm mask/ảnh.
  - **Quan trọng khi deploy lại/đổi host:** docroot flatten kiểu này không map thẳng `public/` ra web —
    chỉ những thư mục có **symlink thủ công ở gốc app** (kiểu `ln -s public/css css`, đã có sẵn cho
    `css`, `js`) mới truy cập được qua URL. Thư mục `bieu-tuong-tai-len` cũng cần 1 symlink như vậy
    (`ln -s public/bieu-tuong-tai-len bieu-tuong-tai-len` tại gốc app) — nếu thiếu, file tải lên vẫn lưu
    đúng chỗ và lưu đúng đường dẫn trong CSDL, nhưng khi mở URL sẽ báo 404.
- Không cho xoá khi còn loại hình dự án hoặc điểm dự án nào đang dùng (`BieuTuong::soDangDung()`) — khi
  xoá cũng tự xoá file `tep` trên đĩa nếu có.
- Trang nào cần vẽ icon phải gán `window.DS_BIEU_TUONG = @json(...)` (mảng {ma, ten, hinh, tep_url})
  trước khi nạp `bieu-tuong.js`, vì thư viện JS đọc danh sách này thay vì tự chứa danh sách cứng.

## Bảng `du_an_hinh_hoc`

Các lớp hình học chi tiết của một dự án — điểm, đường (tuyến hở, VD đường dây đấu nối) hoặc vùng
(khép kín, VD ranh giới cụm/khu công nghiệp). Một dự án có thể có nhiều lớp cùng lúc (VD nhiều vị
trí tua-bin cộng với 1 tuyến đường dây), khác với `du_an.vi_do`/`kinh_do` là toạ độ đại diện duy nhất.

Quản lý tại form thêm/sửa dự án: vẽ tay từng lớp, hoặc nhập từ file KML/KMZ/GeoJSON — nhập file
sẽ giữ nguyên toàn bộ điểm/đường/vùng đọc được, cộng thêm vào danh sách lớp đang có (không ghi đè).

| Cột | Kiểu | Ý nghĩa |
|---|---|---|
| du_an_id | fk du_an | Xoá cứng dự án sẽ xoá theo (`cascadeOnDelete`) |
| loai | string(10) | `diem`, `duong` hoặc `vung` |
| bieu_tuong | string(20), nullable | Chỉ có ý nghĩa khi `loai='diem'`; khoá tới `bieu_tuong.ma`, mặc định `diem_moc` nếu để trống. Dùng chung cho mọi điểm trong nhóm nếu là nhóm nhiều điểm |
| ten | string, nullable | Lấy từ thẻ `<name>` của Placemark khi nhập file 1 điểm/đường/vùng đơn lẻ; với nhóm nhiều điểm thì đây là tên chung của cả nhóm (thường để trống, xem `nhan_diem`) |
| toa_do | json | Mảng `[[lat, lng], ...]`. `duong`/`vung` là các đỉnh của 1 tuyến/1 vùng. `diem` có thể có **nhiều hơn 1** phần tử khi nhiều điểm rời trong cùng 1 file được gộp chung 1 lớp (VD nhiều cột/mốc dọc tuyến) — xem ghi chú bên dưới. Không lặp lại đỉnh đầu ở cuối cho `vung`, vòng khép được tự thêm khi vẽ ra bản đồ |
| nhan_diem | json, nullable | Chỉ dùng khi `loai='diem'` và `toa_do` có nhiều hơn 1 phần tử: mảng tên riêng từng điểm, cùng thứ tự với `toa_do` (lấy từ `<name>` của từng Placemark khi nhập file). Điểm đơn lẻ (`toa_do` có 1 phần tử) dùng cột `ten` ở trên, không cần cột này |
| hien_thi | boolean | Tắt để ẩn khỏi bản đồ mà không cần xoá — áp dụng cho **cả nhóm điểm cùng lúc** nếu là nhóm nhiều điểm, không cần ẩn từng điểm |
| thu_tu | smallint | Thứ tự hiển thị trong danh sách |

**Vì sao gộp nhiều điểm vào 1 lớp:** file KML khảo sát/GPS thường có hàng trăm Placemark kiểu điểm
(cột, mốc...) tách rời nhau. Nếu tạo riêng 1 dòng `du_an_hinh_hoc` cho mỗi điểm thì không thể
ẩn/hiện cả nhóm bằng 1 thao tác, và bảng "lớp hình học" trên form sẽ dài hàng trăm dòng. Vì vậy khi
nhập file, mọi Placemark kiểu điểm trong cùng 1 lần nhập được gộp vào **1 dòng duy nhất**
(`loai='diem'`, `toa_do` chứa toàn bộ toạ độ, `nhan_diem` giữ tên riêng từng điểm) — bật/tắt `hien_thi`
một lần là ẩn/hiện toàn bộ nhóm. Trên bản đồ, nhóm này được vẽ thành GeoJSON `MultiPoint` (thay vì
`Point`) để mỗi điểm vẫn có toạ độ và tên tooltip riêng.

Khi một dự án có ít nhất 1 lớp `duong`/`vung` đang hiện (`DuAn::co_hinh_dang`), bản đồ và trang chi
tiết sẽ **ẩn chấm điểm đại diện** (từ `vi_do`/`kinh_do`) để đỡ rối — chỉ vẽ các lớp thật. Dự án chỉ
có toạ độ đơn (không có lớp `duong`/`vung` nào) vẫn hiện chấm điểm bình thường.

## Bảng `lop_ha_tang` và `doi_tuong_ha_tang`

`lop_ha_tang` là một lần nạp file. `doi_tuong_ha_tang` là từng đối tượng trong file đó, mỗi bản ghi giữ:

- `loai`: `tram`, `duong_day`, `nha_may`, `khac`
- `dien_ap`: kV, có thể rỗng khi dữ liệu nguồn không ghi — chỉ dùng để tô màu đường dây/trạm trên
  bản đồ theo cấp điện áp, không dùng để lọc ẩn/hiện (bản đồ luôn vẽ toàn bộ lớp đang bật)
- `hinh_hoc`: geometry theo chuẩn GeoJSON
- `tam_vi_do`, `tam_kinh_do`: trọng tâm
- `min_*`, `max_*`: hộp bao, dùng để lọc theo khung nhìn bản đồ

Tắt một lớp bằng `hien_thi = false` sẽ loại lớp đó khỏi bản đồ.

## Ghi chú về hiệu năng

Truy vấn khung nhìn lọc trước bằng hộp bao qua chỉ mục `idx_ha_tang_bbox`, giới hạn mặc định
4.000 đối tượng mỗi lần và ưu tiên cấp điện áp cao khi phải cắt bớt. Nếu nạp dữ liệu toàn quốc,
nên cắt theo phạm vi tỉnh bằng QGIS trước khi nạp.
