<?php

namespace App\Http\Controllers;

use App\Models\BaoCaoThang;
use App\Models\BaoCaoThangDuAn;
use App\Models\BaoCaoThangTongHop;
use App\Models\BaoCaoTuanDuAn;
use App\Models\DuAn;
use App\Models\User;
use App\Services\TongHopBaoCaoThang;
use App\Services\XuatExcelBaoCaoThang;
use App\Services\XuatWordBaoCaoThang;
use App\Support\KyThang;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Báo cáo tháng theo mẫu "Tình hình triển khai các dự án năng lượng trên địa bàn tỉnh": chủ đầu tư lập 1 báo cáo/tháng,
 * mỗi dự án 1 dòng với các cột của mẫu (dự kiến khởi công, hoàn thành, tình hình, khó khăn, kiến nghị xã/phường và
 * sở, ngành); Sở tiếp nhận/yêu cầu bổ sung, biên tập nội dung từng dự án rồi tải bảng tổng hợp theo xã (Word, Excel).
 */
class BaoCaoThangController extends Controller
{
    /** Các cột chữ chủ đầu tư nhập cho mỗi dự án */
    private const TRUONG = ['cong_suat', 'chu_dau_tu', 'du_kien_khoi_cong', 'du_kien_hoan_thanh', 'tinh_hinh', 'kho_khan',
        'kien_nghi_xa', 'kien_nghi_so'];

    /** Các ô so với tháng trước để đánh dấu "giống tháng trước — cần rà soát" */
    private const SO_SANH = ['du_kien_khoi_cong', 'du_kien_hoan_thanh', 'tinh_hinh', 'kho_khan', 'kien_nghi_xa', 'kien_nghi_so'];

    public function index(Request $request)
    {
        $user = $request->user();

        return $user->vai_tro === 'chu_dau_tu' ? $this->trangChuDauTu($user) : $this->trangQuanLy($request);
    }

    private function trangChuDauTu(User $user)
    {
        $kyNay = KyThang::hienTai();

        return view('bao-cao.thang.chu-dau-tu', [
            'kyNay' => $kyNay,
            'batDau' => KyThang::batDauBaoCao($user->created_at),
            'phaiBaoCao' => $user->phaiBaoCaoThang(),
            'baoCaoKyNay' => $user->baoCaoThang()->trongKy($kyNay)->first(),
            'canBoSung' => $user->baoCaoThang()->where('trang_thai', 'yeu_cau_bo_sung')->orderByDesc('nam')->orderByDesc('thang')->get(),
            'dsDuAn' => $user->duAn()->orderBy('ten')->get(['id', 'ten']),
            'dsBaoCao' => $user->baoCaoThang()->withCount('chiTiet')->orderByDesc('nam')->orderByDesc('thang')->paginate(20),
        ]);
    }

    private function trangQuanLy(Request $request)
    {
        $ky = KyThang::tuMa($request->query('ky')) ?? KyThang::hienTai();
        if ($ky->laTuongLai()) {
            $ky = KyThang::hienTai();
        }

        $dsChuDauTu = $this->chuDauTuPhaiBaoCao();
        $baoCaoKy = BaoCaoThang::with(['nguoiBaoCao', 'nguoiXuLy', 'chiTiet.duAn'])->trongKy($ky)->daGui()->get()
            ->keyBy('nguoi_bao_cao_id');
        $thieu = $baoCaoKy->keys()->diff($dsChuDauTu->pluck('id'));
        if ($thieu->isNotEmpty()) {
            $dsChuDauTu = $dsChuDauTu->concat(User::with('duAn')->whereIn('id', $thieu)->get())
                ->sortBy(fn (User $u) => $u->don_vi ?: $u->name)->values();
        }
        $phaiNop = $dsChuDauTu->filter(fn (User $u) => ! $ky->somHon(KyThang::batDauBaoCao($u->created_at)) || $baoCaoKy->has($u->id));

        $cacKy = $ky->ganDay((int) config('duan.bao_cao_thang.so_thang_theo_doi', 6));
        $theoDoi = BaoCaoThang::daGui()
            ->where(fn ($q) => $q->where('nam', '>', $cacKy[0]->nam)->orWhere(fn ($q) => $q->where('nam', $cacKy[0]->nam)->where('thang', '>=', $cacKy[0]->thang)))
            ->get(['id', 'nguoi_bao_cao_id', 'nam', 'thang', 'trang_thai'])
            ->groupBy('nguoi_bao_cao_id')
            ->map(fn (Collection $ds) => $ds->keyBy(fn (BaoCaoThang $b) => $b->ky()->ma()));

        $ganNhat = BaoCaoThang::daGui()->whereIn('nguoi_bao_cao_id', $dsChuDauTu->pluck('id'))
            ->orderByDesc('nam')->orderByDesc('thang')->get(['id', 'nguoi_bao_cao_id', 'nam', 'thang', 'trang_thai'])
            ->unique('nguoi_bao_cao_id')->keyBy('nguoi_bao_cao_id');

        // Số đơn vị đã gửi / phải gửi của từng tháng, hiện cạnh tên tháng trong ô chọn tháng
        $dsKyChon = KyThang::hienTai()->ganDay(18);
        $batDau = $dsChuDauTu->mapWithKeys(fn (User $u) => [$u->id => KyThang::batDauBaoCao($u->created_at)]);
        $dau = $dsKyChon[0];
        $guiTheoKy = BaoCaoThang::daGui()
            ->where(fn ($q) => $q->where('nam', '>', $dau->nam)->orWhere(fn ($q) => $q->where('nam', $dau->nam)->where('thang', '>=', $dau->thang)))
            ->get(['nguoi_bao_cao_id', 'nam', 'thang'])
            ->groupBy(fn (BaoCaoThang $b) => $b->ky()->ma());
        $tinhHinhKy = [];
        foreach ($dsKyChon as $k) {
            $gui = $guiTheoKy->get($k->ma(), collect())->pluck('nguoi_bao_cao_id')->unique();
            $phai = $batDau->filter(fn (KyThang $bd) => ! $k->somHon($bd))->keys()->merge($gui)->unique();
            $tinhHinhKy[$k->ma()] = ['gui' => $gui->count(), 'phai' => $phai->count()];
        }

        return view('bao-cao.thang.quan-ly', [
            'ky' => $ky,
            'dsKyChon' => $dsKyChon,
            'tinhHinhKy' => $tinhHinhKy,
            'hang' => $dsChuDauTu->map(fn (User $u) => [
                'nguoi' => $u,
                'bao_cao' => $baoCaoKy->get($u->id),
                'bat_dau' => KyThang::batDauBaoCao($u->created_at),
                'gan_nhat' => $ganNhat->get($u->id),
            ]),
            'dem' => [
                'phai_nop' => $phaiNop->count(),
                'cho' => $baoCaoKy->where('trang_thai', 'da_nop')->count(),
                'tiep_nhan' => $baoCaoKy->where('trang_thai', 'da_tiep_nhan')->count(),
                'bo_sung' => $baoCaoKy->where('trang_thai', 'yeu_cau_bo_sung')->count(),
                'chua_nop' => $phaiNop->count() - $baoCaoKy->count(),
            ],
            'bang' => app(TongHopBaoCaoThang::class)->cuaKy($ky),
            'coTheSua' => in_array($request->user()->vai_tro, ['quan_tri', 'nhap_lieu'], true),
            'choTiepNhanKyKhac' => BaoCaoThang::with('nguoiBaoCao')->where('trang_thai', 'da_nop')
                ->where(fn ($q) => $q->where('nam', '!=', $ky->nam)->orWhere('thang', '!=', $ky->thang))
                ->orderBy('nam')->orderBy('thang')->get(),
            'cacKy' => $cacKy,
            'theoDoi' => $theoDoi,
        ]);
    }

    public function soan(Request $request)
    {
        $user = $this->chiChuDauTu($request);
        $ky = KyThang::tuMa($request->query('ky')) ?? KyThang::hienTai();
        abort_if($ky->laTuongLai(), 404);

        $baoCao = $user->baoCaoThang()->trongKy($ky)->with('chiTiet')->first();
        if ($baoCao && ! $baoCao->conSuaDuoc()) {
            return redirect()->route('bao-cao.thang.show', $baoCao)
                ->with('thong_bao', 'Báo cáo '.mb_strtolower($ky->ten()).' đã được Sở tiếp nhận nên không sửa được nữa.');
        }

        $dsDuAn = $user->duAn()->orderBy('ten')->get();
        if ($dsDuAn->isEmpty()) {
            return redirect()->route('bao-cao.thang.index')
                ->with('thong_bao', 'Tài khoản chưa được gán dự án nào — liên hệ Sở Công Thương để được gán dự án trước khi lập báo cáo.');
        }

        // Như báo cáo tuần: lần đầu mở tháng mới thì chép sẵn nội dung tháng gần nhất để chỉ sửa chỗ thay đổi
        $truoc = $user->baoCaoThang()->with('chiTiet')->where(fn ($q) => $this->truocKy($q, $ky))
            ->orderByDesc('nam')->orderByDesc('thang')->first();
        $mucCu = BaoCaoThangDuAn::query()
            ->join('bao_cao_thang', 'bao_cao_thang.id', '=', 'bao_cao_thang_du_an.bao_cao_thang_id')
            ->where('bao_cao_thang.nguoi_bao_cao_id', $user->id)
            ->where(fn ($q) => $this->truocKy($q, $ky, 'bao_cao_thang.'))
            ->whereIn('bao_cao_thang_du_an.du_an_id', $dsDuAn->pluck('id'))
            ->orderByDesc('bao_cao_thang.nam')->orderByDesc('bao_cao_thang.thang')
            ->get(['bao_cao_thang_du_an.*', 'bao_cao_thang.nam as nam_ky', 'bao_cao_thang.thang as thang_ky'])
            ->unique('du_an_id')->keyBy('du_an_id');
        // Chưa có báo cáo tháng nào: lấy tình hình từ báo cáo tuần gần nhất của dự án làm nền
        $tuanCu = BaoCaoTuanDuAn::query()
            ->join('bao_cao_tuan', 'bao_cao_tuan.id', '=', 'bao_cao_tuan_du_an.bao_cao_tuan_id')
            ->where('bao_cao_tuan.nguoi_bao_cao_id', $user->id)
            ->whereIn('bao_cao_tuan_du_an.du_an_id', $dsDuAn->pluck('id'))
            ->orderByDesc('bao_cao_tuan.tu_ngay')
            ->get(['bao_cao_tuan_du_an.*'])
            ->unique('du_an_id')->keyBy('du_an_id');
        $mucHienTai = $baoCao?->chiTiet->keyBy('du_an_id') ?? collect();

        $muc = $dsDuAn->map(function (DuAn $d) use ($baoCao, $truoc, $mucHienTai, $mucCu, $tuanCu, $user) {
            $m = $mucHienTai->get($d->id);
            $cu = $mucCu->get($d->id);
            $nguon = $m ?? $cu;

            // Dòng của báo cáo gửi theo mẫu cũ được quy đổi sang các cột mới (noiDungMau)
            $gt = $nguon
                ? ['cong_suat' => $nguon->cong_suat, 'chu_dau_tu' => $nguon->chu_dau_tu] + $nguon->noiDungMau()
                : $this->giaTriTuDuAn($d, $user, $tuanCu->get($d->id)) + array_fill_keys(self::TRUONG, null);

            $co = $baoCao ? $m !== null : ($truoc === null || $cu === null || $truoc->chiTiet->contains('du_an_id', $d->id));

            $soSanh = $cu ? ['ky' => KyThang::tuSo($cu->nam_ky, $cu->thang_ky)->ten()] + array_intersect_key($cu->noiDungMau(), array_flip(self::SO_SANH)) : null;

            return ['du_an' => $d, 'co' => $co, 'gt' => $gt, 'truoc' => $soSanh];
        });

        $nguonVb = $baoCao ?? $truoc;

        return view('bao-cao.thang.soan', [
            'ky' => $ky,
            'baoCao' => $baoCao,
            'truoc' => $baoCao ? null : $truoc,
            'vanBan' => [
                'ten_don_vi' => $nguonVb?->ten_don_vi ?: ($user->don_vi ?: $user->name),
                'nguoi_lap' => $nguonVb?->nguoi_lap,
                'dien_thoai' => $nguonVb?->dien_thoai,
            ],
            'muc' => $muc,
        ]);
    }

    public function luu(Request $request)
    {
        $user = $this->chiChuDauTu($request);
        $ky = KyThang::tuMa($request->input('ky'));
        abort_if(! $ky || $ky->laTuongLai(), 404);
        $nop = $request->input('hanh_dong') === 'nop';

        $baoCao = $user->baoCaoThang()->trongKy($ky)->first()
            ?? new BaoCaoThang(['nguoi_bao_cao_id' => $user->id, 'nam' => $ky->nam, 'thang' => $ky->thang, 'trang_thai' => 'nhap']);
        abort_unless($baoCao->conSuaDuoc(), 403, 'Báo cáo đã được Sở tiếp nhận, không sửa được nữa.');

        $quyTac = [
            'ten_don_vi' => [$nop ? 'required' : 'nullable', 'string', 'max:255'],
            'nguoi_lap' => ['nullable', 'string', 'max:255'],
            'dien_thoai' => ['nullable', 'string', 'max:50'],
            'du_an' => ['nullable', 'array'],
            'du_an.*.co' => ['nullable', 'boolean'],
            'du_an.*.cong_suat' => ['nullable', 'string', 'max:255'],
        ];
        foreach (array_diff(self::TRUONG, ['cong_suat']) as $cot) {
            $quyTac["du_an.*.$cot"] = ['nullable', 'string', 'max:10000'];
        }
        $cuaToi = $user->duAn()->pluck('ten', 'id');
        $locChon = fn ($dsMuc) => collect(is_array($dsMuc) ? $dsMuc : [])
            ->filter(fn ($m, $id) => $cuaToi->has($id) && is_array($m) && ! empty($m['co']));

        $tenTruong = ['ten_don_vi' => 'tên đơn vị báo cáo', 'nguoi_lap' => 'người lập', 'dien_thoai' => 'điện thoại',
            'du_an' => 'danh sách dự án'];
        foreach (BaoCaoThangDuAn::TRUONG_CHU as $cot => $nhan) {
            $tenTruong["du_an.*.$cot"] = 'mục "'.$nhan.'"';
        }
        $kiemTra = Validator::make($request->all(), $quyTac, BaoCaoController::THONG_BAO_LOI, $tenTruong);
        if ($nop) {
            $kiemTra->after(function ($v) use ($request, $cuaToi, $locChon) {
                $chon = $locChon($request->input('du_an'));
                if ($chon->isEmpty()) {
                    $v->errors()->add('du_an', 'Chọn ít nhất 1 dự án để đưa vào báo cáo.');
                }
                foreach ($chon as $id => $m) {
                    if (trim((string) ($m['tinh_hinh'] ?? '')) === '') {
                        $v->errors()->add("du_an.$id.tinh_hinh", 'Nhập "Tình hình thực hiện" của dự án "'.$cuaToi[$id].'".');
                    }
                }
            });
        }
        $data = $kiemTra->validate();
        $chon = $locChon($data['du_an'] ?? []);

        DB::transaction(function () use ($baoCao, $data, $chon, $nop) {
            $baoCao->fill(collect($data)->only(['ten_don_vi', 'nguoi_lap', 'dien_thoai'])->all());
            if ($nop) {
                $baoCao->trang_thai = 'da_nop';
                $baoCao->nop_luc = now();
            }
            $baoCao->save();

            $baoCao->chiTiet()->whereNotIn('du_an_id', $chon->keys()->all())->delete();
            foreach ($chon as $id => $m) {
                $gt = [];
                foreach (self::TRUONG as $cot) {
                    $gt[$cot] = BaoCaoThangDuAn::chu($m[$cot] ?? null);
                }
                $baoCao->chiTiet()->updateOrCreate(['du_an_id' => $id], $gt);
            }
        });

        return $nop
            ? redirect()->route('bao-cao.thang.show', $baoCao)->with('thong_bao', 'Đã gửi báo cáo '.mb_strtolower($ky->ten()).' cho Sở.')
            : redirect()->route('bao-cao.thang.soan', ['ky' => $ky->ma()])->with('thong_bao', 'Đã lưu nháp — báo cáo chưa được gửi cho Sở.');
    }

    public function show(Request $request, BaoCaoThang $baoCao, TongHopBaoCaoThang $tongHop)
    {
        $this->kiemTraQuyenXem($request->user(), $baoCao);
        $baoCao->load(['chiTiet.duAn', 'nguoiBaoCao', 'nguoiXuLy']);

        return view('bao-cao.thang.xem', [
            'baoCao' => $baoCao,
            'ky' => $baoCao->ky(),
            'bang' => $tongHop->cuaBaoCao($baoCao),
        ]);
    }

    public function excel(Request $request, BaoCaoThang $baoCao, TongHopBaoCaoThang $tongHop, XuatExcelBaoCaoThang $xuat)
    {
        $this->kiemTraQuyenXem($request->user(), $baoCao);
        $baoCao->load(['chiTiet.duAn', 'nguoiBaoCao']);
        $ten = "Bao-cao-thang-{$baoCao->thang}-{$baoCao->nam}-".Str::slug($baoCao->ten_don_vi_hien_thi).'.xlsx';

        return $xuat->tao($baoCao->ky(), $tongHop->cuaBaoCao($baoCao), $baoCao->ten_don_vi_hien_thi)->taiVe($ten);
    }

    public function tongHopExcel(Request $request, TongHopBaoCaoThang $tongHop, XuatExcelBaoCaoThang $xuat)
    {
        $ky = KyThang::tuMa($request->query('ky')) ?? KyThang::hienTai();

        return $xuat->tao($ky, $tongHop->cuaKy($ky))->taiVe("Tong-hop-bao-cao-thang-{$ky->thang}-{$ky->nam}.xlsx");
    }

    public function tongHopWord(Request $request, TongHopBaoCaoThang $tongHop, XuatWordBaoCaoThang $xuat)
    {
        $ky = KyThang::tuMa($request->query('ky')) ?? KyThang::hienTai();

        return $xuat->tao($ky, $tongHop->cuaKy($ky))->taiVe("Tong-hop-bao-cao-thang-{$ky->thang}-{$ky->nam}.docx");
    }

    /** Trang in bảng tổng hợp tháng: tình hình gửi báo cáo của từng đơn vị + bảng theo xã + thống kê */
    public function tongHopIn(Request $request, TongHopBaoCaoThang $tongHop)
    {
        $ky = KyThang::tuMa($request->query('ky')) ?? KyThang::hienTai();
        $daGui = BaoCaoThang::with(['nguoiBaoCao'])->trongKy($ky)->daGui()->get();
        $phaiNop = $this->themNguoiDaGui($this->chuDauTuPhaiBaoCao(), $daGui)
            ->filter(fn (User $u) => ! $ky->somHon(KyThang::batDauBaoCao($u->created_at)) || $daGui->contains('nguoi_bao_cao_id', $u->id))
            ->values();

        return view('bao-cao.thang.in-tong-hop', [
            'ky' => $ky,
            'daGui' => $daGui,
            'phaiNop' => $phaiNop,
            'bang' => $tongHop->cuaKy($ky),
        ]);
    }

    /** Như trang quản lý: đơn vị đã gửi báo cáo kỳ này nhưng không còn trong danh sách phải báo cáo vẫn được liệt kê */
    private function themNguoiDaGui(Collection $dsChuDauTu, Collection $daGui): Collection
    {
        $thieu = $daGui->pluck('nguoi_bao_cao_id')->unique()->diff($dsChuDauTu->pluck('id'));

        return $thieu->isEmpty() ? $dsChuDauTu : $dsChuDauTu->concat(User::with('duAn')->whereIn('id', $thieu)->get())
            ->sortBy(fn (User $u) => $u->don_vi ?: $u->name)->values();
    }

    /**
     * Sở biên tập nội dung 1 dự án trong bảng tổng hợp tháng (ưu tiên hơn báo cáo của chủ đầu tư cùng tháng). Để trống
     * cả 6 ô thì bỏ phần biên tập của tháng này: bảng quay về nội dung chủ đầu tư báo cáo / tháng trước.
     */
    public function luuTongHop(Request $request, DuAn $duAn)
    {
        $ky = KyThang::tuMa($request->input('ky'));
        abort_unless($ky && ! $ky->laTuongLai(), 404);
        $quyTac = [];
        $tenTruong = [];
        foreach (BaoCaoThangDuAn::NOI_DUNG_MAU as $cot => $nhan) {
            $quyTac[$cot] = ['nullable', 'string', 'max:10000'];
            $tenTruong[$cot] = 'mục "'.$nhan.'"';
        }
        $data = $request->validate($quyTac, BaoCaoController::THONG_BAO_LOI, $tenTruong);
        $gt = array_map(fn ($cot) => BaoCaoThangDuAn::chu($data[$cot] ?? null), array_combine(array_keys($quyTac), array_keys($quyTac)));

        $khoa = ['nam' => $ky->nam, 'thang' => $ky->thang, 'du_an_id' => $duAn->id];
        if (array_filter($gt) === []) {
            BaoCaoThangTongHop::where($khoa)->delete();
            $thongBao = 'Đã bỏ phần Sở biên tập của dự án "'.$duAn->ten.'" — bảng tổng hợp dùng lại nội dung chủ đầu tư báo cáo.';
        } else {
            BaoCaoThangTongHop::updateOrCreate($khoa, $gt + ['nguoi_sua_id' => $request->user()->id]);
            $thongBao = 'Đã lưu nội dung dự án "'.$duAn->ten.'" trong bảng tổng hợp '.mb_strtolower($ky->ten()).'.';
        }

        return redirect()->route('bao-cao.thang.index', ['ky' => $ky->ma()])->withFragment('th-'.$duAn->id)->with('thong_bao', $thongBao);
    }

    public function xuLy(Request $request, BaoCaoThang $baoCao)
    {
        $data = $request->validate([
            'hanh_dong' => ['required', 'in:tiep_nhan,yeu_cau_bo_sung'],
            'y_kien' => ['nullable', 'required_if:hanh_dong,yeu_cau_bo_sung', 'string', 'max:2000'],
        ], ['y_kien.required_if' => 'Ghi rõ nội dung chủ đầu tư cần bổ sung.'] + BaoCaoController::THONG_BAO_LOI,
            ['hanh_dong' => 'thao tác', 'y_kien' => 'ý kiến']);

        // Cho phép tra lai (chuyen ve "yeu cau bo sung") ca khi da tiep nhan — lo tiep nhan nham
        // van sua duoc, khong phai cho chu dau tu nop bao cao moi roi moi xu ly lai duoc.
        if (! in_array($baoCao->trang_thai, ['da_nop', 'da_tiep_nhan'], true)) {
            return back()->withErrors(['xu_ly' => 'Chỉ xử lý được báo cáo đang chờ tiếp nhận hoặc đã tiếp nhận.']);
        }
        if ($baoCao->trang_thai === 'da_tiep_nhan' && $data['hanh_dong'] !== 'yeu_cau_bo_sung') {
            return back()->withErrors(['xu_ly' => 'Báo cáo đã tiếp nhận chỉ có thể trả lại (yêu cầu bổ sung).']);
        }
        $tiepNhan = $data['hanh_dong'] === 'tiep_nhan';
        $baoCao->update([
            'trang_thai' => $tiepNhan ? 'da_tiep_nhan' : 'yeu_cau_bo_sung',
            'nguoi_xu_ly_id' => $request->user()->id,
            'xu_ly_luc' => now(),
            'y_kien_xu_ly' => $data['y_kien'] ?? null,
        ]);

        return redirect()->route('bao-cao.thang.show', $baoCao)->with('thong_bao', $tiepNhan
            ? 'Đã tiếp nhận báo cáo tháng.'
            : 'Đã trả báo cáo, yêu cầu chủ đầu tư bổ sung.');
    }

    /** Tiếp nhận hàng loạt các báo cáo đang chờ (trạng thái "đã nộp") trong 1 lượt. */
    public function xuLyHangLoat(Request $request)
    {
        $data = $request->validate(['bao_cao_ids' => ['required', 'array', 'min:1'], 'bao_cao_ids.*' => ['integer']]);
        $ds = BaoCaoThang::whereIn('id', $data['bao_cao_ids'])->where('trang_thai', 'da_nop')->get();
        $user = $request->user();

        foreach ($ds as $baoCao) {
            $baoCao->update(['trang_thai' => 'da_tiep_nhan', 'nguoi_xu_ly_id' => $user->id, 'xu_ly_luc' => now()]);
        }

        return back()->with('thong_bao', 'Đã tiếp nhận '.$ds->count().' báo cáo.');
    }

    /** Giá trị điền sẵn lần đầu báo cáo tháng một dự án — lấy từ hồ sơ dự án và báo cáo tuần gần nhất */
    private function giaTriTuDuAn(DuAn $d, User $user, ?BaoCaoTuanDuAn $tuan): array
    {
        return [
            'cong_suat' => $d->cong_suat_mw ? BaoCaoTuanDuAn::so($d->cong_suat_mw, 3) : $d->cong_suat,
            'chu_dau_tu' => $d->chu_dau_tu ?: ($user->don_vi ?: $user->name),
            'du_kien_khoi_cong' => $d->ngay_khoi_cong ? 'Ngày '.$d->ngay_khoi_cong->format('d/m/Y') : null,
            'du_kien_hoan_thanh' => $d->ngay_hoan_thanh ? 'Tháng '.$d->ngay_hoan_thanh->format('n/Y') : BaoCaoThangDuAn::chu($d->tien_do_van_hanh),
            'tinh_hinh' => BaoCaoThangDuAn::chu($tuan?->cong_viec_dang_lam),
            'kho_khan' => BaoCaoThangDuAn::chu($tuan?->kho_khan),
        ];
    }

    /** Điều kiện "trước kỳ $ky" theo cặp (năm, tháng) */
    private function truocKy($q, KyThang $ky, string $tien = ''): void
    {
        $q->where($tien.'nam', '<', $ky->nam)
            ->orWhere(fn ($q) => $q->where($tien.'nam', $ky->nam)->where($tien.'thang', '<', $ky->thang));
    }

    /** Chủ đầu tư đã duyệt, được gán ít nhất 1 dự án và được Sở đặt chế độ báo cáo tháng */
    private function chuDauTuPhaiBaoCao(): Collection
    {
        return User::where('vai_tro', 'chu_dau_tu')->where('da_duyet', true)->where('bc_thang', true)->whereHas('duAn')
            ->with(['duAn' => fn ($q) => $q->orderBy('ten')])
            ->get()
            ->sortBy(fn (User $u) => $u->don_vi ?: $u->name)
            ->values();
    }

    private function kiemTraQuyenXem(User $user, BaoCaoThang $baoCao): void
    {
        $duoc = $user->vai_tro === 'chu_dau_tu'
            ? (int) $baoCao->nguoi_bao_cao_id === (int) $user->id
            : $baoCao->daTungNop();
        abort_unless($duoc, 404);
    }

    private function chiChuDauTu(Request $request): User
    {
        $user = $request->user();
        abort_unless($user->vai_tro === 'chu_dau_tu', 403, 'Chỉ tài khoản chủ đầu tư mới lập được báo cáo tháng.');

        return $user;
    }
}
