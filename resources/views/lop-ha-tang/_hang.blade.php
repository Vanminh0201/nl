<article class="ha-tang-layer" data-lop-ha-tang data-branch="{{ $maTinhTrang }}"
         data-loai="{{ $l->loai_hinh_hoc }}" data-hien-thi="{{ $l->hien_thi ? '1' : '0' }}"
         data-tim="{{ \Illuminate\Support\Str::lower($l->ten.' '.$l->nguon.' '.$l->ten_file) }}"
         @if ($conCua ?? false) data-thuoc-nhom="{{ $conCua }}" @endif>
  <div class="ha-tang-layer-main">
    <div class="ha-tang-layer-heading">
      <a class="ha-tang-layer-link" href="{{ route('lop-ha-tang.doi-tuong', $l) }}">{{ $l->ten }}</a>
      <span class="ha-tang-type">{{ ['diem' => 'Điểm', 'duong' => 'Đường', 'vung' => 'Vùng'][$l->loai_hinh_hoc] ?? 'Lớp' }}</span>
      @if ($l->quy_hoach)
        <span class="ha-tang-planned">Quy hoạch</span>
      @endif
    </div>
    <div class="ha-tang-layer-meta">
      <span><b>Nguồn:</b> {{ $l->nguon ?: 'Chưa ghi' }}</span>
      <span class="ha-tang-file" title="{{ $l->ten_file }}">{{ $l->ten_file }}</span>
    </div>
  </div>
  <div class="ha-tang-layer-stats">
    <span><strong>{{ number_format($l->doi_tuong_count, 0, ',', '.') }}</strong> đối tượng</span>
    <span><strong>{{ number_format($l->so_tram, 0, ',', '.') }}</strong> trạm</span>
    <span class="trang-thai {{ $l->hien_thi ? 'bat' : '' }}"><i></i>{{ $l->hien_thi ? 'Đang hiển thị' : 'Đã tắt' }}</span>
  </div>
  <div class="ha-tang-actions">
    <a href="{{ route('lop-ha-tang.doi-tuong', $l) }}" class="btn sm">Đối tượng</a>
    @if (auth()->user()->duocSua())
      <button type="button" class="btn sm" data-sua
              data-action="{{ route('lop-ha-tang.update', $l) }}"
              data-ten="{{ $l->ten }}" data-nguon="{{ $l->nguon }}"
              data-loai="{{ $l->loai_hinh_hoc }}" data-mau="{{ $l->mau }}"
              data-bieu-tuong="{{ $l->bieu_tuong }}" data-kich-thuoc="{{ $l->kich_thuoc }}"
              data-quy-hoach="{{ $l->quy_hoach ? 1 : 0 }}">Sửa</button>
      <form method="post" action="{{ route('lop-ha-tang.toggle', $l) }}">
        @csrf @method('patch')
        <button class="btn sm" title="{{ $l->hien_thi ? 'Ẩn lớp khỏi bản đồ' : 'Hiển thị lớp trên bản đồ' }}">
          {{ $l->hien_thi ? 'Ẩn' : 'Hiển thị' }}
        </button>
      </form>
    @endif
    @if (auth()->user()->duocXoa())
      <form method="post" action="{{ route('lop-ha-tang.destroy', $l) }}"
            onsubmit="return confirm({{ \Illuminate\Support\Js::from('Gỡ lớp "'.$l->ten.'" và toàn bộ đối tượng của lớp?') }})">
        @csrf @method('delete')
        <button class="btn sm danger">Gỡ</button>
      </form>
    @endif
  </div>
</article>
