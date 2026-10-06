{{-- Nội dung chữ của báo cáo: mỗi dòng 1 đoạn, mọi dòng thẳng một lề trái, căn đều hai bên;
     dòng bắt đầu bằng "a)", "1." in đậm phần số thứ tự --}}
@php $dsDong = \App\Models\BaoCaoTuanDuAn::tachDong($chu ?? null); @endphp
@forelse ($dsDong as $dong)
  @if (preg_match('/^((?:[a-zđ]|\d{1,2})[\)\.])\s+(.*)$/iu', $dong, $m))
    <p class="muc"><b>{{ $m[1] }}</b> {{ $m[2] }}</p>
  @else
    <p>{{ $dong }}</p>
  @endif
@empty
  <p class="bc-trong">{{ $trong ?? 'Chưa cập nhật.' }}</p>
@endforelse
