@php $conCua = $conCua ?? null; @endphp
<tr @if ($conCua) data-thuoc-nhom="{{ $conCua }}" hidden @endif>
  <td @if ($conCua) style="padding-left:28px" @endif>
    <a href="{{ route('lop-ha-tang.doi-tuong', $l) }}"><b>{{ $l->ten }}</b></a>
    @if ($l->quy_hoach)
      <span class="hint" style="color:var(--ink-3);border:1px solid var(--line);border-radius:2px;padding:0 4px">Quy hoạch</span>
    @endif
    <br><span class="hint">{{ $l->ten_file }}</span>
  </td>
  <td>{{ $l->nguon }}</td>
  <td class="num">{{ number_format($l->doi_tuong_count, 0, ',', '.') }}</td>
  <td class="num">{{ number_format($l->so_tram, 0, ',', '.') }}</td>
  <td><span class="trang-thai {{ $l->hien_thi ? 'bat' : '' }}"><i></i>{{ $l->hien_thi ? 'Đang bật' : 'Đã tắt' }}</span></td>
  <td style="white-space:nowrap">
    <a href="{{ route('lop-ha-tang.doi-tuong', $l) }}" class="btn sm">Chi tiết</a>
    @if (auth()->user()->duocSua())
      <button type="button" class="btn sm" data-sua
              data-action="{{ route('lop-ha-tang.update', $l) }}"
              data-ten="{{ $l->ten }}" data-nguon="{{ $l->nguon }}"
              data-loai="{{ $l->loai_hinh_hoc }}" data-mau="{{ $l->mau }}"
              data-bieu-tuong="{{ $l->bieu_tuong }}" data-kich-thuoc="{{ $l->kich_thuoc }}"
              data-quy-hoach="{{ $l->quy_hoach ? 1 : 0 }}">Sửa</button>
      <form method="post" action="{{ route('lop-ha-tang.toggle', $l) }}" style="display:inline">
        @csrf @method('patch')
        <button class="btn sm">{{ $l->hien_thi ? 'Tắt' : 'Bật' }}</button>
      </form>
    @endif
    @if (auth()->user()->duocXoa())
      <form method="post" action="{{ route('lop-ha-tang.destroy', $l) }}" style="display:inline"
            onsubmit="return confirm('Gỡ lớp {{ $l->ten }} và toàn bộ đối tượng của lớp?')">
        @csrf @method('delete')
        <button class="btn sm danger">Gỡ</button>
      </form>
    @endif
  </td>
</tr>
