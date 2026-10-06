<?php

namespace App\Http\Controllers;

use App\Models\BaoCaoThang;
use App\Models\BaoCaoTuan;
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
        $tuanTrongKy = $chon ? array_values(array_filter($dsTuan, fn (KyBaoCao $k) => $this->thangCua($k)->bang($chon))) : $dsTuan;
        $thangTrongKy = $chon ? [$chon] : $dsThang;

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
        ]);
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
