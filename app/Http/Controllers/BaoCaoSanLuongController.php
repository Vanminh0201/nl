<?php

namespace App\Http\Controllers;

use App\Models\DuAn;
use App\Models\LoaiHinhDuAn;
use App\Models\SanLuongThang;
use App\Models\User;
use App\Services\TaoTepExcel;
use App\Support\BaoCaoSanLuong;
use App\Support\KyThang;
use App\Support\SanLuongDienUocTinh;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Báo cáo sản lượng điện tháng của các dự án nguồn điện đã vận hành: chủ đầu tư nhập, gửi sản lượng tháng
 * trước (hạn ngày N tháng sau) cho dự án được gán; Sở theo dõi tình hình gửi. Cùng một trang có thống kê
 * so sánh sản lượng theo tháng, năm, loại hình, từng dự án.
 */
class BaoCaoSanLuongController extends Controller
{
    /** Tình trạng gửi số liệu tháng của 1 dự án (trang Sở): khóa => [nhãn, lớp chip] */
    public const TINH_TRANG_GUI = [
        'cho' => ['Chờ tiếp nhận', 'cho'],
        'co' => ['Đã tiếp nhận', 'co'],
        'tra_lai' => ['Đã trả lại', 'do'],
        'dang_soan' => ['Đang soạn', 'cho'],
        'chua_gui' => ['Chưa gửi', ''],
        'chua_gan' => ['Chưa gán tài khoản', ''],
        'khong_yeu_cau' => ['Không yêu cầu báo cáo', ''],
    ];

    public function index(Request $request)
    {
        $user = $request->user();

        return $user->vai_tro === 'chu_dau_tu' ? $this->trangChuDauTu($request, $user) : $this->trangQuanLy($request);
    }

    private function trangChuDauTu(Request $request, User $user)
    {
        $dsDuAn = BaoCaoSanLuong::duAn($user->duAn()->getQuery())->orderBy('ten')->get(['id', 'ten', 'loai_hinh', 'cong_suat_mw', 'trang_thai', 'phat_dien_tu']);
        $soDangVanHanh = $dsDuAn->filter(fn ($d) => BaoCaoSanLuong::phaiBaoCao($d))->count();
        $kyCanBao = BaoCaoSanLuong::kyCanBao();
        $batDau = BaoCaoSanLuong::batDauBaoCao($user->created_at);
        $cacKy = array_reverse($kyCanBao->ganDay(12));
        $dau = end($cacKy);
        $sl = SanLuongThang::whereIn('du_an_id', $dsDuAn->pluck('id'))
            ->where(fn ($q) => $q->where('nam', '>', $dau->nam)->orWhere(fn ($q) => $q->where('nam', $dau->nam)->where('thang', '>=', $dau->thang)))
            ->get()->groupBy(fn (SanLuongThang $s) => $s->ky()->ma());

        $hang = collect($cacKy)->map(function (KyThang $k) use ($sl, $soDangVanHanh, $batDau) {
            $ds = $sl->get($k->ma(), collect());
            $gui = $ds->filter->daCo();

            return [
                'ky' => $k,
                'han' => BaoCaoSanLuong::hanNop($k),
                'gui' => $gui->count(),
                'tiep_nhan' => $ds->where('trang_thai', 'da_tiep_nhan')->count(),
                'tra_lai' => $ds->where('trang_thai', 'tra_lai')->count(),
                'nhap' => $ds->where('trang_thai', 'nhap')->count(),
                'phai' => $soDangVanHanh,
                'tong' => $gui->sum('san_luong'),
                'gui_luc' => $gui->max('gui_luc'),
                'truoc_ap_dung' => $k->somHon($batDau),
            ];
        });

        return view('bao-cao.san-luong.chu-dau-tu', [
            'dsDuAn' => $dsDuAn,
            'kyCanBao' => $kyCanBao,
            'phaiBaoCao' => $user->phaiBaoCaoSanLuong(),
            'hang' => $hang,
        ] + $this->duLieuThongKe($request));
    }

    public function soan(Request $request)
    {
        $user = $this->chiChuDauTu($request);
        $ky = KyThang::tuMa($request->query('ky')) ?? BaoCaoSanLuong::kyCanBao();
        abort_unless(BaoCaoSanLuong::nhapDuoc($ky), 404);

        $dsDuAn = BaoCaoSanLuong::duAn($user->duAn()->getQuery())->orderBy('ten')->get(['id', 'ten', 'loai_hinh', 'cong_suat_mw', 'trang_thai', 'phat_dien_tu']);
        if ($dsDuAn->isEmpty()) {
            return redirect()->route('bao-cao.san-luong.index')
                ->with('thong_bao', 'Tài khoản chưa được gán dự án nguồn điện đã vận hành — liên hệ Sở Công Thương để được gán dự án.');
        }

        $hang = $this->hangNhap($dsDuAn, $ky);

        return view('bao-cao.san-luong.soan', [
            'ky' => $ky,
            'hang' => $hang,
            'coSua' => $hang->contains(fn ($h) => ! $h['dong']?->khoa()),
            'coTraLai' => $hang->contains(fn ($h) => $h['dong']?->trang_thai === 'tra_lai'),
        ]);
    }

    public function luu(Request $request)
    {
        $user = $this->chiChuDauTu($request);
        $ky = KyThang::tuMa($request->input('ky'));
        abort_unless($ky && BaoCaoSanLuong::nhapDuoc($ky), 404);
        $gui = $request->input('hanh_dong') === 'gui';

        $dsDuAn = BaoCaoSanLuong::duAn($user->duAn()->getQuery())->get(['id', 'ten', 'trang_thai', 'phat_dien_tu'])->keyBy('id');
        $giaTri = $this->docBang($request, $dsDuAn, $gui, $ky);

        $soDong = 0;
        $khoa = 0;
        DB::transaction(function () use ($giaTri, $ky, $user, $gui, &$soDong, &$khoa) {
            $cu = SanLuongThang::whereIn('du_an_id', array_keys($giaTri))->trongKy($ky)->lockForUpdate()->get()->keyBy('du_an_id');
            foreach ($giaTri as $id => [$v, $gc]) {
                $dong = $cu->get($id);
                // Đã gửi (chờ tiếp nhận) hoặc Sở đã tiếp nhận: không sửa được, chờ Sở trả lại
                if ($dong?->khoa()) {
                    $khoa++;

                    continue;
                }
                if ($v === null && $gc === null) {
                    if ($dong && $dong->nguon === 'chu_dau_tu' && $dong->trang_thai === 'nhap') {
                        $dong->delete();
                    }

                    continue;
                }
                SanLuongThang::updateOrCreate(['du_an_id' => $id, 'nam' => $ky->nam, 'thang' => $ky->thang], [
                    'san_luong' => $v, 'ghi_chu' => $gc, 'nguon' => 'chu_dau_tu', 'nguoi_cap_nhat_id' => $user->id,
                    'gui_luc' => $gui ? now() : $dong?->gui_luc,
                    'trang_thai' => $gui ? 'da_gui' : ($dong?->trang_thai ?? 'nhap'),
                ]);
                $soDong++;
            }
        });

        if ($soDong === 0 && $khoa > 0) {
            return redirect()->route('bao-cao.san-luong.soan', ['ky' => $ky->ma()])
                ->withErrors(['sl' => 'Sản lượng '.mb_strtolower($ky->ten()).' đã gửi Sở nên không sửa được. Sở trả lại thì mới sửa, gửi lại được.']);
        }

        return $gui
            ? redirect()->route('bao-cao.san-luong.index')->with('thong_bao', 'Đã gửi báo cáo sản lượng '.mb_strtolower($ky->ten())." ({$soDong} dự án) cho Sở.")
            : redirect()->route('bao-cao.san-luong.soan', ['ky' => $ky->ma()])->with('thong_bao', 'Đã lưu nháp — báo cáo chưa được gửi cho Sở.');
    }

    /** Sở tiếp nhận số liệu chủ đầu tư đã gửi, hoặc trả lại (kèm lý do) để chủ đầu tư sửa, gửi lại */
    public function xuLy(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'hanh_dong' => ['required', 'in:tiep_nhan,tra_lai'],
            'y_kien' => ['nullable', 'required_if:hanh_dong,tra_lai', 'string', 'max:2000'],
        ], [
            'ids.required' => 'Chưa chọn số liệu cần xử lý.',
            'y_kien.required_if' => 'Ghi rõ lý do trả lại để chủ đầu tư sửa.',
            'y_kien.max' => 'Lý do trả lại dài quá 2.000 ký tự.',
        ]);
        $tiepNhan = $data['hanh_dong'] === 'tiep_nhan';
        $ds = SanLuongThang::with('duAn:id,ten')->whereIn('id', $data['ids'])
            ->whereIn('trang_thai', $tiepNhan ? ['da_gui'] : SanLuongThang::CHINH_THUC)->get();
        if ($ds->isEmpty()) {
            return back()->withErrors(['xu_ly' => $tiepNhan ? 'Không còn số liệu nào đang chờ tiếp nhận.' : 'Chỉ trả lại được số liệu đã gửi hoặc đã tiếp nhận.']);
        }

        $user = $request->user();
        DB::transaction(function () use ($ds, $user, $tiepNhan, $data) {
            foreach ($ds as $s) {
                $s->update([
                    'trang_thai' => $tiepNhan ? 'da_tiep_nhan' : 'tra_lai',
                    'nguoi_xu_ly_id' => $user->id,
                    'xu_ly_luc' => now(),
                    'y_kien_xu_ly' => $tiepNhan ? $s->y_kien_xu_ly : trim($data['y_kien']),
                ]);
            }
        });

        $ten = $ds->count() === 1 ? '"'.($ds->first()->duAn?->ten ?? '').'"' : 'của '.$ds->count().' dự án';

        return back()->with('thong_bao', $tiepNhan ? "Đã tiếp nhận sản lượng {$ten}." : "Đã trả lại sản lượng {$ten} để chủ đầu tư sửa, gửi lại.");
    }

    /**
     * Trang Sở: tình hình gửi sản lượng của tháng chọn (tiếp nhận / trả lại) và thống kê cả năm của tháng đó
     * (biểu đồ, bảng từng dự án × 12 tháng) trên cùng một trang; lọc loại hình áp dụng cho cả hai phần.
     */
    private function trangQuanLy(Request $request)
    {
        $kyCanBao = BaoCaoSanLuong::kyCanBao();
        $ky = KyThang::tuMa($request->query('ky')) ?? $kyCanBao;
        if (! BaoCaoSanLuong::nhapDuoc($ky)) {
            $ky = $kyCanBao;
        }
        $thongKe = $this->duLieuThongKe($request, $ky->nam);
        $dsDuAn = BaoCaoSanLuong::duAn()->with('taiKhoanChuDauTu:id,name,don_vi,bc_san_luong')
            ->when($thongKe['loaiHinh'], fn ($q, $loaiHinh) => $q->where('loai_hinh', $loaiHinh))
            ->get(['id', 'ten', 'loai_hinh', 'cong_suat_mw', 'trang_thai', 'phat_dien_tu', 'chu_dau_tu_id']);
        // Chủ đầu tư được Sở bỏ chọn "Sản lượng điện" (Cài đặt báo cáo) không phải gửi: không tính chưa gửi / quá hạn
        $yeuCau = fn (DuAn $d) => $d->taiKhoanChuDauTu?->bc_san_luong !== false;
        $hang = $this->hangNhap($dsDuAn, $ky)->map(function ($h) use ($yeuCau) {
            $h['yeu_cau'] = $yeuCau($h['du_an']);

            return $h + ['tinh_trang' => $this->tinhTrangGui($h)];
        });
        $thuTuLh = LoaiHinhDuAn::danhSach()->keys()->flip();
        $nhom = $hang->sortBy(fn ($h) => [$thuTuLh[$h['du_an']->loai_hinh] ?? 99, mb_strtolower($h['du_an']->ten)])
            ->groupBy(fn ($h) => $h['du_an']->loai_hinh);

        // Ô chọn tháng: 24 tháng gần đây, lùi tới tháng 1 năm sớm nhất có số liệu; kèm số dự án đã gửi / phải gửi
        $dau = $kyCanBao->ganDay(24)[0];
        if ($thongKe['dsNam']->min() < $dau->nam) {
            $dau = KyThang::tuSo($thongKe['dsNam']->min(), 1);
        }
        $dsKyChon = [];
        for ($k = $dau; ! $kyCanBao->somHon($k); $k = $k->sau()) {
            $dsKyChon[] = $k;
        }
        $duAnPhaiGui = $dsDuAn->filter($yeuCau);
        $demKy = SanLuongThang::whereIn('du_an_id', $duAnPhaiGui->pluck('id'))->chinhThuc()->where('nam', '>=', $dau->nam)
            ->selectRaw('nam, thang, count(*) n')->groupBy('nam', 'thang')->get()
            ->mapWithKeys(fn ($r) => [KyThang::tuSo($r->nam, $r->thang)->ma() => (int) $r->n]);
        $soVanHanh = $duAnPhaiGui->filter(fn ($d) => BaoCaoSanLuong::phaiBaoCao($d))->count();
        $phaiThangNay = $hang->filter(fn ($h) => $h['yeu_cau'] && BaoCaoSanLuong::phaiBaoCao($h['du_an'], $ky));

        return view('bao-cao.san-luong.quan-ly', [
            'ky' => $ky,
            'kyCanBao' => $kyCanBao,
            'dsKyChon' => $dsKyChon,
            'tinhHinhKy' => collect($dsKyChon)->mapWithKeys(fn (KyThang $k) => [$k->ma() => ['gui' => $demKy[$k->ma()] ?? 0, 'phai' => $soVanHanh]])->all(),
            'nhom' => $nhom,
            'demTinhTrang' => $hang->countBy('tinh_trang')->all(),
            'dem' => [
                'phai' => $phaiThangNay->count(),
                'da_gui' => $phaiThangNay->filter(fn ($h) => $h['dong']?->daCo())->count(),
                'chua_gan' => $phaiThangNay->filter(fn ($h) => ! $h['du_an']->chu_dau_tu_id)->count(),
            ],
            'kpiThang' => $this->kpiThang($thongKe['bang'], $ky),
            'coTheXuLy' => in_array($request->user()->vai_tro, ['quan_tri', 'nhap_lieu'], true),
            'choTiepNhan' => $hang->filter(fn ($h) => $h['dong']?->trang_thai === 'da_gui')->map(fn ($h) => $h['dong']->id)->values(),
        ] + $thongKe);
    }

    /** Tình trạng gửi số liệu tháng của 1 dòng tình hình gửi — khóa trong TINH_TRANG_GUI */
    private function tinhTrangGui(array $h): string
    {
        return match (true) {
            $h['dong']?->trang_thai === 'da_gui' => 'cho',
            $h['dong']?->trang_thai === 'da_tiep_nhan' => 'co',
            $h['dong']?->trang_thai === 'tra_lai' => 'tra_lai',
            ! $h['du_an']->chu_dau_tu_id => 'chua_gan',
            ! $h['yeu_cau'] => 'khong_yeu_cau',
            $h['dong'] !== null => 'dang_soan',
            default => 'chua_gui',
        };
    }

    /**
     * Sản lượng tháng chọn (gồm số ước tính nếu có) và mức thay đổi so tháng trước, cùng kỳ năm trước. Mức thay đổi
     * chỉ tính trên số liệu chính thức của các dự án có số liệu ở cả hai tháng: tháng đang thu thập báo cáo không bị
     * giảm ảo, số ước tính không bị đem so với số thực tế.
     *
     * @param  Collection<int, array<string, mixed>>  $bang
     * @return array{tong: ?float, uoc_tinh: float, so_du_an: int, so_truoc: ?float, so_cung_ky: ?float, ky_truoc: KyThang}
     */
    private function kpiThang(Collection $bang, KyThang $ky): array
    {
        $thang = $ky->thang;
        $coSo = $bang->filter(fn ($h) => isset($h['thang'][$thang]));
        $soSanh = function (callable $layTruoc) use ($coSo, $thang): ?float {
            $cap = $coSo->map(fn ($h) => [$h['thang_chinh_thuc'][$thang] ?? null, $layTruoc($h)])
                ->filter(fn ($c) => $c[0] !== null && $c[1] !== null);
            $truoc = $cap->sum(fn ($c) => $c[1]);

            return $truoc > 0 ? ($cap->sum(fn ($c) => $c[0]) / $truoc - 1) * 100 : null;
        };

        return [
            'tong' => $coSo->isEmpty() ? null : $coSo->sum(fn ($h) => $h['thang'][$thang]),
            'uoc_tinh' => $coSo->sum(fn ($h) => isset($h['thang_chinh_thuc'][$thang]) ? 0 : $h['thang'][$thang]),
            'so_du_an' => $coSo->count(),
            'so_truoc' => $soSanh(fn ($h) => $thang > 1 ? ($h['thang_chinh_thuc'][$thang - 1] ?? null) : ($h['thang_nam_truoc'][12] ?? null)),
            'so_cung_ky' => $soSanh(fn ($h) => $h['thang_nam_truoc'][$thang] ?? null),
            'ky_truoc' => $ky->truoc(),
        ];
    }

    /** Trang thống kê cũ đã gộp vào trang Sản lượng điện: giữ đường dẫn cũ, chuyển sang đúng năm, loại hình, dự án */
    public function thongKe(Request $request)
    {
        $thamSo = array_filter($request->only(['loai_hinh', 'du_an']));
        $nam = (int) $request->query('nam');
        if ($nam && $request->user()->vai_tro === 'chu_dau_tu') {
            $thamSo['nam'] = $nam;
        } elseif ($nam && $nam < BaoCaoSanLuong::kyCanBao()->nam) {
            $thamSo['ky'] = KyThang::tuSo($nam, 12)->ma();
        }

        return redirect()->route('bao-cao.san-luong.index', $thamSo);
    }

    public function excel(Request $request, TaoTepExcel $x)
    {
        $d = $this->duLieuThongKe($request);
        $nam = $d['nam'];
        $soCot = 4 + 12 + 3;
        $s = $x->themSheet('Sản lượng '.$nam, array_merge([5, 34, 15, 10], array_fill(0, 12, 9), [11, 11, 9]), 4);
        $x->dong($s, 1, [['v' => 'SẢN LƯỢNG ĐIỆN CÁC DỰ ÁN NGUỒN ĐIỆN ĐÃ VẬN HÀNH NĂM '.$nam, 'kieu' => 'tieu_de']], 'thuong');
        $x->gop($s, 1, 1, 1, $soCot);
        $x->dong($s, 2, [['v' => 'Đơn vị: triệu kWh'.($d['thangCuoi'] ? ' · số liệu đến tháng '.$d['thangCuoi'].'/'.$nam.'; cùng kỳ tính cùng số tháng năm '.($nam - 1) : ''), 'kieu' => 'tieu_de_phu']], 'thuong');
        $x->gop($s, 2, 1, 2, $soCot);
        if ($nam === SanLuongDienUocTinh::NAM) {
            $x->dong($s, 3, [['v' => 'Các giá trị có dấu ~ là ước tính, † theo tài liệu cung cấp; các giá trị này đã tính vào tổng thống kê nhưng chưa ghi vào báo cáo tháng chính thức.', 'kieu' => 'ghi']], 'thuong');
            $x->gop($s, 3, 1, 3, $soCot);
        }
        $dau = ['STT', 'Dự án', 'Loại hình', 'Công suất (MW)'];
        for ($t = 1; $t <= 12; $t++) {
            $dau[] = 'T'.$t;
        }
        $x->dong($s, 4, array_merge($dau, ['Cả năm', 'Cùng kỳ '.($nam - 1), 'So cùng kỳ (%)']), 'dau_cot');
        $dong = 5;
        $stt = 0;
        $tr = fn (?float $kwh) => $kwh === null ? null : round($kwh / 1e6, 3);
        foreach ($d['nhomBang'] as $lh => $ds) {
            $tenLh = $d['dsLoaiHinh'][$lh]->ten ?? $lh;
            $o = [['v' => '', 'kieu' => 'nhom'], ['v' => mb_strtoupper($tenLh), 'kieu' => 'nhom'], ['v' => '', 'kieu' => 'nhom'], ['v' => round($ds->sum(fn ($h) => $h['mw']), 2), 'kieu' => 'nhom']];
            for ($t = 1; $t <= 12; $t++) {
                $tg = $ds->sum(fn ($h) => $h['thang'][$t] ?? 0);
                $o[] = ['v' => $tg ? $tr($tg) : null, 'kieu' => 'nhom'];
            }
            $o[] = ['v' => $tr($ds->sum('tong')), 'kieu' => 'nhom'];
            $o[] = ['v' => $tr($ds->sum('cung_ky')), 'kieu' => 'nhom'];
            $o[] = ['v' => '', 'kieu' => 'nhom'];
            $x->dong($s, $dong++, $o, 'nhom');
            foreach ($ds as $h) {
                $o = [++$stt, $h['ten'].($h['thu'] ? ' (phát điện thử nghiệm)' : ''), $tenLh, $h['mw'] !== null ? round($h['mw'], 2) : null];
                for ($t = 1; $t <= 12; $t++) {
                    $soThang = $h['thang'][$t] ?? null;
                    if ($soThang !== null && $nam === SanLuongDienUocTinh::NAM) {
                        $soThangUocTinh = $h['uoc_tinh']['thang'][$t] ?? null;
                        $soThangTheoTaiLieu = $h['uoc_tinh']
                            && $t === $h['uoc_tinh']['thang_gan_nhat']
                            && $h['uoc_tinh']['thang_gan_nhat_theo_tai_lieu'];
                        if ($soThangUocTinh !== null) {
                            $soThang = '~'.$tr($soThangUocTinh);
                        } elseif ($soThangTheoTaiLieu) {
                            $soThang = $tr($soThang).'†';
                        } else {
                            $soThang = $tr($soThang);
                        }
                    } else {
                        $soThang = $tr($soThang);
                    }
                    $o[] = $soThang;
                }
                $o[] = $tr($h['tong'] ?: null);
                $o[] = $tr($h['cung_ky'] ?: null);
                $o[] = $h['so_cung_ky'] !== null ? round($h['so_cung_ky'], 1) : null;
                $x->dong($s, $dong++, $o);
            }
        }
        $o = [['v' => '', 'kieu' => 'o_dam'], ['v' => 'TỔNG CỘNG', 'kieu' => 'o_dam'], ['v' => '', 'kieu' => 'o_dam'], ['v' => round($d['bang']->sum(fn ($h) => $h['mw']), 2), 'kieu' => 'o_dam']];
        for ($t = 1; $t <= 12; $t++) {
            $o[] = ['v' => $tr($d['theoThang'][$nam][$t] ?? null), 'kieu' => 'o_dam'];
        }
        $o[] = ['v' => $tr($d['kpi']['luy_ke']), 'kieu' => 'o_dam'];
        $o[] = ['v' => $tr($d['kpi']['cung_ky']), 'kieu' => 'o_dam'];
        $o[] = ['v' => $d['kpi']['so_cung_ky'] !== null ? round($d['kpi']['so_cung_ky'], 1) : null, 'kieu' => 'o_dam'];
        $x->dong($s, $dong, $o, 'o_dam');

        return $x->taiVe("San-luong-dien-{$nam}.xlsx");
    }

    /**
     * Số liệu thống kê và tệp Excel: tổng theo tháng của năm chọn và năm trước, theo loại hình,
     * bảng từng dự án × 12 tháng, chỉ số so sánh; kèm chuỗi nhiều năm của 1 dự án khi chọn xem chi tiết.
     * Số liệu ước tính/theo tài liệu cho Quảng Trạch I chỉ dùng trong thống kê, không ghi vào báo cáo tháng.
     * $namTheoKy: năm theo tháng đang xem ở trang Sở (kể cả năm chưa có số liệu); null thì lấy ?nam= hoặc năm mới nhất có số liệu.
     */
    private function duLieuThongKe(Request $request, ?int $namTheoKy = null): array
    {
        $user = $request->user();
        $dsLoaiHinh = LoaiHinhDuAn::danhSach();
        $qDuAn = BaoCaoSanLuong::duAn();
        if ($user->vai_tro === 'chu_dau_tu') {
            $qDuAn->where('chu_dau_tu_id', $user->id);
        }
        $tatCa = (clone $qDuAn)->with('taiKhoanChuDauTu:id,name,don_vi')->get(['id', 'ten', 'loai_hinh', 'cong_suat_mw', 'san_luong_dien', 'trang_thai', 'phat_dien_tu', 'chu_dau_tu_id']);
        $loaiHinh = $request->query('loai_hinh');
        $loaiHinh = $tatCa->contains('loai_hinh', $loaiHinh) ? $loaiHinh : '';
        $dsDuAn = $loaiHinh ? $tatCa->where('loai_hinh', $loaiHinh)->values() : $tatCa;
        $ids = $dsDuAn->pluck('id');

        $chinhThuc = fn () => SanLuongThang::whereIn('du_an_id', $tatCa->pluck('id'))->chinhThuc();
        $dsNam = $chinhThuc()->distinct()->orderByDesc('nam')->pluck('nam')->map(fn ($n) => (int) $n);
        $namMacDinh = $dsNam->first() ?? BaoCaoSanLuong::kyCanBao()->nam;
        $nam = $namTheoKy ?? (int) $request->query('nam', $namMacDinh);
        if ($namTheoKy === null && ! $dsNam->contains($nam)) {
            $nam = $namMacDinh;
        }
        $dsNam = $dsNam->push($nam)->push(BaoCaoSanLuong::kyCanBao()->nam)->unique()->sortDesc()->values();

        $sl = $chinhThuc()->whereIn('du_an_id', $ids)->whereIn('nam', [$nam, $nam - 1])->get(['du_an_id', 'nam', 'thang', 'san_luong']);
        $theoThang = [$nam => array_fill(1, 12, null), $nam - 1 => array_fill(1, 12, null)];
        foreach ($sl as $s) {
            $theoThang[$s->nam][$s->thang] = ($theoThang[$s->nam][$s->thang] ?? 0) + $s->san_luong;
        }
        $theoDuAn = $sl->groupBy('du_an_id');
        $duLieuBoSung = [];
        foreach ($dsDuAn as $d) {
            $namChon = $theoDuAn->get($d->id, collect())->where('nam', $nam);
            $thangChinhThuc = $namChon->mapWithKeys(fn ($s) => [$s->thang => $s->san_luong])->all();
            $uocTinh = SanLuongDienUocTinh::choDuAn($d->ten, $d->phat_dien_tu, $nam, $thangChinhThuc);
            $thangBoSung = $uocTinh['thang'] ?? [];
            if ($uocTinh && $uocTinh['thang_gan_nhat_theo_tai_lieu'] && ! isset($thangChinhThuc[$uocTinh['thang_gan_nhat']])) {
                $thangBoSung[$uocTinh['thang_gan_nhat']] = $uocTinh['san_luong_thang_gan_nhat'];
            }
            $duLieuBoSung[$d->id] = ['uoc_tinh' => $uocTinh, 'thang' => $thangBoSung];
            foreach ($thangBoSung as $thang => $sanLuong) {
                if (isset($thangChinhThuc[$thang])) {
                    continue;
                }
                $theoThang[$nam][$thang] = ($theoThang[$nam][$thang] ?? 0) + $sanLuong;
            }
        }
        $thangCuoi = (int) collect($theoThang[$nam])->filter(fn ($sanLuong) => $sanLuong !== null)->keys()->max();
        $lhCua = $dsDuAn->pluck('loai_hinh', 'id');
        $theoLoaiHinh = [];
        foreach ($sl->where('nam', $nam) as $s) {
            $theoLoaiHinh[$lhCua[$s->du_an_id]][$s->thang] = ($theoLoaiHinh[$lhCua[$s->du_an_id]][$s->thang] ?? 0) + $s->san_luong;
        }
        foreach ($duLieuBoSung as $id => $boSung) {
            $loaiHinhDuAn = $lhCua[$id];
            foreach ($boSung['thang'] as $thang => $sanLuong) {
                $theoLoaiHinh[$loaiHinhDuAn][$thang] = ($theoLoaiHinh[$loaiHinhDuAn][$thang] ?? 0) + $sanLuong;
            }
        }
        $uocTinhTheoThang = array_fill(1, 12, null);
        foreach ($duLieuBoSung as $boSung) {
            foreach ($boSung['thang'] as $thang => $sanLuong) {
                $uocTinhTheoThang[$thang] = ($uocTinhTheoThang[$thang] ?? 0) + $sanLuong;
            }
        }
        $luyKe = collect($theoThang[$nam])->sum();
        $cungKy = $sl->where('nam', $nam - 1)->where('thang', '<=', $thangCuoi)->sum('san_luong');
        $bang = $dsDuAn->map(function (DuAn $d) use ($theoDuAn, $duLieuBoSung, $nam, $thangCuoi) {
            $ds = $theoDuAn->get($d->id, collect());
            $nay = $ds->where('nam', $nam);
            $thangChinhThuc = $nay->mapWithKeys(fn ($s) => [$s->thang => $s->san_luong])->all();
            $boSung = $duLieuBoSung[$d->id];
            $thang = array_replace($boSung['thang'], $thangChinhThuc);
            $tong = array_sum($thang);
            $ck = $ds->where('nam', $nam - 1)->where('thang', '<=', $thangCuoi)->sum('san_luong');

            return [
                'id' => $d->id, 'ten' => $d->ten, 'loai_hinh' => $d->loai_hinh, 'mw' => $d->cong_suat_mw, 'thu' => BaoCaoSanLuong::phatDienThu($d),
                'thang' => $thang, 'thang_chinh_thuc' => $thangChinhThuc, 'uoc_tinh' => $boSung['uoc_tinh'], 'so_thang' => count($thang), 'tong' => $tong, 'cung_ky' => $ck,
                'chu_dau_tu' => $d->taiKhoanChuDauTu?->don_vi ?: $d->taiKhoanChuDauTu?->name,
                'thang_nam_truoc' => $ds->where('nam', $nam - 1)->mapWithKeys(fn ($s) => [$s->thang => $s->san_luong])->all(),
                'so_cung_ky' => $ck > 0 && $tong > 0 ? ($tong / $ck - 1) * 100 : null,
                'gio' => $d->cong_suat_mw > 0 && $tong > 0 ? $tong / ($d->cong_suat_mw * 1000) : null,
                'thiet_ke' => $d->san_luong_dien > 0 && $tong > 0 ? $tong / $d->san_luong_dien * 100 : null,
            ];
        });
        $thuTuLh = $dsLoaiHinh->keys()->flip();
        $nhomBang = $bang->sortBy(fn ($h) => [$thuTuLh[$h['loai_hinh']] ?? 99, -$h['tong'], mb_strtolower($h['ten'])])->groupBy('loai_hinh');

        // Chi tiết 1 dự án: sản lượng từng tháng của mọi năm có số liệu
        $chiTiet = null;
        if ($idCt = (int) $request->query('du_an')) {
            $duAnCt = $tatCa->firstWhere('id', $idCt);
            if ($duAnCt) {
                $nhieuNam = [];
                foreach ($chinhThuc()->where('du_an_id', $idCt)->orderBy('nam')->get(['nam', 'thang', 'san_luong']) as $s) {
                    $nhieuNam[$s->nam] ??= array_fill(1, 12, null);
                    $nhieuNam[$s->nam][$s->thang] = $s->san_luong;
                }
                krsort($nhieuNam);
                $uocTinh = SanLuongDienUocTinh::choDuAn(
                    $duAnCt->ten,
                    $duAnCt->phat_dien_tu,
                    SanLuongDienUocTinh::NAM,
                    $nhieuNam[SanLuongDienUocTinh::NAM] ?? [],
                );
                $thangBoSung = $uocTinh['thang'] ?? [];
                if ($uocTinh && $uocTinh['thang_gan_nhat_theo_tai_lieu'] && ! isset($nhieuNam[SanLuongDienUocTinh::NAM][$uocTinh['thang_gan_nhat']])) {
                    $thangBoSung[$uocTinh['thang_gan_nhat']] = $uocTinh['san_luong_thang_gan_nhat'];
                }
                $chiTiet = ['du_an' => $duAnCt, 'nam' => $nhieuNam, 'uoc_tinh' => $uocTinh, 'thang_bo_sung' => $thangBoSung];
            }
        }

        return [
            'nam' => $nam,
            'dsNam' => $dsNam,
            'loaiHinh' => $loaiHinh,
            'dsLoaiHinh' => $dsLoaiHinh,
            'loaiHinhCo' => $tatCa->pluck('loai_hinh')->unique()->sortBy(fn ($lh) => $thuTuLh[$lh] ?? 99)->values(),
            'thangCuoi' => $thangCuoi,
            'theoThang' => $theoThang,
            'uocTinhTheoThang' => $uocTinhTheoThang,
            'theoLoaiHinh' => $theoLoaiHinh,
            'kpi' => [
                'luy_ke' => $luyKe,
                'cung_ky' => $cungKy,
                'so_cung_ky' => $cungKy > 0 ? ($luyKe / $cungKy - 1) * 100 : null,
                'thang_gan_nhat' => $thangCuoi ? collect($bang)->sum(fn ($h) => $h['thang'][$thangCuoi] ?? 0) : 0,
                'du_an_thang_gan_nhat' => $thangCuoi ? collect($bang)->filter(fn ($h) => isset($h['thang'][$thangCuoi]))->count() : 0,
                'so_du_an' => $dsDuAn->filter(fn ($d) => BaoCaoSanLuong::phaiBaoCao($d))->count(),
                'cong_suat' => $dsDuAn->filter(fn ($d) => BaoCaoSanLuong::phaiBaoCao($d))->sum('cong_suat_mw'),
            ],
            'bang' => $bang,
            'nhomBang' => $nhomBang,
            'chiTiet' => $chiTiet,
        ];
    }

    /** Dòng nhập liệu cho 1 tháng: số liệu đang có, tháng trước và cùng tháng năm trước để đối chiếu */
    private function hangNhap(Collection $dsDuAn, KyThang $ky): Collection
    {
        $truoc = $ky->truoc();
        $ids = $dsDuAn->pluck('id');
        $sl = SanLuongThang::with('nguoiCapNhat:id,name', 'nguoiXuLy:id,name')->whereIn('du_an_id', $ids)
            ->where(fn ($q) => $q->where(fn ($q) => $q->where('nam', $ky->nam)->where('thang', $ky->thang))
                ->orWhere(fn ($q) => $q->where('nam', $truoc->nam)->where('thang', $truoc->thang))
                ->orWhere(fn ($q) => $q->where('nam', $ky->nam - 1)->where('thang', $ky->thang)))
            ->get();
        $lay = fn (int $id, int $nam, int $thang) => $sl->first(fn ($s) => (int) $s->du_an_id === $id && (int) $s->nam === $nam && (int) $s->thang === $thang);

        return $dsDuAn->map(fn (DuAn $d) => [
            'du_an' => $d,
            'dong' => $lay($d->id, $ky->nam, $ky->thang),
            'truoc' => $lay($d->id, $truoc->nam, $truoc->thang),
            'cung_ky' => $lay($d->id, $ky->nam - 1, $ky->thang),
        ])->filter(fn ($h) => BaoCaoSanLuong::phaiBaoCao($h['du_an'], $ky) || $h['dong'] || $h['truoc'])->values();
    }

    /**
     * Đọc bảng nhập sl[du_an_id][san_luong|ghi_chu] → [id => [kWh|null, ghi chú|null]], chỉ dự án hợp lệ.
     * $batBuoc: khi gửi báo cáo, mỗi dự án đang vận hành phải có sản lượng (0 nếu không phát).
     */
    private function docBang(Request $request, Collection $dsDuAn, bool $batBuoc, ?KyThang $ky = null): array
    {
        $loi = [];
        $kq = [];
        foreach ((array) $request->input('sl', []) as $id => $o) {
            $id = (int) $id;
            if (! $dsDuAn->has($id) || ! is_array($o)) {
                continue;
            }
            $ten = $dsDuAn[$id]->ten;
            $v = BaoCaoSanLuong::docSo($o['san_luong'] ?? '');
            $gc = trim((string) ($o['ghi_chu'] ?? '')) ?: null;
            if ($v === false) {
                $loi["sl.$id.san_luong"] = "Sản lượng của dự án \"{$ten}\" không phải số.";

                continue;
            }
            if ($v !== null && $v > 1e13) {
                $loi["sl.$id.san_luong"] = "Sản lượng của dự án \"{$ten}\" quá lớn — kiểm tra lại đơn vị (kWh).";

                continue;
            }
            if ($gc !== null && mb_strlen($gc) > 2000) {
                $loi["sl.$id.ghi_chu"] = "Ghi chú của dự án \"{$ten}\" dài quá 2.000 ký tự.";

                continue;
            }
            if ($batBuoc && $v === null && BaoCaoSanLuong::phaiBaoCao($dsDuAn[$id], $ky)) {
                $loi["sl.$id.san_luong"] = "Chưa nhập sản lượng của dự án \"{$ten}\" (nhập 0 nếu nhà máy không phát điện trong tháng).";

                continue;
            }
            $kq[$id] = [$v, $gc];
        }
        if ($loi) {
            throw ValidationException::withMessages($loi);
        }

        return $kq;
    }

    private function chiChuDauTu(Request $request): User
    {
        $user = $request->user();
        abort_unless($user->vai_tro === 'chu_dau_tu', 403, 'Chỉ tài khoản chủ đầu tư mới lập được báo cáo sản lượng.');

        return $user;
    }
}
