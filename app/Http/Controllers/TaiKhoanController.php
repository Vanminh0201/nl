<?php

namespace App\Http\Controllers;

use App\Models\DuAn;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TaiKhoanController extends Controller
{
    public function index()
    {
        $dsTaiKhoan = User::query()->with('duAn')->orderBy('da_duyet')->orderBy('name')->get();
        $taiKhoanChoDuyet = $dsTaiKhoan->where('vai_tro', 'chu_dau_tu')->where('da_duyet', false);
        $duAnChoDuyetIds = $taiKhoanChoDuyet->flatMap(fn (User $taiKhoan) => $taiKhoan->du_an_cho_duyet ?? [])->unique();
        $taiKhoanChoDuyetIds = $taiKhoanChoDuyet->modelKeys();

        return view('tai-khoan.index', [
            // Tài khoản chưa duyệt (chủ đầu tư tự đăng ký) nổi lên đầu danh sách để quản trị
            // viên không bỏ sót — trong nhóm đã duyệt/chưa duyệt thì vẫn sắp theo tên.
            'dsTaiKhoan' => $dsTaiKhoan,
            'duAnChoDuyet' => DuAn::query()->whereIn('id', $duAnChoDuyetIds)->get(['id', 'ten'])->keyBy('id'),
            'dsDuAnCoTheGan' => DuAn::query()
                ->where(function ($query) use ($taiKhoanChoDuyetIds): void {
                    $query->whereNull('chu_dau_tu_id')->orWhereIn('chu_dau_tu_id', $taiKhoanChoDuyetIds);
                })
                ->orderBy('ten')
                ->get(['id', 'ten', 'loai_hinh', 'dia_diem', 'dia_diem_hien_nay', 'dia_diem_truoc_day', 'chu_dau_tu_id']),
            'vaiTro' => User::VAI_TRO,
            'mucXem' => User::MUC_XEM,
        ]);
    }

    public function duyet(Request $request, User $taiKhoan)
    {
        abort_unless($taiKhoan->vai_tro === 'chu_dau_tu', 404);

        if ($taiKhoan->da_duyet) {
            return back()->with('thong_bao', "Tài khoản \"{$taiKhoan->name}\" đã được duyệt.");
        }

        $data = $request->validate([
            'du_an_ids' => ['nullable', 'array'],
            'du_an_ids.*' => ['required', 'integer', 'distinct', Rule::exists('du_an', 'id')],
        ], [], ['du_an_ids' => 'dự án', 'du_an_ids.*' => 'dự án']);
        $duAnIdsChon = array_values(array_unique($data['du_an_ids'] ?? []));

        DB::transaction(function () use ($taiKhoan, $duAnIdsChon): void {
            $taiKhoan = User::query()->lockForUpdate()->findOrFail($taiKhoan->getKey());
            $dsDuAnChon = DuAn::query()
                ->whereIn('id', $duAnIdsChon)
                ->where(function ($query) use ($taiKhoan): void {
                    $query->whereNull('chu_dau_tu_id')->orWhere('chu_dau_tu_id', $taiKhoan->id);
                })
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id', 'chu_dau_tu_id']);

            if ($dsDuAnChon->count() !== count($duAnIdsChon)) {
                throw ValidationException::withMessages([
                    'du_an_ids' => 'Không thể duyệt: có dự án bạn chọn đã được gán cho tài khoản khác hoặc không còn khả dụng. Hãy tải lại danh sách và chọn lại.',
                ]);
            }

            DuAn::query()
                ->where('chu_dau_tu_id', $taiKhoan->id)
                ->whereNotIn('id', $duAnIdsChon ?: [0])
                ->get()
                ->each(fn (DuAn $duAn) => $duAn->update(['chu_dau_tu_id' => null]));

            foreach ($dsDuAnChon as $duAn) {
                $duAn->update(['chu_dau_tu_id' => $taiKhoan->id]);
            }

            $taiKhoan->update(['da_duyet' => true, 'du_an_cho_duyet' => null]);
        });

        return back()->with('thong_bao', "Đã duyệt tài khoản \"{$taiKhoan->name}\" và gán ".count($duAnIdsChon).' dự án.');
    }

    public function store(Request $request)
    {
        $data = $this->duLieuHopLe($request);
        $data['password'] = $request->validate(['password' => ['required', 'string', 'min:6']])['password'];

        User::create($data);

        return back()->with('thong_bao', "Đã thêm tài khoản \"{$data['name']}\".");
    }

    public function update(Request $request, User $taiKhoan)
    {
        $data = $this->duLieuHopLe($request, $taiKhoan);

        // Không cho tự đổi vai trò của chính mình để tránh tự khoá quyền quản trị của bản thân
        // giữa phiên đang đăng nhập — muốn đổi vai trò của mình thì phải nhờ quản trị viên khác.
        if ($taiKhoan->id === $request->user()->id && $data['vai_tro'] !== $taiKhoan->vai_tro) {
            return back()->withErrors(['vai_tro' => 'Không thể tự đổi vai trò của chính tài khoản đang đăng nhập.']);
        }

        if ($taiKhoan->vai_tro === 'quan_tri' && $data['vai_tro'] !== 'quan_tri'
            && User::where('vai_tro', 'quan_tri')->count() <= 1) {
            return back()->withErrors(['vai_tro' => 'Không thể đổi vai trò — đây là tài khoản Quản trị hệ thống cuối cùng.']);
        }

        if ($request->filled('password')) {
            $data['password'] = $request->validate(['password' => ['string', 'min:6']])['password'];
        }

        // Đổi vai trò thì quyền xem quay về mặc định của vai trò mới (bỏ phần đã tuỳ chỉnh riêng)
        if ($data['vai_tro'] !== $taiKhoan->vai_tro) {
            $data['quyen_xem'] = null;
        }

        $taiKhoan->update($data);

        return back()->with('thong_bao', "Đã cập nhật tài khoản \"{$taiKhoan->name}\".");
    }

    /** Cấp/thu quyền xem từng mục (User::MUC_XEM) cho 1 tài khoản; quản trị hệ thống luôn xem được hết */
    public function quyenXem(Request $request, User $taiKhoan)
    {
        if ($taiKhoan->vai_tro === 'quan_tri') {
            return back()->withErrors(['quyen_xem' => 'Quản trị hệ thống luôn được xem tất cả các mục, không cần phân quyền.']);
        }

        $data = $request->validate([
            'quyen_xem' => ['nullable', 'array'],
            'quyen_xem.*' => [Rule::in(array_keys(User::MUC_XEM))],
        ], [], ['quyen_xem' => 'quyền xem', 'quyen_xem.*' => 'quyền xem']);

        $ds = $request->boolean('mac_dinh') ? null : array_values(array_unique($data['quyen_xem'] ?? []));
        if ($ds !== null) {
            // "Xem tất cả dự án" chỉ có nghĩa với chủ đầu tư — các vai trò khác luôn thấy hết dự án
            if ($taiKhoan->vai_tro !== 'chu_dau_tu' && ! in_array('tat_ca_du_an', $ds, true)) {
                $ds[] = 'tat_ca_du_an';
            }
            // Trùng đúng mặc định của vai trò thì lưu "theo mặc định" (null) cho gọn
            $a = $ds;
            $b = $taiKhoan->quyenXemMacDinh();
            sort($a);
            sort($b);
            if ($a === $b) {
                $ds = null;
            }
        }

        $taiKhoan->update(['quyen_xem' => $ds]);

        return back()->with('thong_bao', "Đã cập nhật quyền xem của tài khoản \"{$taiKhoan->name}\".");
    }

    public function destroy(Request $request, User $taiKhoan)
    {
        if ($taiKhoan->id === $request->user()->id) {
            return back()->withErrors(['tai_khoan' => 'Không thể xoá chính tài khoản đang đăng nhập.']);
        }

        if ($taiKhoan->vai_tro === 'quan_tri' && User::where('vai_tro', 'quan_tri')->count() <= 1) {
            return back()->withErrors(['tai_khoan' => 'Không thể xoá — đây là tài khoản Quản trị hệ thống cuối cùng.']);
        }

        if ($taiKhoan->vai_tro === 'chu_dau_tu' && $taiKhoan->duAn()->exists()) {
            return back()->withErrors(['tai_khoan' => "Không thể xoá — tài khoản \"{$taiKhoan->name}\" đang được gán cho ít nhất 1 dự án. Hãy đổi chủ đầu tư của các dự án đó trước."]);
        }

        $ten = $taiKhoan->name;
        $taiKhoan->delete();

        return back()->with('thong_bao', "Đã xoá tài khoản \"{$ten}\".");
    }

    private function duLieuHopLe(Request $request, ?User $taiKhoan = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($taiKhoan?->id)],
            'vai_tro' => ['required', Rule::in(array_keys(User::VAI_TRO))],
            'don_vi' => ['nullable', 'string', 'max:255'],
        ], [], [
            'name' => 'tên',
            'email' => 'thư điện tử',
            'vai_tro' => 'vai trò',
            'don_vi' => 'đơn vị',
        ]);
    }
}
