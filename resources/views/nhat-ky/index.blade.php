@extends('layouts.app')
@section('tieu_de', 'Nhật ký hoạt động')

@section('noi_dung')
@php
  $muiGio = \App\Http\Controllers\NhatKyController::MUI_GIO;
  $tenVaiTro = \App\Models\User::VAI_TRO;
  $tenNhom = \App\Models\NhatKy::NHOM;
  $tenKieu = \App\Support\NhatKyHoatDong::KIEU;
  $dangLoc = request()->hasAny(['tu_khoa', 'nguoi_dung', 'nhom', 'tu_ngay', 'den_ngay']);
  $chuoi = fn ($v) => is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : (is_bool($v) ? ($v ? 'Có' : 'Không') : (string) $v);
@endphp
<div class="pad">
  <div class="nk-so">
    <div class="nk-o"><span>Thay đổi dữ liệu hôm nay</span><b>{{ number_format($soLieu['thay_doi'], 0, ',', '.') }}</b></div>
    <div class="nk-o"><span>Lượt đăng nhập hôm nay</span><b>{{ number_format($soLieu['dang_nhap'], 0, ',', '.') }}</b></div>
    <div class="nk-o"><span>Người dùng hoạt động hôm nay</span><b>{{ number_format($soLieu['nguoi_dung'], 0, ',', '.') }}</b></div>
    <div class="nk-o{{ $soLieu['that_bai'] ? ' canh-bao' : '' }}"><span>Đăng nhập sai trong 7 ngày</span><b>{{ number_format($soLieu['that_bai'], 0, ',', '.') }}</b></div>
  </div>

  <form method="get" class="thanhloc">
    <label class="grow">Từ khoá
      <input type="search" name="tu_khoa" value="{{ is_scalar(request('tu_khoa')) ? request('tu_khoa') : '' }}" placeholder="Hoạt động, đối tượng, người dùng, địa chỉ IP">
    </label>
    <label>Người thực hiện
      <select name="nguoi_dung">
        <option value="">Tất cả</option>
        @foreach ($dsNguoiDung as $u)
          <option value="{{ $u->id }}" @selected((string) request('nguoi_dung') === (string) $u->id)>{{ $u->name }}</option>
        @endforeach
      </select>
    </label>
    <label>Nhóm hoạt động
      <select name="nhom">
        <option value="chinh" @selected($nhom === 'chinh')>Tất cả, trừ xem trang</option>
        @foreach ($tenNhom as $ma => $ten)
          <option value="{{ $ma }}" @selected($nhom === $ma)>{{ $ten }}</option>
        @endforeach
        <option value="tat_ca" @selected($nhom === 'tat_ca')>Tất cả</option>
      </select>
    </label>
    <label>Từ ngày <input type="date" name="tu_ngay" value="{{ is_scalar(request('tu_ngay')) ? request('tu_ngay') : '' }}"></label>
    <label>Đến ngày <input type="date" name="den_ngay" value="{{ is_scalar(request('den_ngay')) ? request('den_ngay') : '' }}"></label>
    <button class="btn primary">Lọc</button>
    @if ($dangLoc)
      <a class="btn" href="{{ route('nhat-ky.index') }}">Bỏ lọc</a>
    @endif
  </form>

  <div class="tablewrap">
    <table class="nk-bang">
      <colgroup>
        <col style="width:110px"><col style="width:200px"><col><col style="width:30%"><col style="width:130px">
      </colgroup>
      <thead>
        <tr><th>Thời gian</th><th>Người thực hiện</th><th>Hoạt động</th><th>Đối tượng</th><th>Địa chỉ IP</th></tr>
      </thead>
      <tbody>
      @forelse ($dsNhatKy as $n)
        @php
          $tg = $n->created_at->copy()->timezone($muiGio);
          $ct = $n->chi_tiet ?? [];
        @endphp
        <tr>
          <td class="nk-tg">{{ $tg->format('d/m/Y') }}<small>{{ $tg->format('H:i:s') }}</small></td>
          <td>
            {{ $n->ten_nguoi_dung ?: '—' }}
            @if ($n->vai_tro)<small>{{ $tenVaiTro[$n->vai_tro] ?? $n->vai_tro }}</small>@endif
          </td>
          <td>
            <span class="nk-nhom nk-{{ $n->nhom }}">{{ $tenNhom[$n->nhom] ?? $n->nhom }}</span><span class="nk-hd{{ isset($ct['loi']) ? ' loi' : '' }}" title="{{ $n->phuong_thuc }} {{ $n->duong_dan }}">{{ $n->hanh_dong }}</span>
            @if ($ct)
              <details class="nk-ct">
                <summary>Chi tiết</summary>
                @if (! empty($ct['loi']))
                  <p class="nk-loi">{{ $ct['loi'] }}</p>
                @endif
                @if (! empty($ct['email']))
                  <p>Thư điện tử đã nhập: {{ $ct['email'] }}</p>
                @endif
                @foreach ($ct['thay_doi'] ?? [] as $d)
                  <div class="nk-td">
                    <b>{{ $tenKieu[$d['kieu']] ?? $d['kieu'] }} {{ isset($d['so_luong']) ? $d['so_luong'].' ' : '' }}{{ mb_strtolower($d['mo_hinh']) }}</b>@if (! empty($d['ten']))<span>: {{ $d['ten'] }}</span>@endif
                    @if (! empty($d['truong']))
                      <table class="nk-truong">
                        <tr><th>Trường</th><th>Trước</th><th>Sau</th></tr>
                        @foreach ($d['truong'] as $t)
                          <tr><td>{{ $t['truong'] }}</td><td>{{ $t['truoc'] ?? '—' }}</td><td>{{ $t['sau'] ?? '—' }}</td></tr>
                        @endforeach
                      </table>
                    @endif
                  </div>
                @endforeach
                @if (! empty($ct['du_lieu']))
                  <table class="nk-truong">
                    <tr><th>Dữ liệu gửi lên</th><th>Giá trị</th></tr>
                    @foreach ($ct['du_lieu'] as $k => $v)
                      <tr><td>{{ $k }}</td><td>{{ $chuoi($v) }}</td></tr>
                    @endforeach
                  </table>
                @endif
                <p class="nk-url">{{ $n->phuong_thuc }} {{ $n->duong_dan }}{{ $n->ma_phan_hoi ? ' · '.$n->ma_phan_hoi : '' }}</p>
              </details>
            @endif
          </td>
          <td>
            @if ($n->lien_ket)
              <a href="{{ $n->lien_ket }}">{{ $n->doi_tuong }}</a>
            @else
              {{ $n->doi_tuong }}
            @endif
          </td>
          <td class="nk-ip" title="{{ $n->trinh_duyet }}">{{ $n->ip }}</td>
        </tr>
      @empty
        <tr><td colspan="5" class="nk-trong">Không có hoạt động nào.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  <div style="margin-top:12px">{{ $dsNhatKy->links('vendor.pagination.don-gian') }}</div>
</div>
@endsection
