@push('cuoi_trang')
<script>
// Ô nhập sản lượng: dán cả cột số từ Excel thì điền lần lượt xuống các dòng dưới; rời ô thì định dạng lại số
(function () {
  var o = Array.prototype.slice.call(document.querySelectorAll('input.sl-so'));
  if (!o.length) return;
  function docSo(s) {
    s = String(s).replace(/[\s ]+/g, '');
    if (s === '') return null;
    if (s.indexOf('.') >= 0 && s.indexOf(',') >= 0) {
      s = s.lastIndexOf(',') > s.lastIndexOf('.') ? s.replace(/\./g, '').replace(',', '.') : s.replace(/,/g, '');
    } else if (/^\d{1,3}(\.\d{3})+$/.test(s)) {
      s = s.replace(/\./g, '');
    } else if (/^\d{1,3}(,\d{3})+$/.test(s)) {
      s = s.replace(/,/g, '');
    } else {
      s = s.replace(',', '.');
    }
    var v = Number(s);
    return isFinite(v) && v >= 0 ? v : NaN;
  }
  function dinhDang(inp) {
    var v = docSo(inp.value);
    inp.classList.toggle('sai', v !== null && isNaN(v));
    if (v !== null && !isNaN(v)) inp.value = v.toLocaleString('vi-VN', { maximumFractionDigits: 1 });
  }
  o.forEach(function (inp, i) {
    inp.addEventListener('blur', function () { dinhDang(inp); });
    inp.addEventListener('paste', function (e) {
      var chu = (e.clipboardData || window.clipboardData).getData('text');
      var dong = chu.replace(/\r/g, '').split('\n').filter(function (x, k, a) { return x !== '' || k < a.length - 1; });
      if (dong.length < 2) return;
      e.preventDefault();
      dong.forEach(function (x, k) {
        var dich = o[i + k];
        if (dich) { dich.value = x.split('\t')[0]; dinhDang(dich); }
      });
    });
    // Enter: xuống ô dưới thay vì gửi form
    inp.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') { e.preventDefault(); if (o[i + 1]) o[i + 1].focus(); }
    });
  });
})();
</script>
@endpush
