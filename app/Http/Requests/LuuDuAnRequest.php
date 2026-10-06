<?php

namespace App\Http\Requests;

use App\Models\DuAn;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LuuDuAnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->vai_tro, ['quan_tri', 'nhap_lieu'], true);
    }

    // Tiến độ gõ kiểu Việt Nam "99,65" — đổi dấu phẩy thành dấu chấm trước khi kiểm tra số
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('tien_do'))) {
            $this->merge(['tien_do' => str_replace(',', '.', trim($this->input('tien_do')))]);
        }

        // Lớp hình học gửi thành 1 ô JSON duy nhất (hinh_hoc_json) thay vì mỗi toạ độ 1 ô ẩn: dự án
        // lớn (VD trang trại điện gió 1.251 lớp, ~17.000 toạ độ) vượt giới hạn max_input_vars của PHP
        // (5.000 ô) — PHP lặng lẽ cắt bớt, lưu form sẽ làm mất phần lớn dữ liệu bản đồ.
        if ($this->has('hinh_hoc_json')) {
            $ds = json_decode((string) $this->input('hinh_hoc_json'), true);
            $this->merge(['hinh_hoc' => is_array($ds) ? $ds : 'khong_hop_le']);
        }

        // Dòng văn bản mới thêm nhưng để trống hết (không số, ngày, loại, tệp) thì bỏ, không bắt chọn hồ sơ
        if (is_array($this->input('van_ban'))) {
            $this->merge(['van_ban' => array_filter($this->input('van_ban'), fn ($r, $i) => ! empty($r['id'])
                || filled($r['loai'] ?? null) || filled($r['so_van_ban'] ?? null) || filled($r['ngay_van_ban'] ?? null)
                || $this->hasFile("van_ban.{$i}.tep"), ARRAY_FILTER_USE_BOTH)]);
        }
    }

    public function rules(): array
    {
        return [
            'ten' => ['required', 'string', 'max:255'],
            'chu_dau_tu' => ['nullable', 'string', 'max:255'],
            'chu_dau_tu_id' => ['nullable', Rule::exists('users', 'id')->where('vai_tro', 'chu_dau_tu')],
            'loai_hinh' => ['required', Rule::exists('loai_hinh_du_an', 'ma')],
            'dia_diem' => ['nullable', 'string', 'max:255'],
            'trang_thai' => ['required', Rule::exists('trang_thai_du_an', 'ten')],

            'von_dang_ky' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'dien_tich' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'cong_suat' => ['nullable', 'string', 'max:255'],
            'cong_suat_mw' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'san_luong_dien' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'tien_do' => ['nullable', 'numeric', 'between:0,100', 'decimal:0,2'],

            'ngay_khoi_cong' => ['nullable', 'date'],
            'ngay_hoan_thanh' => ['nullable', 'date', 'after_or_equal:ngay_khoi_cong'],
            'tien_do_van_hanh' => ['nullable', 'string', 'max:255'],
            'phat_dien_tu' => ['nullable', 'date_format:Y-m'],

            'vi_do' => ['nullable', 'numeric', 'between:-90,90', 'required_with:kinh_do'],
            'kinh_do' => ['nullable', 'numeric', 'between:-180,180', 'required_with:vi_do'],

            // Văn bản của 8 hồ sơ pháp lý — mỗi hồ sơ có thể có nhiều văn bản (VD chủ trương: chấp thuận
            // lần đầu, các lần điều chỉnh...), mỗi văn bản có thể kèm 1 tệp. Cùng loại tệp với tải ở trang chi tiết.
            'van_ban' => ['nullable', 'array'],
            'van_ban.*.id' => ['nullable', 'integer'],
            'van_ban.*.ho_so' => ['required', Rule::in(array_keys(DuAn::HO_SO_PHAP_LY))],
            'van_ban.*.loai' => ['nullable', 'string', 'max:255'],
            'van_ban.*.so_van_ban' => ['nullable', 'string', 'max:100'],
            'van_ban.*.ngay_van_ban' => ['nullable', 'date'],
            'van_ban.*.tep' => ['nullable', 'file', 'max:51200', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,zip,rar'],

            // Các lớp hình học bổ sung (điểm/đường/vùng), giữ nguyên số lượng lớp đọc được
            // từ file KML/KMZ hoặc vẽ tay — không giới hạn chỉ 1 đường/1 vùng như trước.
            'hinh_hoc' => ['nullable', 'array'],
            'hinh_hoc.*.loai' => ['required', Rule::in(['diem', 'duong', 'vung'])],
            'hinh_hoc.*.ten' => ['nullable', 'string', 'max:255'],
            'hinh_hoc.*.bieu_tuong' => ['nullable', Rule::exists('bieu_tuong', 'ma')],
            'hinh_hoc.*.mau' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            // Kiểm tra từng toạ độ ngay trong 1 closure — quy tắc wildcard "toa_do.*.*" sinh ra hàng chục
            // nghìn quy tắc riêng với dự án lớn, rất chậm.
            'hinh_hoc.*.toa_do' => ['required', 'array', function ($attribute, $value, $fail) {
                $chiSo = (int) explode('.', $attribute)[1];
                $loai = $this->input("hinh_hoc.{$chiSo}.loai");
                $toiThieu = $loai === 'diem' ? 1 : ($loai === 'duong' ? 2 : 3);
                if (count($value) < $toiThieu) {
                    $fail(match ($loai) {
                        'diem' => 'Điểm phải có toạ độ.',
                        'duong' => 'Đường phải có ít nhất 2 điểm.',
                        default => 'Vùng phải có ít nhất 3 đỉnh.',
                    });

                    return;
                }
                foreach ($value as $p) {
                    if (! is_array($p) || count($p) !== 2 || ! is_numeric($p[0] ?? null) || ! is_numeric($p[1] ?? null)
                        || abs((float) $p[0]) > 90 || abs((float) $p[1]) > 180) {
                        $fail('Lớp hình học thứ '.($chiSo + 1).' có toạ độ không hợp lệ.');

                        return;
                    }
                }
            }],
            'hinh_hoc.*.nhan_diem' => ['nullable', 'array'],
            'hinh_hoc.*.nhan_diem.*' => ['nullable', 'string', 'max:255'],
            'hinh_hoc.*.hien_thi' => ['nullable', 'boolean'],

            'ghi_chu' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'ten' => 'tên dự án',
            'loai_hinh' => 'loại hình',
            'trang_thai' => 'trạng thái',
            'von_dang_ky' => 'vốn đăng ký',
            'dien_tich' => 'diện tích',
            'cong_suat_mw' => 'công suất (MW)',
            'san_luong_dien' => 'sản lượng điện',
            'tien_do' => 'tiến độ',
            'vi_do' => 'vĩ độ',
            'kinh_do' => 'kinh độ',
            'ngay_hoan_thanh' => 'ngày hoàn thành',
        ];
    }

    public function messages(): array
    {
        return [
            'hinh_hoc.array' => 'Dữ liệu lớp bản đồ gửi lên bị hỏng — tải lại trang rồi thử lại.',
            'vi_do.required_with' => 'Toạ độ phải nhập đủ cả vĩ độ và kinh độ.',
            'kinh_do.required_with' => 'Toạ độ phải nhập đủ cả vĩ độ và kinh độ.',
            'tien_do.decimal' => 'Tiến độ chỉ ghi tối đa 2 chữ số thập phân.',
            'van_ban.*.ho_so.required' => 'Chọn hồ sơ cho từng văn bản.',
            'van_ban.*.tep.max' => 'Mỗi tệp văn bản tối đa 50 MB.',
            'van_ban.*.tep.mimes' => 'Tệp văn bản chỉ nhận PDF, Word, Excel, ảnh (JPG, PNG) hoặc tệp nén (ZIP, RAR).',
        ];
    }

    /** Dữ liệu cột của bảng du_an, kèm các ô tích hồ sơ pháp lý. Không gồm hinh_hoc (bảng riêng). */
    public function duLieuDuAn(): array
    {
        $data = $this->validated();
        unset($data['hinh_hoc'], $data['van_ban']);

        foreach (array_keys(DuAn::HO_SO_PHAP_LY) as $k) {
            $data[$k] = $this->boolean($k);
        }

        $data['nguoi_cap_nhat_id'] = $this->user()->id;

        return $data;
    }

    /**
     * Danh sách lớp hình học (điểm/đường/vùng) để đồng bộ vào bảng du_an_hinh_hoc — null khi form
     * không gửi kèm dữ liệu bản đồ (không có ô hinh_hoc_json), để giữ nguyên lớp đang có thay vì xoá.
     */
    public function danhSachHinhHoc(): ?array
    {
        return $this->has('hinh_hoc_json') ? ($this->validated()['hinh_hoc'] ?? []) : null;
    }

    /** Danh sách văn bản chủ trương đầu tư (kèm file, lấy trực tiếp từ request vì file
     *  không được validated() giữ lại nguyên vẹn dạng UploadedFile). */
    public function danhSachVanBan(): array
    {
        $rows = $this->input('van_ban', []);

        foreach ($rows as $i => $r) {
            $rows[$i]['tep'] = $this->file("van_ban.{$i}.tep");
        }

        return $rows;
    }
}
