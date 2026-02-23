

exports.detectParentSliderItemCon = function(_t){


  var $conSliderItem = null;

  if (_t.parent().parent().parent().parent().hasClass('slider-item')) {
    $conSliderItem = _t.parent().parent().parent().parent();
  }
  if (_t.parent().parent().parent().parent().parent().hasClass('slider-item')) {
    $conSliderItem = _t.parent().parent().parent().parent().parent();
  }

  if (_t.parent().parent().parent().parent().parent().parent().hasClass('slider-item')) {
    $conSliderItem = _t.parent().parent().parent().parent().parent().parent();
  }
  if (_t.parent().parent().parent().parent().parent().parent().parent().hasClass('slider-item')) {
    $conSliderItem = _t.parent().parent().parent().parent().parent().parent().parent();
  }
  if (_t.parent().parent().parent().parent().parent().parent().parent().parent().hasClass('slider-item')) {
    $conSliderItem = _t.parent().parent().parent().parent().parent().parent().parent().parent();
  }
  // -- 9
  if (_t.parent().parent().parent().parent().parent().parent().parent().parent().parent().hasClass('slider-item')) {
    $conSliderItem = _t.parent().parent().parent().parent().parent().parent().parent().parent().parent();
  }
  // -- 10
  if (_t.parent().parent().parent().parent().parent().parent().parent().parent().parent().parent().hasClass('slider-item')) {
    $conSliderItem = _t.parent().parent().parent().parent().parent().parent().parent().parent().parent().parent();
  }

  return $conSliderItem;
}