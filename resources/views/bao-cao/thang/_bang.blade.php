{{--
  Bảng báo cáo tháng theo mẫu "Tình hình triển khai các dự án năng lượng trên địa bàn tỉnh" (TongHopBaoCaoThang):
  nhóm theo xã, trong xã chia theo tình trạng triển khai.
  Biến: $bang; $ky (tháng của bảng); $hienNguon (bool — chip nguồn nội dung: Sở biên tập / chủ đầu tư / chép từ tháng
  trước); $coTheSua (bool — nút "Sửa" từng dự án, mở hộp biên tập trên trang Sở).
--}}
@php
  $hienNguon = $hienNguon ?? false;
  $coTheSua = $coTheSua ?? false;
  $soCot = 8 + ($coTheSua ? 1 : 0);
  $tenKy = fn ($k) => 'T'.$k->thang.'/'.$k->nam;
  $daCoNeo = [];
@endphp
@if ($bang['xa'])
  <div class="tablewrap bct-khung">
    <table class="bct-bang bct-xa">
      <thead>
        <tr>
          <th rowspan="2" class="bct-tt">TT</th>
          <th rowspan="2" class="bct-thong-tin">Thông tin dự án<span>(Tên, chủ đầu tư, công suất)</span></th>
          <th rowspan="2" class="bct-moc">Dự kiến khởi công</th>
          <th rowspan="2" class="bct-moc">Dự kiến hoàn thành</th>
          <th rowspan="2" class="bct-dai">Tình hình thực hiện</th>
          <th rowspan="2" class="bct-vua">Khó khăn vướng mắc</th>
          <th colspan="2">Kiến nghị xử lý</th>
          @if ($coTheSua)<th rowspan="2" class="bct-sua"><span class="sr-only">Biên tập</span></th>@endif
        </tr>
        <tr><th class="bct-vua">Xã/phường</th><th class="bct-vua">Sở, ngành</th></tr>
      </thead>
      <tbody>
      @foreach ($bang['xa'] as $xa)
        <tr class="bct-xa-dau" data-xa><td colspan="{{ $soCot }}">{{ $xa['ten'] }}</td></tr>
        @foreach ($xa['nhom'] as $nhom)
          <tr class="bct-tinh-trang" data-tinh-trang><td colspan="{{ $soCot }}">* {{ $nhom['ten'] }}</td></tr>
          @foreach ($nhom['muc'] as $m)
            @php
              $id = $m['du_an']->id;
              $neo = ! isset($daCoNeo[$id]);
              $daCoNeo[$id] = true;
            @endphp
            <tr @if ($neo) id="th-{{ $id }}" @endif data-du-an="{{ $id }}" data-tim="{{ $m['ten'] }} {{ $m['chu_dau_tu'] }}"
                data-co-noi-dung="{{ array_filter($m['noi_dung']) ? 1 : 0 }}">
              <td class="bct-tt">{{ $m['tt'] }}</td>
              <td class="bct-thong-tin">
                <b>{{ $m['ten'] }}</b>
                @if ($m['chu_dau_tu'])<div>{{ $m['chu_dau_tu'] }}</div>@endif
                @if ($m['cong_suat'])<div>{{ $m['cong_suat'] }}</div>@endif
                @if ($hienNguon)
                  <div class="bct-nguon">
                    @if ($m['nguon'] === 'so')
                      <span class="chip xanh" title="Sở biên tập{{ $m['so_sua']?->nguoiSua ? ' — '.$m['so_sua']->nguoiSua->name : '' }}">Sở biên tập{{ $m['ky_nguon']->bang($ky) ? '' : ' · chép từ '.$tenKy($m['ky_nguon']) }}</span>
                    @elseif ($m['nguon'] === 'chu_dau_tu')
                      <a href="{{ route('bao-cao.thang.show', $m['bao_cao']) }}" class="chip {{ $m['ky_nguon']->bang($ky) ? 'co' : '' }}"
                         title="Mở báo cáo của chủ đầu tư">Chủ đầu tư · {{ $m['ky_nguon']->bang($ky) ? $tenKy($m['ky_nguon']) : 'chép từ '.$tenKy($m['ky_nguon']) }}</a>
                    @else
                      <span class="chip">Chưa có nội dung</span>
                    @endif
                  </div>
                @endif
              </td>
              @foreach (\App\Services\XuatExcelBaoCaoThang::COT_NOI_DUNG as $cot)
                <td class="bct-van">@include('bao-cao._doan-van', ['chu' => $m['noi_dung'][$cot], 'trong' => ''])</td>
              @endforeach
              @if ($coTheSua)
                <td class="bct-sua"><button type="button" class="btn sm" data-sua-th="{{ $id }}">Sửa</button></td>
              @endif
            </tr>
          @endforeach
        @endforeach
      @endforeach
      </tbody>
    </table>
  </div>
@else
  <p class="trong" style="margin:0">Chưa có dự án nào.</p>
@endif
