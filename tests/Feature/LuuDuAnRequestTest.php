<?php

namespace Tests\Feature;

use App\Http\Requests\LuuDuAnRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class LuuDuAnRequestTest extends TestCase
{
    public function test_location_validation_accepts_multiple_official_places_and_rejects_unknown_values(): void
    {
        $request = LuuDuAnRequest::create('/du-an', 'POST');
        $request->setRouteResolver(static fn () => null);
        $rules = array_intersect_key($request->rules(), array_flip([
            'dia_diem_hien_nay',
            'dia_diem_hien_nay.*',
            'dia_diem_truoc_day',
            'dia_diem_truoc_day.*',
        ]));

        $hopLe = Validator::make([
            'dia_diem_hien_nay' => ['Phường Đông Hà', 'Xã Cam Lộ'],
            'dia_diem_truoc_day' => ['Xã Triệu Nguyên — Huyện Đa Krông, tỉnh Quảng Trị'],
        ], $rules, $request->messages());
        $khongHopLe = Validator::make([
            'dia_diem_hien_nay' => ['Địa điểm không tồn tại'],
            'dia_diem_truoc_day' => ['Xã tự đặt'],
        ], $rules, $request->messages());

        $this->assertTrue($hopLe->passes());
        $this->assertFalse($khongHopLe->passes());
        $this->assertSame(
            'Hãy chỉ chọn xã/phường trong danh sách đơn vị hành chính mới của tỉnh Quảng Trị.',
            $khongHopLe->errors()->first('dia_diem_hien_nay.0')
        );
        $this->assertSame(
            'Hãy chỉ chọn xã/phường thuộc Quảng Bình hoặc Quảng Trị trước sáp nhập.',
            $khongHopLe->errors()->first('dia_diem_truoc_day.0')
        );
    }
}
