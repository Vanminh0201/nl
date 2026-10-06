{{-- Ô giải ngân trên bảng in tổng hợp: ghi rõ từng chỉ tiêu (đơn vị tỷ đồng ghi ở tiêu đề cột) --}}
@php $soGn = fn ($v) => \App\Models\BaoCaoTuanDuAn::so($v); @endphp
@if ($mau !== null)<div><span>{{ $nhanMau }}:</span><span>{{ $soGn($mau) }}</span></div>@endif
@if ($giaTri !== null)<div><span>{{ $nhan }}:</span><span>{{ $soGn($giaTri) }}</span></div>@endif
@if ($giaTri !== null && $mau)<div class="dat">Đạt {{ \App\Models\BaoCaoTuanDuAn::phanTram($giaTri, $mau) }}%</div>@endif
