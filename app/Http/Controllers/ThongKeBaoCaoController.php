<?php

namespace App\Http\Controllers;

use App\Models\BaoCaoThang;
use App\Models\BaoCaoTuan;
use App\Models\BaoCaoTuanDuAn;
use App\Models\DuAn;
use App\Models\User;
use App\Support\KyBaoCao;
use App\Support\KyThang;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Thống kê báo cáo đã gửi theo từng chủ đầu tư — trong 1 tháng hoặc toàn bộ các tháng từ khi áp
 * dụng: số kỳ phải báo cáo, đã gửi (theo tình trạng xử lý), chưa gửi, tỷ lệ; kèm bảng theo từng tháng.
 * Tuần tính vào tháng chứa ngày thứ Năm cuối kỳ. Kỳ đang diễn ra (tuần này, tháng chưa quá hạn)
 * chỉ tính khi đã gửi, không tính là "chưa gửi".
 */
class ThongKeBaoCaoController extends Controller
{
    public function index(Request $request)
    {
        [$dsTuan, $dsThang] = $this->cacKy();
        $chon = KyThang::tuMa($request->query('thang'));
        if ($chon && ! collect($dsThang)->contains(fn (KyThang $k) => $k->bang($chon))) {
            $chon = null;
        }
        $tabTienDo = $request->routeIs('bao-cao.tien-do') || $request->query('tab') === 'tien-do';
        $tuanTrongKy = $chon ? array_values(array_filter($dsTuan, fn (KyBaoCao $k) => $this->thangCua($k)->bang($chon))) : $dsTuan;
        $thangTrongKy = $chon ? [$chon] : $dsThang;
        $dsDuAn = DuAn::query()->orderBy('ten')->get(['id', 'ten', 'trang_thai']);
        $duAnId = $request->query('du_an');
        $duAnId = is_scalar($duAnId) && filter_var($duAnId, FILTER_VALIDATE_INT) !== false
            ? (int) $duAnId
            : null;
        if ($duAnId !== null && ! $dsDuAn->contains('id', $duAnId)) {
            $duAnId = null;
        }
        $kyTruocThang = $chon && $tuanTrongKy !== [] ? [$tuanTrongKy[0]->truoc()] : [];
        $cacKyBieuDo = array_merge($kyTruocThang, $tuanTrongKy);
        $duLieuTienDo = $this->duLieuBieuDoTienDo($cacKyBieuDo, $duAnId, $dsDuAn, count($kyTruocThang));

        $tuanDaGui = BaoCaoTuan::daGui()->get(['id', 'nguoi_bao_cao_id', 'nam', 'tuan', 'tu_ngay', 'trang_thai', 'nop_luc'])
            ->groupBy('nguoi_bao_cao_id')->map(fn (Collection $ds) => $ds->keyBy(fn (BaoCaoTuan $b) => $b->ky()->ma()));
        $thangDaGui = BaoCaoThang::daGui()->get(['id', 'nguoi_bao_cao_id', 'nam', 'thang', 'trang_thai', 'nop_luc'])
            ->groupBy('nguoi_bao_cao_id')->map(fn (Collection $ds) => $ds->keyBy(fn (BaoCaoThang $b) => $b->ky()->ma()));

        $dsNguoi = User::where('vai_tro', 'chu_dau_tu')->with(['duAn' => fn ($q) => $q->orderBy('ten')])->get()
            ->filter(fn (User $u) => ($u->da_duyet && ($u->bc_tuan || $u->bc_thang) && $u->duAn->isNotEmpty())
                || $tuanDaGui->has($u->id) || $thangDaGui->has($u->id))
            ->sortBy(fn (User $u) => $u->don_vi ?: $u->name)->values();

        $hang = $dsNguoi->map(function (User $u) use ($tuanTrongKy, $thangTrongKy, $dsThang, $dsTuan, $tuanDaGui, $thangDaGui, $chon) {
            $cuaTuan = $tuanDaGui->get($u->id, collect());
            $cuaThang = $thangDaGui->get($u->id, collect());
            $bdTuan = KyBaoCao::batDauBaoCao($u->created_at);
            $bdThang = KyThang::batDauBaoCao($u->created_at);
            $tuanPhai = fn (KyBaoCao $k) => $u->bc_tuan && $u->da_duyet && ! $k->somHon($bdTuan) && ! $k->laHienTai();
            $thangPhai = fn (KyThang $k) => $u->bc_thang && $u->da_duyet && ! $k->somHon($bdThang) && (! $k->laHienTai() || $k->quaHan());

            $theoThang = [];
            if (! $chon) {
                foreach ($dsThang as $t) {
                    $tuanCuaThang = array_filter($dsTuan, fn (KyBaoCao $k) => $this->thangCua($k)->bang($t));
                    $theoThang[$t->ma()] = [
                        'tuan' => $this->dem($tuanCuaThang, $cuaTuan, $tuanPhai),
                        'thang' => $cuaThang->get($t->ma()),
                        'thang_phai' => $thangPhai($t),
                    ];
                }
            }

            return [
                'nguoi' => $u,
                'tuan' => $this->dem($tuanTrongKy, $cuaTuan, $tuanPhai),
                'thang' => $this->dem($thangTrongKy, $cuaThang, $thangPhai),
                'theo_thang' => $theoThang,
            ];
        });

        $tong = fn (string $loai) => collect(['phai', 'gui', 'da_tiep_nhan', 'da_nop', 'yeu_cau_bo_sung', 'chua'])
            ->mapWithKeys(fn ($k) => [$k => $hang->sum(fn ($h) => $h[$loai][$k])])->all();

        return view('bao-cao.thong-ke', [
            'chon' => $chon,
            'dsThang' => $dsThang,
            'soTuan' => count($tuanTrongKy),
            'soThang' => count($thangTrongKy),
            'hang' => $hang,
            'tongTuan' => $tong('tuan'),
            'tongThang' => $tong('thang'),
            'dsDuAn' => $dsDuAn,
            'duAnDuocChon' => $duAnId,
            'bieuDoTienDo' => $duLieuTienDo['bieu_do'],
            'tienDoTheoDuAn' => $duLieuTienDo['theo_du_an'],
            'tongDuAnTrongPhamVi' => $duLieuTienDo['tong_du_an'],
            'tabTienDo' => $tabTienDo,
        ]);
    }

    /**
     * Tiến độ là bình quân không trọng số của các dự án có số liệu. Mức thay đổi chỉ so sánh
     * những dự án có dữ liệu ở cả hai tuần để không nhầm biến động phạm vi báo cáo với tiến độ.
     * Nếu cùng một dự án có nhiều báo cáo trong một tuần, chỉ dùng lần nộp mới nhất.
     *
     * @return array{bieu_do: array, theo_du_an: array, tong_du_an: int}
     */
    private function duLieuBieuDoTienDo(array $cacKy, ?int $duAnId, Collection $dsDuAn, int $soKyBoQua): array
    {
        $theoTuan = collect();
        if ($cacKy !== [] && $dsDuAn->isNotEmpty()) {
            $kyDau = $cacKy[0];
            $kyCuoi = $cacKy[count($cacKy) - 1];
            $theoTuan = BaoCaoTuanDuAn::query()
                ->join('bao_cao_tuan', 'bao_cao_tuan.id', '=', 'bao_cao_tuan_du_an.bao_cao_tuan_id')
                ->whereNotNull('bao_cao_tuan.nop_luc')
                ->whereBetween('bao_cao_tuan.tu_ngay', [$kyDau->tuNgay->toDateString(), $kyCuoi->tuNgay->toDateString()])
                ->whereIn('bao_cao_tuan_du_an.du_an_id', $dsDuAn->modelKeys())
                ->when($duAnId !== null, fn ($q) => $q->where('bao_cao_tuan_du_an.du_an_id', $duAnId))
                ->orderByDesc('bao_cao_tuan.nop_luc')
                ->orderByDesc('bao_cao_tuan_du_an.id')
                ->get([
                    'bao_cao_tuan.nam',
                    'bao_cao_tuan.tuan',
                    'bao_cao_tuan_du_an.id',
                    'bao_cao_tuan_du_an.du_an_id',
                    'bao_cao_tuan_du_an.tien_do',
                    'bao_cao_tuan_du_an.giai_ngan_nam',
                    'bao_cao_tuan_du_an.ke_hoach_von_nam',
                ])
                ->groupBy(fn (BaoCaoTuanDuAn $dong) => sprintf('%d-W%02d', $dong->nam, $dong->tuan))
                ->map(fn ($cacDong) => $cacDong->unique('du_an_id')->values());
        }

        $cacDiem = collect($cacKy)->map(function (KyBaoCao $ky) use ($theoTuan): array {
            $cacDong = $theoTuan->get($ky->ma(), collect());
            $coTienDo = $cacDong->filter(fn (BaoCaoTuanDuAn $dong) => $dong->tien_do !== null);
            $coGiaiNgan = $cacDong->filter(fn (BaoCaoTuanDuAn $dong) => $dong->giai_ngan_nam !== null);
            $coKeHoach = $cacDong->filter(fn (BaoCaoTuanDuAn $dong) => $dong->ke_hoach_von_nam !== null);

            return [
                'nhan' => $ky->ten(),
                'ma_ky' => $ky->ma(),
                'nam_tai_chinh' => $ky->namTaiChinh(),
                'tien_do' => $coTienDo->isNotEmpty() ? round((float) $coTienDo->avg('tien_do'), 2) : null,
                'so_du_an_tien_do' => $coTienDo->count(),
                'so_du_an_giai_ngan' => $coGiaiNgan->count(),
                'giai_ngan_nam' => $coGiaiNgan->isNotEmpty() ? round((float) $coGiaiNgan->sum('giai_ngan_nam'), 3) : null,
                'ke_hoach_von_nam' => $coKeHoach->isNotEmpty() ? round((float) $coKeHoach->sum('ke_hoach_von_nam'), 3) : null,
            ];
        })->values();

        $bieuDo = $cacDiem->map(function (array $diem, int $chiSo) use ($cacDiem, $theoTuan): array {
            $kyTruoc = $chiSo > 0 ? $cacDiem[$chiSo - 1] : null;
            $cungNamTaiChinh = $kyTruoc && $diem['nam_tai_chinh'] === $kyTruoc['nam_tai_chinh'];
            $dongKyTruoc = $kyTruoc
                ? $theoTuan->get($kyTruoc['ma_ky'], collect())->keyBy('du_an_id')
                : collect();
            $dongKyNay = $theoTuan->get($diem['ma_ky'], collect());
            $soSanhTienDo = $dongKyNay->filter(fn (BaoCaoTuanDuAn $dong) => $dong->tien_do !== null
                && $dongKyTruoc->has($dong->du_an_id)
                && $dongKyTruoc->get($dong->du_an_id)->tien_do !== null);
            $chenhTienDo = $soSanhTienDo->isNotEmpty()
                ? round((float) $soSanhTienDo->avg(fn (BaoCaoTuanDuAn $dong) => $dong->tien_do - $dongKyTruoc->get($dong->du_an_id)->tien_do), 2)
                : null;
            $soSanhGiaiNgan = $cungNamTaiChinh
                ? $dongKyNay->filter(fn (BaoCaoTuanDuAn $dong) => $dong->giai_ngan_nam !== null
                    && $dongKyTruoc->has($dong->du_an_id)
                    && $dongKyTruoc->get($dong->du_an_id)->giai_ngan_nam !== null)
                : collect();
            $giaiNganKyTruoc = $soSanhGiaiNgan->isNotEmpty()
                ? round((float) $soSanhGiaiNgan->sum(fn (BaoCaoTuanDuAn $dong) => $dongKyTruoc->get($dong->du_an_id)->giai_ngan_nam), 3)
                : null;
            $chenhGiaiNgan = $soSanhGiaiNgan->isNotEmpty()
                ? round((float) $soSanhGiaiNgan->sum(fn (BaoCaoTuanDuAn $dong) => $dong->giai_ngan_nam - $dongKyTruoc->get($dong->du_an_id)->giai_ngan_nam), 3)
                : null;
            $ketQua = array_merge($diem, [
                'tien_do_chenh_lech' => $chenhTienDo,
                'so_du_an_so_sanh_tien_do' => $soSanhTienDo->count(),
                'giai_ngan_chenh_lech' => $chenhGiaiNgan,
                'so_du_an_so_sanh_giai_ngan' => $soSanhGiaiNgan->count(),
                'giai_ngan_ky_truoc' => $giaiNganKyTruoc,
                'giai_ngan_thay_doi_phan_tram' => $chenhGiaiNgan !== null && $giaiNganKyTruoc > 0
                    ? round($chenhGiaiNgan / $giaiNganKyTruoc * 100, 1)
                    : null,
            ]);
            unset($ketQua['ma_ky']);

            return $ketQua;
        })->all();

        $tongDuAn = $duAnId !== null ? 1 : $dsDuAn->count();
        $bieuDo = array_map(
            fn (array $diem) => array_merge($diem, ['tong_du_an_trong_pham_vi' => $tongDuAn]),
            $bieuDo,
        );

        $chiSoKyHienThiDau = $soKyBoQua;
        $duAnTheoMaKy = $theoTuan->map(fn (Collection $cacDong) => $cacDong->keyBy('du_an_id'));
        $theoDuAn = $dsDuAn
            ->when($duAnId !== null, fn (Collection $ds) => $ds->where('id', $duAnId))
            ->map(function (DuAn $duAn) use ($cacKy, $duAnTheoMaKy, $chiSoKyHienThiDau): array {
                $chiSoKyGanNhat = null;
                $dongGanNhat = null;
                $chiSoCuoi = count($cacKy) - 1;

                for ($chiSo = $chiSoCuoi; $chiSo >= $chiSoKyHienThiDau; $chiSo--) {
                    $dong = $duAnTheoMaKy->get($cacKy[$chiSo]->ma(), collect())->get($duAn->id);
                    if ($dong) {
                        $chiSoKyGanNhat = $chiSo;
                        $dongGanNhat = $dong;
                        break;
                    }
                }

                $dongKyTruoc = $chiSoKyGanNhat !== null && $chiSoKyGanNhat > 0
                    ? $duAnTheoMaKy->get($cacKy[$chiSoKyGanNhat - 1]->ma(), collect())->get($duAn->id)
                    : null;
                $cungNamTaiChinh = $chiSoKyGanNhat !== null && $dongKyTruoc
                    && $cacKy[$chiSoKyGanNhat]->namTaiChinh() === $cacKy[$chiSoKyGanNhat - 1]->namTaiChinh();

                return [
                    'id' => $duAn->id,
                    'ten' => $duAn->ten,
                    'trang_thai' => $duAn->trang_thai,
                    'ky' => $chiSoKyGanNhat !== null ? $cacKy[$chiSoKyGanNhat]->ten() : null,
                    'tien_do' => $dongGanNhat?->tien_do,
                    'tien_do_chenh_lech' => $dongGanNhat?->tien_do !== null && $dongKyTruoc?->tien_do !== null
                        ? round($dongGanNhat->tien_do - $dongKyTruoc->tien_do, 2)
                        : null,
                    'giai_ngan_nam' => $dongGanNhat?->giai_ngan_nam,
                    'giai_ngan_chenh_lech' => $cungNamTaiChinh && $dongGanNhat?->giai_ngan_nam !== null && $dongKyTruoc->giai_ngan_nam !== null
                        ? round($dongGanNhat->giai_ngan_nam - $dongKyTruoc->giai_ngan_nam, 3)
                        : null,
                    'ke_hoach_von_nam' => $dongGanNhat?->ke_hoach_von_nam,
                ];
            })
            ->sortBy(fn (array $dong) => [
                $dong['ky'] === null,
                $dong['tien_do'] === null,
                -($dong['tien_do'] ?? 0),
                mb_strtolower($dong['ten']),
            ])
            ->values()
            ->all();

        return [
            'bieu_do' => array_slice($bieuDo, $soKyBoQua),
            'theo_du_an' => $theoDuAn,
            'tong_du_an' => $tongDuAn,
        ];
    }

    /** Đếm trên danh sách kỳ: đã gửi (theo tình trạng), chưa gửi (kỳ phải báo cáo mà không có), các báo cáo đã gửi */
    private function dem(array $cacKy, Collection $daGui, callable $phaiNop): array
    {
        $kq = ['phai' => 0, 'gui' => 0, 'da_tiep_nhan' => 0, 'da_nop' => 0, 'yeu_cau_bo_sung' => 0, 'chua' => 0, 'ds' => [], 'thieu' => []];
        foreach ($cacKy as $k) {
            $b = $daGui->get($k->ma());
            if ($b) {
                $kq['gui']++;
                $kq[$b->trang_thai] = ($kq[$b->trang_thai] ?? 0) + 1;
                $kq['ds'][] = $b;
            } elseif ($phaiNop($k)) {
                $kq['chua']++;
                $kq['thieu'][] = $k;
            }
        }
        $kq['phai'] = $kq['gui'] + $kq['chua'];

        return $kq;
    }

    /** Tháng của 1 kỳ tuần = tháng chứa ngày thứ Năm cuối kỳ */
    private function thangCua(KyBaoCao $k): KyThang
    {
        return KyThang::chuaNgay($k->denNgay);
    }

    /** Tất cả các tuần và tháng từ mốc áp dụng sớm nhất (hoặc báo cáo sớm nhất) đến hiện tại */
    private function cacKy(): array
    {
        $tuanDau = KyBaoCao::batDauApDung() ?? KyBaoCao::hienTai();
        $bcTuan = BaoCaoTuan::daGui()->orderBy('tu_ngay')->first(['nam', 'tuan']);
        if ($bcTuan && $bcTuan->ky()->somHon($tuanDau)) {
            $tuanDau = $bcTuan->ky();
        }
        if ($tuanDau->laTuongLai()) {
            $tuanDau = KyBaoCao::hienTai();
        }
        $dsTuan = [];
        for ($k = $tuanDau; ! $k->laTuongLai() && count($dsTuan) < 520; $k = $k->sau()) {
            $dsTuan[] = $k;
        }

        $thangDau = KyThang::batDauApDung() ?? KyThang::hienTai();
        $bcThang = BaoCaoThang::daGui()->orderBy('nam')->orderBy('thang')->first(['nam', 'thang']);
        if ($bcThang && $bcThang->ky()->somHon($thangDau)) {
            $thangDau = $bcThang->ky();
        }
        if ($this->thangCua($dsTuan[0])->somHon($thangDau)) {
            $thangDau = $this->thangCua($dsTuan[0]);
        }
        if ($thangDau->laTuongLai()) {
            $thangDau = KyThang::hienTai();
        }
        $dsThang = [];
        for ($k = $thangDau; ! $k->laTuongLai() && count($dsThang) < 120; $k = $k->sau()) {
            $dsThang[] = $k;
        }

        return [$dsTuan, $dsThang];
    }
}
