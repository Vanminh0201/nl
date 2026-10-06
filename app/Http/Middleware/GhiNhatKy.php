<?php

namespace App\Http\Middleware;

use App\Models\NhatKy;
use App\Support\NhatKyHoatDong;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Ghi nhật ký hoạt động sau khi trả trang (terminate) để không làm chậm người dùng: thao tác thay đổi dữ liệu
 * (mọi request không phải GET), tải/xuất tệp và xem các trang chính. Đăng nhập/đăng xuất ghi qua sự kiện xác
 * thực (AppServiceProvider), các lời gọi API nạp dữ liệu bản đồ không ghi.
 */
class GhiNhatKy
{
    /** Tên route => mô tả hoạt động */
    private const HANH_DONG = [
        // thay đổi dữ liệu
        'du-an.store' => 'Thêm dự án', 'du-an.update' => 'Sửa dự án', 'du-an.destroy' => 'Xoá dự án',
        'du-an.vi-tri-tu-file' => 'Đọc vị trí dự án từ tệp', 'du-an.ho-so-tep.store' => 'Tải lên văn bản hồ sơ pháp lý',
        'ho-so-tep.destroy' => 'Xoá văn bản hồ sơ pháp lý', 'van-ban.destroy' => 'Xoá văn bản hồ sơ pháp lý', 'lop-ha-tang.store' => 'Nạp lớp hạ tầng', 'lop-ha-tang.update' => 'Nạp lại lớp hạ tầng',
        'lop-ha-tang.toggle' => 'Bật/tắt hiển thị lớp hạ tầng', 'lop-ha-tang.hien-thi-nhieu' => 'Bật/tắt hiển thị nhóm lớp hạ tầng',
        'lop-ha-tang.nhap-them' => 'Nhập thêm điểm vào lớp hạ tầng', 'lop-ha-tang.destroy' => 'Xoá lớp hạ tầng',
        'doi-tuong-ha-tang.store' => 'Thêm điểm lưới điện', 'doi-tuong-ha-tang.update' => 'Sửa thông tin đối tượng lưới điện',
        'doi-tuong-ha-tang.vi-tri' => 'Kéo sửa vị trí điểm lưới điện', 'doi-tuong-ha-tang.hinh-hoc' => 'Sửa hình dạng tuyến lưới điện',
        'bao-cao.luu' => 'Lưu, gửi báo cáo tuần', 'bao-cao.xu-ly' => 'Xử lý báo cáo tuần', 'bao-cao.xu-ly-hang-loat' => 'Xử lý hàng loạt báo cáo tuần',
        'bao-cao.thang.luu' => 'Lưu, gửi báo cáo tháng', 'bao-cao.thang.xu-ly' => 'Xử lý báo cáo tháng',
        'bao-cao.thang.xu-ly-hang-loat' => 'Xử lý hàng loạt báo cáo tháng', 'bao-cao.thang.phan-nhom' => 'Phân nhóm báo cáo tháng',
        'bao-cao.cai-dat.luu' => 'Lưu cài đặt báo cáo', 'bao-cao.san-luong.luu' => 'Lưu, gửi báo cáo sản lượng điện',
        'danh-muc.loai-hinh.store' => 'Thêm loại hình dự án', 'danh-muc.loai-hinh.update' => 'Sửa loại hình dự án', 'danh-muc.loai-hinh.destroy' => 'Xoá loại hình dự án',
        'danh-muc.trang-thai.store' => 'Thêm trạng thái dự án', 'danh-muc.trang-thai.update' => 'Sửa trạng thái dự án', 'danh-muc.trang-thai.destroy' => 'Xoá trạng thái dự án',
        'danh-muc.bieu-tuong.store' => 'Thêm biểu tượng', 'danh-muc.bieu-tuong.update' => 'Sửa biểu tượng', 'danh-muc.bieu-tuong.destroy' => 'Xoá biểu tượng',
        'tai-khoan.store' => 'Thêm tài khoản', 'tai-khoan.update' => 'Sửa tài khoản', 'tai-khoan.duyet' => 'Duyệt tài khoản',
        'tai-khoan.quyen-xem' => 'Phân quyền xem', 'tai-khoan.destroy' => 'Xoá tài khoản',
        'ca-nhan.cap-nhat' => 'Cập nhật thông tin cá nhân', 'ca-nhan.mat-khau' => 'Đổi mật khẩu', 'dang-ky' => 'Đăng ký tài khoản',
        // tải, xuất tệp
        'xuat.csv' => 'Xuất danh sách dự án (CSV)', 'xuat.excel' => 'Xuất danh sách dự án (Excel)', 'xuat.geojson' => 'Xuất dữ liệu bản đồ (GeoJSON)',
        'ho-so-tep.show' => 'Mở văn bản hồ sơ pháp lý', 'van-ban.tep' => 'Mở văn bản hồ sơ pháp lý', 'bao-cao.word' => 'Tải báo cáo tuần (Word)',
        'bao-cao.tong-hop-word' => 'Tải tổng hợp báo cáo tuần (Word)', 'bao-cao.tong-hop-in' => 'In tổng hợp báo cáo tuần',
        'bao-cao.thang.excel' => 'Tải báo cáo tháng (Excel)', 'bao-cao.thang.tong-hop-excel' => 'Tải tổng hợp báo cáo tháng (Excel)',
        'bao-cao.thang.tong-hop-in' => 'In tổng hợp báo cáo tháng', 'bao-cao.san-luong.excel' => 'Tải thống kê sản lượng điện (Excel)',
        // xem trang
        'ban-do' => 'Xem bản đồ', 'du-an.index' => 'Xem danh sách dự án', 'du-an.show' => 'Xem chi tiết dự án',
        'du-an.create' => 'Mở trang thêm dự án', 'du-an.edit' => 'Mở trang sửa dự án', 'thong-ke' => 'Xem thống kê',
        'lop-ha-tang.index' => 'Xem lớp hạ tầng', 'lop-ha-tang.doi-tuong' => 'Xem đối tượng của lớp hạ tầng',
        'bao-cao.index' => 'Xem báo cáo tuần', 'bao-cao.soan' => 'Mở trang lập báo cáo tuần', 'bao-cao.show' => 'Xem báo cáo tuần',
        'bao-cao.thong-ke' => 'Xem thống kê báo cáo', 'bao-cao.thang.index' => 'Xem báo cáo tháng', 'bao-cao.thang.soan' => 'Mở trang lập báo cáo tháng',
        'bao-cao.thang.show' => 'Xem báo cáo tháng', 'bao-cao.cai-dat' => 'Xem cài đặt báo cáo', 'danh-muc.index' => 'Xem danh mục',
        'tai-khoan.index' => 'Xem người dùng', 'ca-nhan' => 'Xem thông tin cá nhân', 'nhat-ky.index' => 'Xem nhật ký',
        'bao-cao.san-luong.index' => 'Xem báo cáo sản lượng điện', 'bao-cao.san-luong.soan' => 'Mở trang nhập sản lượng điện',
        'bao-cao.san-luong.thong-ke' => 'Xem thống kê sản lượng điện',
    ];

    private const TAI_XUAT = ['xuat.csv', 'xuat.excel', 'xuat.geojson', 'ho-so-tep.show', 'van-ban.tep', 'bao-cao.word', 'bao-cao.tong-hop-word',
        'bao-cao.tong-hop-in', 'bao-cao.thang.excel', 'bao-cao.thang.tong-hop-excel', 'bao-cao.thang.tong-hop-in', 'bao-cao.san-luong.excel'];

    /** Ghi qua sự kiện xác thực, không ghi lại ở đây */
    private const BO_QUA = ['dang-nhap', 'dang-xuat'];

    /** Dữ liệu gửi lên không bao giờ ghi */
    private const AN_DU_LIEU = ['_token', '_method', 'password', 'password_confirmation', 'mat_khau', 'mat_khau_moi', 'mat_khau_cu',
        'current_password', 'hinh_hoc_json'];

    public function __construct(private NhatKyHoatDong $nhatKy)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        try {
            $this->ghi($request, $response);
            // Dọn dần: lượt xem trang giữ 90 ngày, hoạt động khác 3 năm
            if (random_int(1, 300) === 1) {
                NhatKy::where('nhom', 'xem')->where('created_at', '<', now()->subDays(90))->delete();
                NhatKy::where('created_at', '<', now()->subYears(3))->delete();
            }
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function ghi(Request $request, Response $response): void
    {
        $route = $request->route();
        $ten = $route?->getName();
        $phuongThuc = $request->method();
        if (in_array($ten, self::BO_QUA, true) || in_array($phuongThuc, ['HEAD', 'OPTIONS'], true)) {
            return;
        }
        $thayDoi = $this->nhatKy->layThayDoi();
        $doc = in_array($phuongThuc, ['GET'], true);
        if (in_array($ten, self::TAI_XUAT, true)) {
            $nhom = 'tai_xuat';
        } elseif (! $doc) {
            $nhom = 'thay_doi';
        } elseif ($ten && isset(self::HANH_DONG[$ten]) && $response->getStatusCode() === 200) {
            $nhom = 'xem';
        } else {
            return;
        }
        $u = $request->user();
        if (! $u && $ten !== 'dang-ky') {
            return;
        }

        // Đối tượng: model gắn trên đường dẫn (VD /du-an/{duAn}); thêm mới thì lấy đối tượng vừa tạo
        $doiTuong = null;
        $lienKet = null;
        foreach ($route?->parameters() ?? [] as $p) {
            if ($p instanceof Model) {
                $loai = NhatKyHoatDong::MO_HINH[get_class($p)][0] ?? null;
                $doiTuong = ($loai ? $loai.': ' : '').NhatKyHoatDong::tenDoiTuong($p);
                $lienKet = NhatKyHoatDong::lienKet($p->fresh() ?? $p);
                break;
            }
        }
        if (! $doiTuong && $thayDoi) {
            $doiTuong = NhatKyHoatDong::moTaThayDoi($thayDoi);
        }

        $chiTiet = [];
        if ($thayDoi) {
            $chiTiet['thay_doi'] = $thayDoi;
        }
        $loi = null;
        if ($request->hasSession() && $request->session()->has('errors')) {
            $loi = $request->session()->get('errors')->first();
        } elseif ($response->getStatusCode() >= 400) {
            $loi = 'Mã phản hồi '.$response->getStatusCode();
        }
        if ($loi) {
            $chiTiet['loi'] = mb_substr((string) $loi, 0, 500);
        }
        // Dữ liệu gửi lên: chỉ ghi khi không có thay đổi model nào để đối chiếu (VD bật/tắt hàng loạt) hoặc bị lỗi
        if ($nhom === 'thay_doi' && (! $thayDoi || $loi)) {
            $duLieu = $this->tomTatDuLieu($request);
            if ($duLieu) {
                $chiTiet['du_lieu'] = $duLieu;
            }
        }
        if ($ten === 'dang-ky') {
            $doiTuong = 'Tài khoản: '.mb_substr((string) $request->input('email'), 0, 200);
        }

        $this->nhatKy->ghi([
            'user_id' => $u?->id, 'ten_nguoi_dung' => $u?->name ?? mb_substr((string) $request->input('name'), 0, 200), 'vai_tro' => $u?->vai_tro,
            'nhom' => $nhom, 'hanh_dong' => (self::HANH_DONG[$ten] ?? $phuongThuc.' '.$request->path()).($loi ? ' (không thành công)' : ''),
            'doi_tuong' => $doiTuong ? mb_substr($doiTuong, 0, 500) : null, 'lien_ket' => $lienKet,
            'chi_tiet' => $chiTiet ?: null, 'phuong_thuc' => $phuongThuc,
            'duong_dan' => mb_substr('/'.ltrim($request->path(), '/').($doc && $request->getQueryString() ? '?'.$request->getQueryString() : ''), 0, 500),
            'ma_phan_hoi' => $response->getStatusCode(), 'ip' => $request->ip(), 'trinh_duyet' => mb_substr((string) $request->userAgent(), 0, 255),
        ]);
    }

    /** Dữ liệu form đã bỏ mật khẩu, token, chuỗi quá dài; tệp tải lên chỉ ghi tên */
    private function tomTatDuLieu(Request $request): array
    {
        $kq = [];
        foreach ($request->except(self::AN_DU_LIEU) as $khoa => $v) {
            if (count($kq) >= 30) {
                $kq['…'] = 'còn nữa';
                break;
            }
            $kq[$khoa] = $this->rutGon($v);
        }
        foreach ($request->allFiles() as $khoa => $tep) {
            $ds = is_array($tep) ? $tep : [$tep];
            $kq[$khoa] = implode(', ', array_map(fn ($t) => $t instanceof UploadedFile ? $t->getClientOriginalName() : '(tệp)', array_filter($ds, fn ($t) => $t instanceof UploadedFile)));
        }

        return $kq;
    }

    private function rutGon(mixed $v): mixed
    {
        if (is_array($v)) {
            if (count($v) > 20) {
                return '('.count($v).' giá trị)';
            }

            return array_map(fn ($x) => $this->rutGon($x), $v);
        }
        if (is_string($v) && mb_strlen($v) > 200) {
            return mb_substr($v, 0, 200).'…';
        }

        return $v;
    }
}
