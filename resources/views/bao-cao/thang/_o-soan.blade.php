{{-- 1 ô nội dung của dự án trên trang lập báo cáo tháng. Biến: $d (dự án), $ten / $khoa (tên ô trong form / khoá lỗi),
     $gt (giá trị điền sẵn), $tr (nội dung tháng trước để so sánh), $goiY, $cot, $nhan, $soDong. "Tình hình" bắt buộc khi gửi. --}}
@php $batBuoc = $cot === 'tinh_hinh'; @endphp
<div class="bcs-o" data-o data-so-sanh data-truoc="{{ $tr[$cot] ?? '' }}" @if ($batBuoc) data-bat-buoc @endif>
  <div class="bcs-o-dau">
    <label for="o-{{ $d->id }}-{{ $cot }}">{{ $nhan }}@if ($batBuoc) <span class="bb">*</span>@endif</label>
    <span class="bcs-nhan" data-nhan></span>
  </div>
  <textarea id="o-{{ $d->id }}-{{ $cot }}" name="{{ $ten }}[{{ $cot }}]" rows="{{ $soDong }}" data-tu-gian
            placeholder="{{ $goiY[$cot] }}">{{ old("$khoa.$cot", $gt[$cot]) }}</textarea>
  @error("$khoa.$cot")<span class="sai">{{ $message }}</span>@enderror
  @if (! empty($tr[$cot]))
    <details class="bcs-truoc">
      <summary>Xem nội dung {{ mb_strtolower($tr['ky']) }}</summary>
      <div class="bcs-truoc-noi-dung">{{ $tr[$cot] }}</div>
    </details>
  @endif
</div>
