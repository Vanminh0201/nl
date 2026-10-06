# Phần mềm quản lý dự án công nghiệp

Ứng dụng Laravel quản lý danh mục dự án công nghiệp trên nền bản đồ, có lớp hạ tầng lưới điện.

## Chức năng

- Danh mục dự án: thông tin chung, quy mô, tiến độ, 5 mốc hồ sơ pháp lý, toạ độ và ranh giới vùng.
- Bản đồ: điểm dự án tô màu theo loại hình, ranh giới cụm và khu công nghiệp, lọc theo loại hình và trạng thái.
- Lớp hạ tầng lưới điện: nạp GeoJSON, KML, KMZ; đường dây và trạm tô màu theo cấp điện áp.
- Thống kê theo loại hình, trạng thái, địa bàn, và số dự án còn thiếu từng loại hồ sơ.
- Xuất CSV đọc thẳng bằng Excel và xuất GeoJSON để mở bằng QGIS.
- Bốn mức tài khoản: quản trị, nhập liệu, chỉ xem, và chủ đầu tư (chỉ thấy dự án gán riêng cho mình).

## Yêu cầu

- PHP 8.2 trở lên, kèm extension `zip` (đọc KMZ) và `simplexml` (đọc KML)
- Composer
- MySQL 8 hoặc MariaDB 10.4 trở lên

Truy vấn tìm trạm gần nhất dùng các hàm `RADIANS`, `ASIN`, `POWER` của MySQL. SQLite chỉ chạy được
nếu bản biên dịch có bật nhóm hàm toán học.

## Cài đặt

Bộ mã này là phần riêng của ứng dụng, không kèm bộ khung Laravel. Cài theo thứ tự:

```bash
composer create-project laravel/laravel qlda
cd qlda
cp -r /duong/dan/giai-nen/{app,config,database,resources,routes,public,bootstrap} .
cp /duong/dan/giai-nen/.env.example .env
php artisan key:generate
```

Sửa thông tin kết nối cơ sở dữ liệu trong `.env`, rồi:

```bash
php artisan migrate
php artisan db:seed
php artisan serve
```

Seeder tạo hai tài khoản, mật khẩu đều là `Diennang@123`:

| Thư điện tử | Vai trò |
|---|---|
| minhqld@gmail.com | Quản trị |
| bunbap88@gmail.com | Nhập liệu |

Đổi cả hai mật khẩu ngay sau lần đăng nhập đầu tiên.

Tài khoản "chỉ xem" và "chủ đầu tư" không có sẵn trong seeder vì không cố định thư điện tử, tạo bằng tinker:

```bash
php artisan tinker --execute="App\Models\User::create(['name'=>'Tên hiển thị','email'=>'ai_do@vi_du.com','password'=>'mat-khau-tam','vai_tro'=>'xem']);"
```

Đổi `vai_tro` thành `chu_dau_tu` cho tài khoản chủ đầu tư. Tài khoản chủ đầu tư chỉ thấy dự án có gán
"Tài khoản chủ đầu tư" trùng với mình — gán ở mục Sửa dự án, chỉ quản trị và nhập liệu thấy được ô này.

Ở môi trường không phải production, seeder nạp thêm 6 dự án mẫu gắn nhãn "dữ liệu thử".
Toạ độ của chúng là ước lượng, không phải số liệu thật. Xoá trước khi nhập danh mục thực tế:

```bash
php artisan tinker --execute="App\Models\DuAn::where('ten','like','%dữ liệu thử%')->forceDelete();"
```

## Triển khai lên Hostinger

Đăng nhập SSH rồi tải mã nguồn lên, sau đó chạy:

```bash
bash trien-khai-hostinger.sh
```

Kịch bản này kiểm tra phiên bản PHP và extension, tạo bộ khung Laravel nếu chưa có, chép mã nguồn đè lên,
chạy migrate và seed, nạp bộ nhớ đệm, rồi trỏ `public_html` vào thư mục `public` của Laravel bằng liên kết
tượng trưng. Thư mục `public_html` cũ được đổi tên kèm mốc thời gian chứ không bị xoá.

Trước khi chạy, mở file và sửa ba biến ở đầu: `TEN_MIEN`, `THU_MUC_APP`, `PUBLIC_HTML`.

Tạo cơ sở dữ liệu MySQL trong hPanel ở mục Cơ sở dữ liệu, rồi điền tên cơ sở dữ liệu, tài khoản
và mật khẩu vào `.env`.

Nếu gói hosting không cho tạo liên kết tượng trưng, dùng cách thay thế: chép nội dung thư mục
`public` vào `public_html`, rồi sửa hai đường dẫn trong `public_html/index.php` trỏ về thư mục
chứa mã nguồn.

Sau mỗi lần cập nhật mã nguồn:

```bash
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

## Nạp lớp lưới điện

1. Tải dữ liệu lưới điện từ openinframap.org hoặc bản xuất KMZ của Infrageomatics, hoặc dùng file
   khảo sát riêng (GeoJSON/KML/KMZ).
2. Vào mục Lớp hạ tầng, đặt tên lớp, ghi rõ nguồn và thời điểm trích xuất, rồi tải file lên.

Trạm biến áp được nhận diện theo thẻ `power=substation` của OpenStreetMap, hoặc theo tên có chứa
"trạm biến áp", "TBA". Cấp điện áp đọc từ thẻ `voltage` (đơn vị vôn, tự quy đổi sang kV), nếu
không có thì bắt từ tên — chỉ dùng để tô màu trên bản đồ, không có cũng không sao.

Dữ liệu OpenStreetMap đủ ở cấp 220–500 kV, thưa ở 110 kV và gần như trống ở lưới trung áp. Dùng để
định vị và sàng lọc, không dùng làm số liệu trích dẫn trong văn bản. Số liệu chính thức lấy từ
Công ty Điện lực Quảng Trị hoặc EVNCPC.

## Cấu trúc mã nguồn

```
app/Models/DuAn.php                  danh mục loại hình, trạng thái, hồ sơ pháp lý; scope lọc
app/Models/LopHaTang.php             lớp dữ liệu hạ tầng đã nạp
app/Models/DoiTuongHaTang.php        từng đường dây, trạm, nhà máy trong lớp
app/Services/DocDuLieuKhongGian.php  đọc GeoJSON, KML, KMZ
app/Services/PhanTichHaTang.php      nhập lớp, truy vấn theo khung nhìn bản đồ
app/Http/Controllers/                bản đồ, dự án, lớp hạ tầng, thống kê, xuất dữ liệu, đăng nhập
resources/views/                     giao diện Blade
public/js/ban-do.js                  bản đồ Leaflet, không cần bước biên dịch
public/css/app.css                   toàn bộ định kiểu
```

Giao diện dùng Leaflet nạp từ CDN, không có bước build bằng npm hay Vite.
